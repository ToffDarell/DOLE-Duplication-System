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
            ->toArray();

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

        // 4. Geographic Distribution (Top Municipalities Horizontal Bar Chart)
        $geographicStats = Beneficiary::selectRaw('municipality, COUNT(*) as total')
            ->whereNotNull('municipality')
            ->where('municipality', '!=', '')
            ->groupBy('municipality')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'municipality')
            ->toArray();

        if (empty($geographicStats)) {
            $geographicStats = [
                'Malaybalay City' => 0,
                'Valencia City' => 0,
                'Manolo Fortich' => 0,
                'Maramag' => 0,
                'Quezon' => 0,
                'Don Carlos' => 0,
                'Lantapan' => 0,
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
            'recentBeneficiaries',
            'recentDuplicates'
        ));
    }
}
