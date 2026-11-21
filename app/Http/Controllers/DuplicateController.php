<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\DuplicateFlag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DuplicateController extends Controller
{
    public function index(Request $request): View
    {
        $municipalities = Beneficiary::distinct()
            ->whereNotNull('municipality')
            ->where('municipality', '!=', '')
            ->pluck('municipality')
            ->sort()
            ->values();

        $selectedMunicipality = trim((string) $request->input('municipality'));
        $barangayQuery = Beneficiary::query()
            ->whereNotNull('barangay')
            ->where('barangay', '!=', '');

        if ($selectedMunicipality !== '') {
            $barangayQuery->where(function ($q) use ($selectedMunicipality) {
                $q->where('municipality', $selectedMunicipality)
                    ->orWhereRaw('LOWER(TRIM(municipality)) = ?', [strtolower($selectedMunicipality)])
                    ->orWhere('municipality', 'LIKE', '%'.$selectedMunicipality.'%');
            });
        }

        $barangays = $barangayQuery
            ->distinct()
            ->pluck('barangay')
            ->sort()
            ->values();

        $baseCountQuery = DuplicateFlag::query();
        $query = DuplicateFlag::with(['beneficiary.beneficiaryPrograms.program', 'matchedBeneficiary.beneficiaryPrograms.program', 'reviewer']);

        $this->applySharedFilters($baseCountQuery, $request);
        $this->applySharedFilters($query, $request);

        // Distinct flag counts dynamically updated by active filters
        $counts = [
            'all' => (clone $baseCountQuery)->count(),
            'first_name' => (clone $baseCountQuery)->where(function ($q) {
                $q->where('match_type', 'first_name')
                    ->orWhere('matched_fields', 'like', '%first_name%')
                    ->orWhere('matched_fields', 'like', '%First Name%');
            })->count(),
            'surname' => (clone $baseCountQuery)->where(function ($q) {
                $q->whereIn('match_type', ['surname', 'last_name'])
                    ->orWhere('matched_fields', 'like', '%last_name%')
                    ->orWhere('matched_fields', 'like', '%surname%')
                    ->orWhere('matched_fields', 'like', '%Surname%')
                    ->orWhere('matched_fields', 'like', '%Last Name%');
            })->count(),
            'address' => (clone $baseCountQuery)->where(function ($q) {
                $q->where('household_match_flag', true)
                    ->orWhereIn('match_type', ['address', 'household'])
                    ->orWhere('matched_fields', 'like', '%address%')
                    ->orWhere('matched_fields', 'like', '%purok%')
                    ->orWhere('matched_fields', 'like', '%household%');
            })->count(),
            'resolved' => (clone $baseCountQuery)->whereIn('status', ['resolved', 'resolved_not_duplicate', 'overridden'])->count(),
            'removed' => (clone $baseCountQuery)->where('status', 'resolved_duplicate')->count(),
        ];

        $filter = $this->resolveActiveTabFilter($request);
        $this->applyTabAndSearchFilters($query, $request, $filter);

        // Count pending flags matching the active filter across all pages (total + per DOLE score band)
        $pendingBaseQuery = (clone $query)->where('status', 'pending');
        $pendingMatchingCount = (clone $pendingBaseQuery)->count();
        $pendingBandCounts = [
            'exact' => (clone $pendingBaseQuery)->where('match_score', '>=', 100)->count(),
            'high' => (clone $pendingBaseQuery)->whereBetween('match_score', [85, 99])->count(),
            'moderate' => (clone $pendingBaseQuery)->whereBetween('match_score', [70, 84])->count(),
            'low' => (clone $pendingBaseQuery)->where('match_score', '<', 70)->count(),
        ];

        if (! in_array($filter, ['first_name', 'surname', 'address', 'resolved', 'removed'], true) && ! $request->filled('status')) {
            $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END");
        }

        $flags = $query->latest()->paginate(15)->withQueryString();

        $pagePendingIds = $flags->getCollection()
            ->where('status', 'pending')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $pageLowRiskIds = $flags->getCollection()
            ->filter(fn ($f) => $f->status === 'pending' && (int) $f->match_score < 70)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $pageExactIds = $flags->getCollection()
            ->filter(fn ($f) => $f->status === 'pending' && (int) $f->match_score >= 100)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $pageHighRiskIds = $flags->getCollection()
            ->filter(fn ($f) => $f->status === 'pending' && (int) $f->match_score >= 85 && (int) $f->match_score < 100)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $pageModerateIds = $flags->getCollection()
            ->filter(fn ($f) => $f->status === 'pending' && (int) $f->match_score >= 70 && (int) $f->match_score < 85)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return view('duplicates.index', compact(
            'flags',
            'counts',
            'filter',
            'municipalities',
            'barangays',
            'pendingMatchingCount',
            'pendingBandCounts',
            'pagePendingIds',
            'pageLowRiskIds',
            'pageModerateIds',
            'pageHighRiskIds',
            'pageExactIds',
        ));
    }

    public function resolve(Request $request, DuplicateFlag $flag): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:resolved_duplicate,resolved_not_duplicate,overridden'],
            'remarks' => ['required', 'string', 'max:500'],
        ]);

        $decision = $request->input('status');
        $remarks = $request->input('remarks');
        $candidateName = $flag->beneficiary?->full_name ?? ($flag->matched_fields['rejected_applicant_name'] ?? 'Candidate');

        $this->applyResolution($flag, $decision, $remarks);

        if ($decision === 'resolved_duplicate') {
            return redirect()->route('duplicates.index')->with('success', "Duplicate applicant {$candidateName} rejected and removed from registered beneficiaries.");
        }

        return redirect()->route('duplicates.index')->with('success', "Duplicate flag for {$candidateName} resolved as {$decision}. Record approved.");
    }

    /**
     * Return a lightweight JSON preview of pending beneficiary names affected by a bulk selection.
     */
    public function bulkPreview(Request $request): JsonResponse
    {
        $selectAllMatching = $request->boolean('select_all_matching');

        $query = DuplicateFlag::with([
            'beneficiary:id,full_name,first_name,last_name',
            'beneficiary.beneficiaryPrograms.program:id,code',
            'matchedBeneficiary:id,full_name,first_name,last_name',
            'matchedBeneficiary.beneficiaryPrograms.program:id,code',
        ])->where('status', 'pending');

        if ($selectAllMatching) {
            $this->applySharedFilters($query, $request);
            $filter = $this->resolveActiveTabFilter($request);
            $this->applyTabAndSearchFilters($query, $request, $filter);
        } else {
            $ids = array_values(array_unique(array_map('intval', (array) $request->input('flag_ids', []))));
            if (empty($ids)) {
                return response()->json(['total' => 0, 'showing' => 0, 'items' => []]);
            }
            $query->whereIn('id', $ids);
        }

        $total = (clone $query)->count();
        $items = $query->latest()
            ->limit(200)
            ->get()
            ->map(function (DuplicateFlag $flag) {
                $programs = $flag->beneficiary?->beneficiaryPrograms
                    ?->map(fn ($bp) => $bp->program?->code)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all() ?? [];
                if (empty($programs)) {
                    $programs = $flag->matchedBeneficiary?->beneficiaryPrograms
                        ?->map(fn ($bp) => $bp->program?->code)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all() ?? [];
                }

                return [
                    'id' => $flag->id,
                    'candidate' => $flag->beneficiary?->full_name ?? ($flag->matched_fields['rejected_applicant_name'] ?? 'Candidate'),
                    'matched' => $flag->matchedBeneficiary?->full_name ?? 'Existing Record',
                    'programs' => implode(', ', $programs) ?: '—',
                    'score' => (int) $flag->match_score,
                ];
            })
            ->values();

        return response()->json([
            'total' => $total,
            'showing' => $items->count(),
            'items' => $items,
        ]);
    }

    /**
     * Bulk resolve multiple pending duplicate flags (selected on page or all matching active filters).
     */
    public function bulkResolve(Request $request): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:resolved_duplicate,resolved_not_duplicate,overridden'],
            'remarks' => ['required', 'string', 'max:500'],
            'select_all_matching' => ['nullable', 'boolean'],
            'flag_ids' => ['required_without:select_all_matching', 'array'],
            'flag_ids.*' => ['integer', 'exists:duplicate_flags,id'],
            'confirm_bulk' => ['nullable', 'boolean'],
        ]);

        $decision = (string) $request->input('status');
        $remarks = trim((string) $request->input('remarks'));
        $selectAllMatching = $request->boolean('select_all_matching');

        if (($selectAllMatching || $decision === 'resolved_duplicate') && ! $request->boolean('confirm_bulk')) {
            return back()->with('error', 'Please check the confirmation box before applying this bulk resolution.');
        }

        $query = DuplicateFlag::with(['beneficiary', 'matchedBeneficiary'])
            ->where('status', 'pending');

        if ($selectAllMatching) {
            $this->applySharedFilters($query, $request);
            $filter = $this->resolveActiveTabFilter($request);
            $this->applyTabAndSearchFilters($query, $request, $filter);
        } else {
            $ids = array_values(array_unique(array_map('intval', $request->input('flag_ids', []))));
            $query->whereIn('id', $ids);
        }

        $flagsToResolve = $query->get();

        if ($flagsToResolve->isEmpty()) {
            return redirect()->route('duplicates.index', $this->preserveFilterQuery($request))
                ->with('warning', 'No pending duplicate flags matched your selection.');
        }

        $resolvedCount = 0;
        DB::transaction(function () use ($flagsToResolve, $decision, $remarks, &$resolvedCount) {
            foreach ($flagsToResolve as $flag) {
                if ($flag->status !== 'pending') {
                    continue;
                }
                $this->applyResolution($flag, $decision, $remarks);
                $resolvedCount++;
            }
        });

        $label = match ($decision) {
            'resolved_duplicate' => 'Confirmed Duplicate (Removed)',
            'overridden' => 'Approved (Override)',
            default => 'Not a Duplicate (Verified)',
        };

        return redirect()->route('duplicates.index', $this->preserveFilterQuery($request))
            ->with('success', "Bulk resolved {$resolvedCount} pending duplicate flag(s) as {$label}.");
    }

    /**
     * Apply the exact resolution state change + AuditLog for a single DuplicateFlag.
     */
    protected function applyResolution(DuplicateFlag $flag, string $decision, string $remarks): void
    {
        $candidate = $flag->beneficiary;
        $matched = $flag->matchedBeneficiary;
        $candidateName = $candidate?->full_name ?? ($flag->matched_fields['rejected_applicant_name'] ?? 'Candidate');
        $matchedName = $matched?->full_name ?? 'Matched Beneficiary';

        if ($decision === 'resolved_duplicate') {
            $mf = $flag->matched_fields ?? [];
            $mf['rejected_applicant_name'] = $candidateName;
            $mf['rejected_applicant_dob'] = $candidate?->date_of_birth?->format('M d, Y');
            $mf['rejected_applicant_address'] = $candidate?->address;
            $mf['rejected_applicant_gov_id'] = $candidate?->government_id_number;
            $mf['rejection_reason'] = $remarks;

            $flag->update([
                'status' => 'resolved_duplicate',
                'remarks' => $remarks,
                'reviewed_by' => auth()->id(),
                'matched_fields' => $mf,
            ]);

            if ($candidate) {
                $candidate->delete();
            }

            AuditLog::log([
                'action' => 'reject_duplicate_beneficiary',
                'model_type' => DuplicateFlag::class,
                'model_id' => $flag->id,
                'description' => "Rejected duplicate registration for {$candidateName} (matched with existing beneficiary {$matchedName}). Duplicate applicant removed from database.",
            ]);

            return;
        }

        $flag->update([
            'status' => $decision,
            'remarks' => $remarks,
            'reviewed_by' => auth()->id(),
        ]);

        if ($candidate) {
            BeneficiaryProgram::where('beneficiary_id', $candidate->id)
                ->where('status', 'pending')
                ->update(['status' => 'approved']);
        }

        AuditLog::log([
            'action' => $flag->household_match_flag ? 'override_household_duplicate' : 'duplicate_override',
            'model_type' => DuplicateFlag::class,
            'model_id' => $flag->id,
            'description' => "Approved {$candidateName} despite duplicate flag (matched with {$matchedName}) as {$decision}. Remarks: {$remarks}",
        ]);
    }

    /**
     * Apply shared top-level filters (program, municipality, barangay, sex, risk_level).
     *
     * @param  Builder<DuplicateFlag>  $query
     */
    protected function applySharedFilters(Builder $query, Request $request): void
    {
        $programUpper = $request->filled('program') ? strtoupper(trim((string) $request->input('program'))) : null;
        if ($programUpper === 'ALL') {
            $programUpper = null;
        }

        if ($programUpper) {
            $query->where(function ($sub) use ($programUpper) {
                $sub->whereHas('beneficiary.beneficiaryPrograms.program', function ($pq) use ($programUpper) {
                    $pq->where('code', $programUpper);
                })->orWhereHas('matchedBeneficiary.beneficiaryPrograms.program', function ($mq) use ($programUpper) {
                    $mq->where('code', $programUpper);
                });
            });
        }

        if ($municipality = trim((string) $request->input('municipality'))) {
            $query->where(function ($sub) use ($municipality) {
                $sub->whereHas('beneficiary', function ($bq) use ($municipality) {
                    $bq->where('municipality', $municipality)
                        ->orWhereRaw('LOWER(TRIM(municipality)) = ?', [strtolower($municipality)])
                        ->orWhere('municipality', 'LIKE', '%'.$municipality.'%');
                })->orWhereHas('matchedBeneficiary', function ($mq) use ($municipality) {
                    $mq->where('municipality', $municipality)
                        ->orWhereRaw('LOWER(TRIM(municipality)) = ?', [strtolower($municipality)])
                        ->orWhere('municipality', 'LIKE', '%'.$municipality.'%');
                });
            });
        }

        if ($barangay = trim((string) $request->input('barangay'))) {
            $query->where(function ($sub) use ($barangay) {
                $sub->whereHas('beneficiary', function ($bq) use ($barangay) {
                    $bq->where('barangay', $barangay)
                        ->orWhereRaw('LOWER(TRIM(barangay)) = ?', [strtolower($barangay)]);
                })->orWhereHas('matchedBeneficiary', function ($mq) use ($barangay) {
                    $mq->where('barangay', $barangay)
                        ->orWhereRaw('LOWER(TRIM(barangay)) = ?', [strtolower($barangay)]);
                });
            });
        }

        if ($sex = $request->input('sex')) {
            $formattedSex = ucfirst(strtolower((string) $sex));
            if (in_array($formattedSex, ['Male', 'Female'], true)) {
                $query->where(function ($sub) use ($formattedSex) {
                    $sub->whereHas('beneficiary', fn ($bq) => $bq->where('sex', $formattedSex))
                        ->orWhereHas('matchedBeneficiary', fn ($mq) => $mq->where('sex', $formattedSex));
                });
            }
        }

        if ($riskLevel = $request->input('risk_level')) {
            if ($riskLevel === 'exact' || $riskLevel === 'exact_match') {
                $query->where('match_score', '>=', 100);
            } elseif ($riskLevel === 'high' || $riskLevel === 'high_risk') {
                $query->whereBetween('match_score', [85, 99]);
            } elseif ($riskLevel === 'moderate' || $riskLevel === 'moderate_risk') {
                $query->whereBetween('match_score', [70, 84]);
            } elseif ($riskLevel === 'low' || $riskLevel === 'low_risk') {
                $query->where('match_score', '<', 70);
            }
        }
    }

    protected function resolveActiveTabFilter(Request $request): string
    {
        $filter = $request->input('filter');
        if ($filter) {
            return (string) $filter;
        }
        if ($request->input('status') === 'resolved_duplicate') {
            return 'removed';
        }
        if ($request->input('status') === 'resolved' || $request->input('status') === 'overridden') {
            return 'resolved';
        }
        if ($request->boolean('household')) {
            return 'address';
        }
        if ($request->filled('match_type')) {
            return (string) $request->input('match_type');
        }

        return 'all';
    }

    /**
     * Apply tab pill filters, match_type, and search query.
     *
     * @param  Builder<DuplicateFlag>  $query
     */
    protected function applyTabAndSearchFilters(Builder $query, Request $request, string $filter): void
    {
        if ($filter === 'first_name') {
            $query->where(function ($q) {
                $q->where('match_type', 'first_name')
                    ->orWhere('matched_fields', 'like', '%first_name%')
                    ->orWhere('matched_fields', 'like', '%First Name%');
            });
        } elseif ($filter === 'surname') {
            $query->where(function ($q) {
                $q->whereIn('match_type', ['surname', 'last_name'])
                    ->orWhere('matched_fields', 'like', '%last_name%')
                    ->orWhere('matched_fields', 'like', '%surname%')
                    ->orWhere('matched_fields', 'like', '%Surname%')
                    ->orWhere('matched_fields', 'like', '%Last Name%');
            });
        } elseif ($filter === 'address') {
            $query->where(function ($q) {
                $q->where('household_match_flag', true)
                    ->orWhereIn('match_type', ['address', 'household'])
                    ->orWhere('matched_fields', 'like', '%address%')
                    ->orWhere('matched_fields', 'like', '%purok%')
                    ->orWhere('matched_fields', 'like', '%household%');
            });
        } elseif ($filter === 'resolved') {
            $query->whereIn('status', ['resolved', 'resolved_not_duplicate', 'overridden']);
        } elseif ($filter === 'removed') {
            $query->where('status', 'resolved_duplicate');
        } elseif ($status = $request->input('filter_status', $request->input('status'))) {
            if (! in_array($status, ['resolved_duplicate', 'resolved_not_duplicate', 'overridden'], true)) {
                $query->where('status', $status);
            }
        }

        if ($matchType = $request->input('match_type')) {
            if (! in_array($filter, ['first_name', 'surname', 'address'], true)) {
                $query->where('match_type', $matchType);
            }
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('beneficiary', function ($bq) use ($search) {
                    $bq->where('full_name', 'like', "%{$search}%");
                })->orWhereHas('matchedBeneficiary', function ($mq) use ($search) {
                    $mq->where('full_name', 'like', "%{$search}%");
                });
            });
        }
    }

    /**
     * Preserve active filter query parameters after bulk actions.
     *
     * @return array<string, mixed>
     */
    protected function preserveFilterQuery(Request $request): array
    {
        return array_filter([
            'program' => $request->input('program'),
            'municipality' => $request->input('municipality'),
            'barangay' => $request->input('barangay'),
            'sex' => $request->input('sex'),
            'risk_level' => $request->input('risk_level'),
            'filter' => $request->input('filter'),
            'search' => $request->input('search'),
        ], fn ($v) => filled($v));
    }
}
