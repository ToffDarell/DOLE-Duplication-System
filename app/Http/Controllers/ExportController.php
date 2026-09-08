<?php

namespace App\Http\Controllers;

use App\Exports\BeneficiariesExport;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Program;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Interactive Report Generator & Export Center (Panelist: Mark Ian Mukara).
     */
    public function index(Request $request): View
    {
        $programs = Program::all();
        $municipalities = Beneficiary::distinct()->whereNotNull('municipality')->where('municipality', '!=', '')->pluck('municipality')->sort()->values();

        // Selected municipality's barangays for dependent dropdown
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

        // Live preview of filtered query (limited for snappy UI rendering)
        $query = $this->getFilteredQuery($request);
        $totalCount = (clone $query)->count();
        $previewBeneficiaries = (clone $query)->take(25)->get();

        // Summary metrics for the filtered set
        $maleCount = (clone $query)->where('sex', 'Male')->count();
        $femaleCount = (clone $query)->where('sex', 'Female')->count();
        $exactDuplicateCount = (clone $query)->whereHas('duplicateFlags', fn ($q) => $q->where('match_score', '>=', 100))->count();
        $highRiskCount = (clone $query)->whereHas('duplicateFlags', fn ($q) => $q->whereBetween('match_score', [85, 99]))->count();

        return view('exports.index', compact(
            'programs',
            'municipalities',
            'barangays',
            'totalCount',
            'previewBeneficiaries',
            'maleCount',
            'femaleCount',
            'exactDuplicateCount',
            'highRiskCount'
        ));
    }

    /**
     * Dependent dynamic dropdown API for barangays based on municipality.
     */
    public function getBarangaysByMunicipality(Request $request): JsonResponse
    {
        $municipality = trim((string) $request->input('municipality'));
        if (! $municipality) {
            return response()->json([]);
        }

        $barangays = Beneficiary::where(function ($q) use ($municipality) {
            $q->where('municipality', $municipality)
                ->orWhereRaw('LOWER(TRIM(municipality)) = ?', [strtolower($municipality)])
                ->orWhere('municipality', 'LIKE', '%'.$municipality.'%');
        })
            ->distinct()
            ->whereNotNull('barangay')
            ->where('barangay', '!=', '')
            ->pluck('barangay')
            ->sort()
            ->values();

        return response()->json($barangays);
    }

    /**
     * Export filtered beneficiaries to Excel (.xlsx) with Maatwebsite Excel (Panelist: Glaiza Mae Libe).
     */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        $query = $this->getFilteredQuery($request);
        $count = (clone $query)->count();

        AuditLog::log([
            'action' => 'export_excel',
            'description' => "Exported {$count} beneficiaries to Excel (.xlsx)",
        ]);

        $fileName = 'dole_beneficiaries_export_'.date('Y-m-d_His').'.xlsx';

        return Excel::download(new BeneficiariesExport($query), $fileName);
    }

    /**
     * Export filtered beneficiaries to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $beneficiaries = $this->getFilteredQuery($request)->get();

        AuditLog::log([
            'action' => 'export_csv',
            'description' => 'Exported '.$beneficiaries->count().' beneficiaries to CSV',
        ]);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="dole_beneficiaries_export_'.date('Y-m-d_His').'.csv"',
        ];

        $callback = function () use ($beneficiaries) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID', 'Full Name', 'Date of Birth', 'Age', 'Sex', 'Civil Status',
                'Municipality', 'Barangay', 'House Number / Address', 'Gov ID Type', 'Gov ID Number',
                'Contact Number', 'Program(s)', 'Duplication Risk Status',
            ]);

            foreach ($beneficiaries as $b) {
                $programs = $b->beneficiaryPrograms->map(fn ($bp) => ($bp->program?->code ?? 'N/A').' ('.$bp->availment_year.')')->implode(', ');
                $maxScore = $b->duplicateFlags->max('match_score') ?? 0;
                $riskStatus = $maxScore >= 100 ? 'Exact Duplicate (100%)' : ($maxScore >= 85 ? "High Risk ({$maxScore}%)" : ($maxScore >= 70 ? "Moderate Risk ({$maxScore}%)" : ($maxScore > 0 ? "Low Risk ({$maxScore}%)" : 'Clean Record')));

                fputcsv($file, [
                    $b->id,
                    $b->full_name,
                    $b->date_of_birth ? $b->date_of_birth->format('Y-m-d') : '',
                    $b->age,
                    $b->sex,
                    $b->civil_status,
                    $b->municipality,
                    $b->barangay,
                    $b->address,
                    $b->government_id_type,
                    $b->government_id_number,
                    $b->contact_number,
                    $programs ?: 'None',
                    $riskStatus,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export filtered beneficiaries to PDF.
     */
    public function exportPdf(Request $request)
    {
        // Increase memory and execution limits for DomPDF rendering
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        // Limit query to 250 records for optimal PDF rendering performance
        $beneficiaries = $this->getFilteredQuery($request)->take(250)->get();

        AuditLog::log([
            'action' => 'export_pdf',
            'description' => 'Exported '.$beneficiaries->count().' beneficiaries to PDF',
        ]);

        // Prepare Base64 logo for optimal DomPDF rendering without file path resolution issues
        $logoPath = public_path('logo dole.png');
        if (! file_exists($logoPath)) {
            $logoPath = public_path('dole-logo.png');
        }
        $logoSrc = file_exists($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : null;

        $pdf = Pdf::loadView('exports.beneficiaries_pdf', compact('beneficiaries', 'logoSrc'))
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'sans-serif');

        return $pdf->download('dole_beneficiaries_report_'.date('Y-m-d').'.pdf');
    }

    /**
     * Centralized Multi-Category Filter Query Builder (Panelist: Mark Ian Mukara).
     */
    protected function getFilteredQuery(Request $request)
    {
        $query = Beneficiary::with(['beneficiaryPrograms.program', 'duplicateFlags']);

        // 1. Search Query (Name, ID, Contact)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('government_id_number', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        // 2. Sex Filter (Male, Female)
        if ($sex = $request->input('sex')) {
            if (in_array(ucfirst($sex), ['Male', 'Female'])) {
                $query->where('sex', ucfirst($sex));
            }
        }

        // 3. Municipality Filter
        if ($municipality = trim((string) $request->input('municipality'))) {
            $query->where(function ($q) use ($municipality) {
                $q->where('municipality', $municipality)
                    ->orWhereRaw('LOWER(TRIM(municipality)) = ?', [strtolower($municipality)])
                    ->orWhere('municipality', 'LIKE', '%'.$municipality.'%');
            });
        }

        // 4. Barangay Filter (Dependent Dropdown)
        if ($barangay = trim((string) $request->input('barangay'))) {
            $query->where(function ($q) use ($barangay) {
                $q->where('barangay', $barangay)
                    ->orWhereRaw('LOWER(TRIM(barangay)) = ?', [strtolower($barangay)]);
            });
        }

        // 5. Age Range Filter (18-30, 31-50, 51-59, 60+ / Senior)
        if ($ageRange = $request->input('age_range')) {
            $now = Carbon::now();
            if ($ageRange === '18-30') {
                $query->whereBetween('date_of_birth', [
                    $now->copy()->subYears(30)->startOfDay(),
                    $now->copy()->subYears(18)->endOfDay(),
                ]);
            } elseif ($ageRange === '31-50') {
                $query->whereBetween('date_of_birth', [
                    $now->copy()->subYears(50)->startOfDay(),
                    $now->copy()->subYears(31)->endOfDay(),
                ]);
            } elseif ($ageRange === '51-59' || $ageRange === '51+') {
                $query->where('date_of_birth', '<=', $now->copy()->subYears(51)->endOfDay());
            } elseif ($ageRange === '60+' || $ageRange === 'senior') {
                $query->where(function ($q) use ($now) {
                    $q->where('date_of_birth', '<=', $now->copy()->subYears(60)->endOfDay())
                        ->orWhere('is_senior_citizen', true);
                });
            }
        }

        // 6. Duplication Risk Level Filter (Exact Match, High Risk, Moderate Risk, Low Risk, Clean)
        if ($riskLevel = $request->input('risk_level')) {
            if ($riskLevel === 'exact' || $riskLevel === 'exact_match') {
                $query->whereHas('duplicateFlags', function ($q) {
                    $q->where('match_score', '>=', 100);
                });
            } elseif ($riskLevel === 'high' || $riskLevel === 'high_risk') {
                $query->whereHas('duplicateFlags', function ($q) {
                    $q->whereBetween('match_score', [85, 99]);
                });
            } elseif ($riskLevel === 'moderate' || $riskLevel === 'moderate_risk') {
                $query->whereHas('duplicateFlags', function ($q) {
                    $q->whereBetween('match_score', [70, 84]);
                });
            } elseif ($riskLevel === 'low' || $riskLevel === 'low_risk') {
                $query->where(function ($q) {
                    $q->whereHas('duplicateFlags', function ($dq) {
                        $dq->where('match_score', '<', 70);
                    })->orWhereDoesntHave('duplicateFlags');
                });
            } elseif ($riskLevel === 'clean') {
                $query->whereDoesntHave('duplicateFlags')
                    ->whereDoesntHave('matchedDuplicateFlags');
            }
        }

        // 7. Program Filter
        if ($programCode = $request->input('program')) {
            $query->whereHas('beneficiaryPrograms.program', function ($q) use ($programCode) {
                $q->where('code', $programCode);
            });
        }

        // 8. Sector Filter (Senior, PWD, Student)
        if ($sector = $request->input('sector')) {
            if ($sector === 'senior') {
                $query->where('is_senior_citizen', true);
            } elseif ($sector === 'pwd') {
                $query->where('is_pwd', true);
            } elseif ($sector === 'student') {
                $query->where('is_student', true);
            }
        }

        // 9. Date Range Filter
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Alphabetical Sorting by Last Name, then First Name (Panelist: Glaiza Mae Libe)
        $query->orderBy('last_name', 'asc')->orderBy('first_name', 'asc');

        return $query;
    }
}
