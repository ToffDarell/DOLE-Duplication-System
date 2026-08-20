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
        $totalBeneficiaries = Beneficiary::count();
        $totalDuplicates = DuplicateFlag::count();
        $pendingDuplicates = DuplicateFlag::where('status', 'pending')->count();
        $resolvedDuplicates = DuplicateFlag::whereIn('status', ['resolved_duplicate', 'resolved_not_duplicate', 'overridden'])->count();

        $programStats = BeneficiaryProgram::selectRaw('programs.code, COUNT(*) as total')
            ->join('programs', 'programs.id', '=', 'beneficiary_programs.program_id')
            ->groupBy('programs.code')
            ->pluck('total', 'code');

        $recentBeneficiaries = Beneficiary::with('creator')
            ->latest()
            ->take(10)
            ->get();

        $recentDuplicates = DuplicateFlag::with(['beneficiary', 'matchedBeneficiary'])
            ->where('status', 'pending')
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard', compact(
            'totalBeneficiaries',
            'totalDuplicates',
            'pendingDuplicates',
            'resolvedDuplicates',
            'programStats',
            'recentBeneficiaries',
            'recentDuplicates',
        ));
    }
}
