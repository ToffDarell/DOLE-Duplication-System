<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\DuplicateFlag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $barangays = [];
        if ($selectedMunicipality) {
            $barangays = Beneficiary::where(function ($q) use ($selectedMunicipality) {
                $q->where('municipality', $selectedMunicipality)
                    ->orWhereRaw('LOWER(TRIM(municipality)) = ?', [strtolower($selectedMunicipality)])
                    ->orWhere('municipality', 'LIKE', '%'.$selectedMunicipality.'%');
            })
                ->distinct()
                ->whereNotNull('barangay')
                ->where('barangay', '!=', '')
                ->pluck('barangay')
                ->sort()
                ->values();
        }

        $programUpper = $request->filled('program') ? strtoupper(trim($request->input('program'))) : null;
        if ($programUpper === 'ALL') {
            $programUpper = null;
        }

        // Base query for counts with program scope applied
        $baseCountQuery = DuplicateFlag::query();
        $query = DuplicateFlag::with(['beneficiary.beneficiaryPrograms.program', 'matchedBeneficiary.beneficiaryPrograms.program', 'reviewer']);

        if ($programUpper) {
            $filterProg = function ($sub) use ($programUpper) {
                $sub->whereHas('beneficiary.beneficiaryPrograms.program', function ($pq) use ($programUpper) {
                    $pq->where('code', $programUpper);
                })->orWhereHas('matchedBeneficiary.beneficiaryPrograms.program', function ($mq) use ($programUpper) {
                    $mq->where('code', $programUpper);
                });
            };
            $baseCountQuery->where($filterProg);
            $query->where($filterProg);
        }

        // Municipality Filter
        if ($municipality = trim((string) $request->input('municipality'))) {
            $filterMuni = function ($q) use ($municipality) {
                $q->where(function ($sub) use ($municipality) {
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
            };
            $baseCountQuery->where($filterMuni);
            $query->where($filterMuni);
        }

        // Barangay Filter
        if ($barangay = trim((string) $request->input('barangay'))) {
            $filterBrgy = function ($q) use ($barangay) {
                $q->where(function ($sub) use ($barangay) {
                    $sub->whereHas('beneficiary', function ($bq) use ($barangay) {
                        $bq->where('barangay', $barangay)
                            ->orWhereRaw('LOWER(TRIM(barangay)) = ?', [strtolower($barangay)]);
                    })->orWhereHas('matchedBeneficiary', function ($mq) use ($barangay) {
                        $mq->where('barangay', $barangay)
                            ->orWhereRaw('LOWER(TRIM(barangay)) = ?', [strtolower($barangay)]);
                    });
                });
            };
            $baseCountQuery->where($filterBrgy);
            $query->where($filterBrgy);
        }

        // Sex Filter
        if ($sex = $request->input('sex')) {
            $formattedSex = ucfirst(strtolower($sex));
            if (in_array($formattedSex, ['Male', 'Female'])) {
                $filterSex = function ($q) use ($formattedSex) {
                    $q->where(function ($sub) use ($formattedSex) {
                        $sub->whereHas('beneficiary', fn ($bq) => $bq->where('sex', $formattedSex))
                            ->orWhereHas('matchedBeneficiary', fn ($mq) => $mq->where('sex', $formattedSex));
                    });
                };
                $baseCountQuery->where($filterSex);
                $query->where($filterSex);
            }
        }

        // Risk Level / Match Percentage Filter
        if ($riskLevel = $request->input('risk_level')) {
            $filterRisk = function ($q) use ($riskLevel) {
                if ($riskLevel === 'exact' || $riskLevel === 'exact_match') {
                    $q->where('match_score', '>=', 100);
                } elseif ($riskLevel === 'high' || $riskLevel === 'high_risk') {
                    $q->whereBetween('match_score', [85, 99]);
                } elseif ($riskLevel === 'moderate' || $riskLevel === 'moderate_risk') {
                    $q->whereBetween('match_score', [70, 84]);
                } elseif ($riskLevel === 'low' || $riskLevel === 'low_risk') {
                    $q->where('match_score', '<', 70);
                }
            };
            $baseCountQuery->where($filterRisk);
            $query->where($filterRisk);
        }

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

        // Determine current active filter tab
        $filter = $request->input('filter');
        if (! $filter) {
            if ($request->input('status') === 'resolved_duplicate') {
                $filter = 'removed';
            } elseif ($request->input('status') === 'resolved' || $request->input('status') === 'overridden') {
                $filter = 'resolved';
            } elseif ($request->boolean('household')) {
                $filter = 'address';
            } elseif ($request->filled('match_type')) {
                $filter = $request->input('match_type');
            } else {
                $filter = 'all';
            }
        }

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
        } elseif ($status = $request->input('status')) {
            if (in_array($status, ['overridden', 'resolved'])) {
                $query->whereIn('status', ['overridden', 'resolved_not_duplicate', 'resolved_duplicate']);
            } else {
                $query->where('status', $status);
            }
        } else {
            // Default: show pending flags first
            $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END");
        }

        if ($matchType = $request->input('match_type')) {
            if (! in_array($filter, ['first_name', 'surname', 'address'])) {
                $query->where('match_type', $matchType);
            }
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('beneficiary', function ($bq) use ($search) {
                    $bq->where('full_name', 'like', "%{$search}%");
                })->orWhereHas('matchedBeneficiary', function ($mq) use ($search) {
                    $mq->where('full_name', 'like', "%{$search}%");
                });
            });
        }

        $flags = $query->latest()->paginate(15)->withQueryString();

        return view('duplicates.index', compact('flags', 'counts', 'filter', 'municipalities', 'barangays'));
    }

    public function resolve(Request $request, DuplicateFlag $flag): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:resolved_duplicate,resolved_not_duplicate,overridden'],
            'remarks' => ['required', 'string', 'max:500'],
        ]);

        $decision = $request->input('status');
        $remarks = $request->input('remarks');

        $candidate = $flag->beneficiary;
        $matched = $flag->matchedBeneficiary;
        $candidateName = $candidate?->full_name ?? ($flag->matched_fields['rejected_applicant_name'] ?? 'Candidate');
        $matchedName = $matched?->full_name ?? 'Matched Beneficiary';

        if ($decision === 'resolved_duplicate') {
            // Store candidate snapshot in matched_fields so the flag retains full info in audit history
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

            // Completely delete the duplicate candidate so they are NOT recorded in beneficiaries
            if ($candidate) {
                $candidate->delete();
            }

            AuditLog::log([
                'action' => 'reject_duplicate_beneficiary',
                'model_type' => DuplicateFlag::class,
                'model_id' => $flag->id,
                'description' => "Rejected duplicate registration for {$candidateName} (matched with existing beneficiary {$matchedName}). Duplicate applicant removed from database.",
            ]);

            return redirect()->route('duplicates.index')->with('success', "Duplicate applicant {$candidateName} rejected and removed from registered beneficiaries.");
        }

        // For approved decisions (resolved_not_duplicate or overridden)
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

        return redirect()->route('duplicates.index')->with('success', "Beneficiary {$candidateName} verified and approved.");
    }
}
