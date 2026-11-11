<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\Program;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Official list of Bukidnon Municipalities & Cities.
     */
    public const MUNICIPALITIES = [
        'Baungon',
        'Cabanglasan',
        'Damulog',
        'Dangcagan',
        'Don Carlos',
        'Impasugong',
        'Kadingilan',
        'Kalilangan',
        'Kibawe',
        'Kitaotao',
        'Lantapan',
        'Libona',
        'Malaybalay City',
        'Malitbog',
        'Manolo Fortich',
        'Maramag',
        'Pangantucan',
        'Quezon',
        'San Fernando',
        'Sumilao',
        'Talakag',
        'Valencia City',
    ];

    /**
     * Standard DOLE Implementation Type presets.
     */
    public const IMPLEMENTATION_TYPES = [
        'Direct Implementation',
        'ACP / Co-Partner (LGU)',
        'Barangay / Community Implementation',
        'Emergency / Calamity Response',
        'Special Implementation',
    ];

    public function index(Request $request): View
    {
        $programs = Program::withCount([
            'projects',
            'beneficiaryPrograms as beneficiaries_count',
            'beneficiaryPrograms as unassigned_count' => fn ($q) => $q->whereNull('project_id'),
        ])->orderByRaw("CASE code WHEN 'TUPAD' THEN 1 WHEN 'SPES' THEN 2 WHEN 'DILP' THEN 3 WHEN 'GIP' THEN 4 ELSE 5 END")->get();

        $selected = $request->filled('program')
            ? $programs->firstWhere('code', strtoupper((string) $request->input('program')))
            : null;

        $totalProjectsCount = Project::count();
        $totalAssignedBeneficiaries = BeneficiaryProgram::whereNotNull('project_id')->count();

        $projects = Project::with('program')
            ->withCount('beneficiaryPrograms')
            ->when($selected, fn ($q) => $q->where('program_id', $selected->id))
            ->when($request->input('search'), function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('implementation_type', 'like', "%{$s}%")
                        ->orWhere('municipality', 'like', "%{$s}%")
                        ->orWhere('description', 'like', "%{$s}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('projects.index', compact('programs', 'selected', 'projects', 'totalProjectsCount', 'totalAssignedBeneficiaries'));
    }

    public function create(Request $request): View
    {
        $programs = Program::orderByRaw("CASE code WHEN 'TUPAD' THEN 1 WHEN 'SPES' THEN 2 WHEN 'DILP' THEN 3 WHEN 'GIP' THEN 4 ELSE 5 END")->get();
        $municipalities = self::MUNICIPALITIES;
        $implementationTypes = self::IMPLEMENTATION_TYPES;

        return view('projects.create', [
            'programs' => $programs,
            'municipalities' => $municipalities,
            'implementationTypes' => $implementationTypes,
            'selectedProgramId' => $programs->firstWhere('code', strtoupper((string) $request->input('program')))?->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:200'],
            'implementation_type' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'municipality' => ['nullable', 'string', 'max:100'],
            'target_beneficiaries' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $project = Project::create($data + ['created_by' => $request->user()->id]);

        return redirect()->route('projects.show', $project)->with('success', 'Project created successfully.');
    }

    public function edit(Project $project): View
    {
        $project->load('program');
        $programs = Program::orderByRaw("CASE code WHEN 'TUPAD' THEN 1 WHEN 'SPES' THEN 2 WHEN 'DILP' THEN 3 WHEN 'GIP' THEN 4 ELSE 5 END")->get();
        $municipalities = self::MUNICIPALITIES;
        $implementationTypes = self::IMPLEMENTATION_TYPES;

        return view('projects.edit', compact('project', 'programs', 'municipalities', 'implementationTypes'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:200'],
            'implementation_type' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'municipality' => ['nullable', 'string', 'max:100'],
            'target_beneficiaries' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $project->update($data);

        return redirect()->route('projects.show', $project)->with('success', 'Project details and implementation dates updated successfully.');
    }

    public function show(Request $request, Project $project): View
    {
        $project->load('program');

        // Extract distinct barangays with counts for beneficiaries enrolled in this project
        $availableBarangays = Beneficiary::whereIn('id', function ($query) use ($project) {
            $query->select('beneficiary_id')
                ->from('beneficiary_programs')
                ->where('project_id', $project->id);
        })
            ->whereNotNull('barangay')
            ->where('barangay', '!=', '')
            ->select('barangay')
            ->selectRaw('count(*) as count')
            ->groupBy('barangay')
            ->orderBy('barangay')
            ->get();

        $selectedBarangay = $request->input('barangay');
        $selectedStatus = $request->input('status');

        $statusCounts = [
            'all' => $project->beneficiaryPrograms()->count(),
            'approved' => $project->beneficiaryPrograms()->where('status', 'approved')->count(),
            'pending' => $project->beneficiaryPrograms()->where('status', 'pending')->count(),
        ];

        $members = $project->beneficiaryPrograms()
            ->with(['beneficiary', 'program'])
            ->when($selectedStatus, fn ($q, $status) => $q->where('status', $status))
            ->whereHas('beneficiary', function ($q) use ($request, $selectedBarangay) {
                if ($selectedBarangay) {
                    $q->where('barangay', $selectedBarangay);
                }

                if ($search = $request->input('search')) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('full_name', 'like', "%{$search}%")
                            ->orWhere('government_id_number', 'like', "%{$search}%")
                            ->orWhere('barangay', 'like', "%{$search}%")
                            ->orWhere('municipality', 'like', "%{$search}%");
                    });
                }
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('projects.show', compact('project', 'members', 'availableBarangays', 'selectedBarangay', 'selectedStatus', 'statusCounts'));
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted. Its beneficiaries were kept and are now unassigned.');
    }
}
