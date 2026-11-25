<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParticipationHistoryController extends Controller
{
    /**
     * Privacy-safe beneficiary yearly participation lookup.
     * Exposes only Name, Program, Year, Municipality/Barangay, and Status — never sensitive PII.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $year = $request->filled('year') ? (int) $request->input('year') : null;
        $programCode = strtoupper(trim((string) $request->input('program')));
        $municipality = trim((string) $request->input('municipality'));

        $query = BeneficiaryProgram::query()
            ->select([
                'beneficiary_programs.id',
                'beneficiary_programs.beneficiary_id',
                'beneficiary_programs.program_id',
                'beneficiary_programs.project_id',
                'beneficiary_programs.availment_year',
                'beneficiary_programs.status',
            ])
            ->with([
                'beneficiary:id,full_name,first_name,middle_name,last_name,suffix,barangay,municipality',
                'program:id,code,name',
                'project:id,name',
            ])
            ->whereHas('beneficiary');

        if ($search !== '') {
            $query->whereHas('beneficiary', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%");
            });
        }

        if ($year !== null) {
            $query->where('availment_year', $year);
        }

        if ($programCode !== '') {
            $query->whereHas('program', fn ($q) => $q->where('code', $programCode));
        }

        if ($municipality !== '') {
            $query->whereHas('beneficiary', fn ($q) => $q->where('municipality', $municipality));
        }

        $records = $query
            ->orderByDesc('availment_year')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $availableYears = BeneficiaryProgram::query()
            ->distinct()
            ->orderByDesc('availment_year')
            ->pluck('availment_year')
            ->filter()
            ->values();

        $programs = Program::enabled()->orderBy('code')->get(['id', 'code', 'name']);
        $municipalities = Beneficiary::BUKIDNON_MUNICIPALITIES;

        return view('history.index', compact(
            'records',
            'availableYears',
            'programs',
            'municipalities',
            'search',
            'year',
            'programCode',
            'municipality',
        ));
    }
}
