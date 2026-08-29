<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\DuplicateFlag;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $currentYear = (int) date('Y');

        // Top Summary Cards Metrics
        $totalBeneficiaries = Beneficiary::count();
        $totalDuplicates = DuplicateFlag::count();
        $pendingDuplicates = DuplicateFlag::where('status', 'pending')->count();
        $overriddenCount = DuplicateFlag::whereIn('status', ['overridden', 'resolved_not_duplicate', 'resolved_duplicate'])->count();
        $resolvedDuplicates = $overriddenCount;

        // 1. Duplicate Status Breakdown (Pie / Donut Chart)
        $cleanRecords = Beneficiary::whereDoesntHave('duplicateFlags')
            ->whereDoesntHave('matchedDuplicateFlags')
            ->count();

        $highConfidencePending = DuplicateFlag::where('match_score', '>=', 75)
            ->where('status', 'pending')
            ->count();

        $mediumConfidencePending = DuplicateFlag::where('match_score', '<', 75)
            ->where('status', 'pending')
            ->count();

        $duplicateStatusBreakdown = [
            'Clean Records (Passed)' => $cleanRecords,
            'High-Confidence Duplicates (Blocked)' => $highConfidencePending,
            'Medium-Confidence Warnings (Pending Validation)' => $mediumConfidencePending,
            'Resolved / Overridden Records' => $resolvedDuplicates,
        ];

        // 2. Program Distribution (Pie / Donut Chart)
        $rawProgramStats = BeneficiaryProgram::selectRaw('programs.code, COUNT(*) as total')
            ->join('programs', 'programs.id', '=', 'beneficiary_programs.program_id')
            ->groupBy('programs.code')
            ->pluck('total', 'code')
            ->map(fn ($total) => (int) $total)
            ->toArray();

        // Explicit ordered map keyed by program code — the frontend colours each
        // slice by this key, so every program renders in its own colour.
        $programDistribution = [
            'TUPAD' => $rawProgramStats['TUPAD'] ?? 0,
            'SPES' => $rawProgramStats['SPES'] ?? 0,
            'DILP' => $rawProgramStats['DILP'] ?? 0,
            'GIP' => $rawProgramStats['GIP'] ?? 0,
        ];

        // 3. Registration & Duplicate Trends (Monthly Combined Bar/Line Graph)
        $monthlyRegistrations = array_fill(1, 12, 0);
        Beneficiary::whereYear('created_at', $currentYear)
            ->select('created_at')
            ->get()
            ->groupBy(fn ($b) => (int) $b->created_at->format('n'))
            ->each(function ($group, $month) use (&$monthlyRegistrations) {
                $monthlyRegistrations[$month] = $group->count();
            });

        $monthlyDuplicates = array_fill(1, 12, 0);
        DuplicateFlag::whereYear('created_at', $currentYear)
            ->select('created_at')
            ->get()
            ->groupBy(fn ($flag) => (int) $flag->created_at->format('n'))
            ->each(function ($group, $month) use (&$monthlyDuplicates) {
                $monthlyDuplicates[$month] = $group->count();
            });

        $trendMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $trendRegistrations = array_values($monthlyRegistrations);
        $trendDuplicates = array_values($monthlyDuplicates);

        // 4. Municipal Breakdown Stats
        $standardMunicipalities = Beneficiary::BUKIDNON_MUNICIPALITIES;

        // Group with UPPER(TRIM()) at the database so mixed-case / padded rows
        // aggregate into one bucket, then re-key onto canonical Bukidnon
        // spellings for display and to merge with $standardMunicipalities.
        $rawMunicipalBeneficiaries = $this->canonicaliseMunicipalityCounts(
            Beneficiary::selectRaw('UPPER(TRIM(municipality)) as municipality_name, COUNT(*) as total')
                ->whereNotNull('municipality')
                ->where('municipality', '!=', '')
                ->groupBy('municipality_name')
                ->pluck('total', 'municipality_name')
                ->toArray()
        );

        $rawMunicipalDuplicates = $this->canonicaliseMunicipalityCounts(
            DuplicateFlag::join('beneficiaries', 'beneficiaries.id', '=', 'duplicate_flags.beneficiary_id')
                ->whereNotNull('beneficiaries.municipality')
                ->where('beneficiaries.municipality', '!=', '')
                ->selectRaw('UPPER(TRIM(beneficiaries.municipality)) as municipality_name, COUNT(*) as total')
                ->groupBy('municipality_name')
                ->pluck('total', 'municipality_name')
                ->toArray()
        );

        // Build sorted associative arrays and chart arrays
        $allMunicipalities = array_unique(array_merge($standardMunicipalities, array_keys($rawMunicipalBeneficiaries), array_keys($rawMunicipalDuplicates)));
        sort($allMunicipalities);

        $municipalLabels = [];
        $municipalBeneficiaryCounts = [];
        $municipalDuplicateCounts = [];

        foreach ($allMunicipalities as $muni) {
            $municipalLabels[] = $muni;
            $municipalBeneficiaryCounts[] = $rawMunicipalBeneficiaries[$muni] ?? 0;
            $municipalDuplicateCounts[] = $rawMunicipalDuplicates[$muni] ?? 0;
        }

        // "Duplicates by Municipality" donut: drop empty municipalities, rank by
        // volume, keep the leading slices and fold the long tail into "Others" so
        // the legend stays readable regardless of how many LGUs have flags.
        $duplicateSliceLimit = 6;
        $rankedDuplicateMunicipalities = collect($rawMunicipalDuplicates)
            ->map(fn ($count) => (int) $count)
            ->filter(fn ($count) => $count > 0)
            ->sortDesc();

        $municipalDuplicateDistribution = $rankedDuplicateMunicipalities
            ->take($duplicateSliceLimit)
            ->toArray();

        $othersDuplicateTotal = $rankedDuplicateMunicipalities
            ->slice($duplicateSliceLimit)
            ->sum();

        if ($othersDuplicateTotal > 0) {
            $municipalDuplicateDistribution['Others'] = $othersDuplicateTotal;
        }

        // Top 8 municipalities for compact bar chart — canonicalise, then rank
        $geographicStats = $this->canonicaliseMunicipalityCounts(
            Beneficiary::selectRaw('UPPER(TRIM(municipality)) as municipality_name, COUNT(*) as total')
                ->whereNotNull('municipality')
                ->where('municipality', '!=', '')
                ->groupBy('municipality_name')
                ->pluck('total', 'municipality_name')
                ->toArray()
        );
        arsort($geographicStats);
        $geographicStats = array_slice($geographicStats, 0, 8, true);

        if (empty($geographicStats)) {
            $geographicStats = [
                'Malaybalay City' => 0,
                'Valencia City' => 0,
                'Manolo Fortich' => 0,
                'Maramag' => 0,
                'Quezon' => 0,
                'Don Carlos' => 0,
                'Lantapan' => 0,
                'Baungon' => 0,
            ];
        }

        // Recent Lists for Activity Feeds
        $recentBeneficiaries = Beneficiary::with('creator')
            ->latest()
            ->take(8)
            ->get();

        $recentDuplicates = DuplicateFlag::with(['beneficiary', 'matchedBeneficiary'])
            ->where('status', 'pending')
            ->latest()
            ->take(6)
            ->get();

        return view('dashboard', compact(
            'totalBeneficiaries',
            'totalDuplicates',
            'pendingDuplicates',
            'overriddenCount',
            'resolvedDuplicates',
            'duplicateStatusBreakdown',
            'programDistribution',
            'trendMonths',
            'trendRegistrations',
            'trendDuplicates',
            'geographicStats',
            'municipalLabels',
            'municipalBeneficiaryCounts',
            'municipalDuplicateCounts',
            'municipalDuplicateDistribution',
            'recentBeneficiaries',
            'recentDuplicates'
        ));
    }

    /**
     * Collapse a raw "municipality => count" map onto canonical Bukidnon names,
     * summing any buckets that merge together (e.g. "VALENCIA CITY" +
     * "Valencia City") and dropping blanks.
     *
     * @param  array<string, int|string>  $rawCounts
     * @return array<string, int>
     */
    private function canonicaliseMunicipalityCounts(array $rawCounts): array
    {
        $merged = [];

        foreach ($rawCounts as $name => $count) {
            $canonical = Beneficiary::canonicalMunicipality((string) $name);

            if ($canonical === null) {
                continue;
            }

            $merged[$canonical] = ($merged[$canonical] ?? 0) + (int) $count;
        }

        ksort($merged);

        return $merged;
    }
}
