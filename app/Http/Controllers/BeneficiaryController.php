<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBeneficiaryRequest;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\DilpGroup;
use App\Models\DuplicateFlag;
use App\Models\Program;
use App\Models\TupadProfile;
use App\Services\DuplicateDetectionService;
use App\Services\EligibilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BeneficiaryController extends Controller
{
    public function __construct(
        protected DuplicateDetectionService $duplicateService,
        protected EligibilityService $eligibilityService
    ) {}

    public function index(Request $request): View
    {
        $query = Beneficiary::with(['beneficiaryPrograms.program', 'creator']);

        // 1. Search by name, government ID, contact number
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('government_id_number', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        // 2. Filter by Program
        if ($programCode = $request->input('program')) {
            $query->whereHas('beneficiaryPrograms.program', function ($q) use ($programCode) {
                $q->where('code', $programCode);
            });
        }

        // 3. Filter by Municipality
        if ($municipality = $request->input('municipality')) {
            $query->where('municipality', $municipality);
        }

        // 4. Filter by Barangay
        if ($barangay = $request->input('barangay')) {
            $query->where('barangay', $barangay);
        }

        // 5. Filter by Date range
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['full_name', 'last_name', 'date_of_birth', 'municipality', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $beneficiaries = $query->paginate($request->input('per_page', 15))->withQueryString();

        // Get filter dropdown datasets
        $programs = Program::all();
        $municipalities = Beneficiary::distinct()->whereNotNull('municipality')->pluck('municipality')->sort();

        return view('beneficiaries.index', compact('beneficiaries', 'programs', 'municipalities'));
    }

    public function create(): View
    {
        $programs = Program::all();
        $dilpGroups = DilpGroup::all();
        $municipalities = $this->getBukidnonMunicipalities();

        return view('beneficiaries.create', compact('programs', 'dilpGroups', 'municipalities'));
    }

    /**
     * Check duplicate status via AJAX before submitting form.
     */
    public function checkDuplicate(Request $request): JsonResponse
    {
        $data = $request->all();
        $dupResult = $this->duplicateService->checkDuplicates($data, $request->input('exclude_id'));

        return response()->json($dupResult);
    }

    public function store(StoreBeneficiaryRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        // 1. Eligibility Check
        $eligibilityErrors = $this->eligibilityService->validateEligibility($data);
        if (! empty($eligibilityErrors)) {
            return back()->withErrors($eligibilityErrors)->withInput();
        }

        // 2. Duplicate Check
        $dupResult = $this->duplicateService->checkDuplicates($data);

        // If duplicates found and user hasn't explicitly confirmed override
        if ($dupResult['has_duplicates'] && ! $request->boolean('confirm_override')) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'duplicate_detected',
                    'duplicates' => $dupResult['flags'],
                    'max_score' => $dupResult['max_score'],
                    'is_exact' => $dupResult['is_exact'],
                ], 422);
            }

            return back()->withInput()->with('duplicate_flags', $dupResult['flags']);
        }

        // 3. Save Record inside DB Transaction
        DB::beginTransaction();
        try {
            // Build full name
            $fullName = Beneficiary::buildFullName(
                $data['first_name'],
                $data['middle_name'] ?? null,
                $data['last_name'],
                $data['suffix'] ?? null
            );

            // Auto set demographic flags
            $dob = Carbon::parse($data['date_of_birth']);
            $age = $dob->age;
            $isSenior = $age >= 60 || filter_var($data['is_senior_citizen'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $beneficiary = Beneficiary::create([
                'full_name' => $fullName,
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'suffix' => $data['suffix'] ?? null,
                'date_of_birth' => $data['date_of_birth'],
                'sex' => $data['sex'],
                'civil_status' => $data['civil_status'] ?? null,
                'government_id_type' => $data['government_id_type'] ?? null,
                'government_id_number' => $data['government_id_number'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'address' => $data['address'] ?? null,
                'barangay' => $data['barangay'],
                'municipality' => $data['municipality'],
                'is_senior_citizen' => $isSenior,
                'is_pwd' => filter_var($data['is_pwd'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_student' => filter_var($data['is_student'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_government_employee' => filter_var($data['is_government_employee'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_graduating_college' => filter_var($data['is_graduating_college'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'created_by' => auth()->id(),
            ]);

            // Link Program
            $program = Program::where('code', $data['program_code'])->firstOrFail();
            $beneficiaryProgram = BeneficiaryProgram::create([
                'beneficiary_id' => $beneficiary->id,
                'program_id' => $program->id,
                'availment_year' => $data['availment_year'],
                'enrollment_type' => $data['enrollment_type'] ?? null,
                'dilp_group_id' => $data['dilp_group_id'] ?? null,
                'internship_duration' => $data['internship_duration'] ?? null,
                'status' => 'pending',
            ]);

            // Save TUPAD profile if program is TUPAD
            if ($program->code === 'TUPAD') {
                TupadProfile::create([
                    'beneficiary_program_id' => $beneficiaryProgram->id,
                    'project_location_barangay' => $data['project_location_barangay'] ?? $data['barangay'],
                    'project_location_municipality' => $data['project_location_municipality'] ?? $data['municipality'],
                    'project_location_province' => $data['project_location_province'] ?? 'Bukidnon',
                    'project_location_district' => $data['project_location_district'] ?? null,
                    'epayment_account_no' => $data['epayment_account_no'] ?? null,
                    'beneficiary_type' => $data['beneficiary_type'] ?? null,
                    'occupation' => $data['occupation'] ?? null,
                    'average_monthly_income' => $data['average_monthly_income'] ?? null,
                    'dependent_name' => $data['dependent_name'] ?? null,
                    'dependent_relationship' => $data['dependent_relationship'] ?? null,
                    'interested_in_employment' => filter_var($data['interested_in_employment'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'employment_interest_detail' => $data['employment_interest_detail'] ?? null,
                    'skills_training_needed' => $data['skills_training_needed'] ?? null,
                ]);
            }

            // Record duplicate flags if duplicates were flagged
            if ($dupResult['has_duplicates']) {
                foreach ($dupResult['flags'] as $flagData) {
                    DuplicateFlag::create([
                        'beneficiary_id' => $beneficiary->id,
                        'matched_beneficiary_id' => $flagData['matched_beneficiary_id'],
                        'match_score' => $flagData['match_score'],
                        'match_type' => $flagData['match_type'],
                        'matched_fields' => $flagData['matched_fields'],
                        'status' => $request->boolean('confirm_override') ? 'overridden' : 'pending',
                        'reviewed_by' => $request->boolean('confirm_override') ? auth()->id() : null,
                        'remarks' => $data['override_remarks'] ?? 'Saved with override during registration',
                    ]);
                }
            }

            AuditLog::log([
                'action' => 'create',
                'model_type' => Beneficiary::class,
                'model_id' => $beneficiary->id,
                'description' => "Registered beneficiary {$beneficiary->full_name} for program {$program->code}",
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Beneficiary registered successfully.',
                    'beneficiary_id' => $beneficiary->id,
                ]);
            }

            return redirect()->route('beneficiaries.show', $beneficiary)
                ->with('success', "Beneficiary {$beneficiary->full_name} successfully registered under {$program->code}.");

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->withInput()->with('warning', 'An error occurred while saving beneficiary: '.$e->getMessage());
        }
    }

    public function show(Beneficiary $beneficiary): View
    {
        $beneficiary->load([
            'beneficiaryPrograms.program',
            'beneficiaryPrograms.tupadProfile',
            'beneficiaryPrograms.dilpGroup',
            'creator',
            'duplicateFlags.matchedBeneficiary',
        ]);

        return view('beneficiaries.show', compact('beneficiary'));
    }

    public function edit(Beneficiary $beneficiary): View
    {
        $programs = Program::all();
        $dilpGroups = DilpGroup::all();
        $municipalities = $this->getBukidnonMunicipalities();
        $beneficiary->load('beneficiaryPrograms.tupadProfile');

        return view('beneficiaries.edit', compact('beneficiary', 'programs', 'dilpGroups', 'municipalities'));
    }

    public function update(Request $request, Beneficiary $beneficiary): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date'],
            'sex' => ['required', 'in:Male,Female'],
            'civil_status' => ['nullable', 'string', 'max:50'],
            'government_id_type' => ['nullable', 'string', 'max:50'],
            'government_id_number' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'barangay' => ['required', 'string', 'max:100'],
            'municipality' => ['required', 'string', 'max:100'],
            'is_senior_citizen' => ['nullable', 'boolean'],
            'is_pwd' => ['nullable', 'boolean'],
            'is_student' => ['nullable', 'boolean'],
            'is_government_employee' => ['nullable', 'boolean'],
            'is_graduating_college' => ['nullable', 'boolean'],
        ]);

        $fullName = Beneficiary::buildFullName(
            $data['first_name'],
            $data['middle_name'] ?? null,
            $data['last_name'],
            $data['suffix'] ?? null
        );

        $beneficiary->update([
            'full_name' => $fullName,
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'suffix' => $data['suffix'] ?? null,
            'date_of_birth' => $data['date_of_birth'],
            'sex' => $data['sex'],
            'civil_status' => $data['civil_status'] ?? null,
            'government_id_type' => $data['government_id_type'] ?? null,
            'government_id_number' => $data['government_id_number'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'address' => $data['address'] ?? null,
            'barangay' => $data['barangay'],
            'municipality' => $data['municipality'],
            'is_senior_citizen' => filter_var($data['is_senior_citizen'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_pwd' => filter_var($data['is_pwd'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_student' => filter_var($data['is_student'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_government_employee' => filter_var($data['is_government_employee'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_graduating_college' => filter_var($data['is_graduating_college'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);

        AuditLog::log([
            'action' => 'update',
            'model_type' => Beneficiary::class,
            'model_id' => $beneficiary->id,
            'description' => "Updated details for beneficiary {$beneficiary->full_name}",
        ]);

        return redirect()->route('beneficiaries.show', $beneficiary)->with('success', 'Beneficiary updated successfully.');
    }

    public function destroy(Beneficiary $beneficiary): RedirectResponse
    {
        $name = $beneficiary->full_name;
        $id = $beneficiary->id;

        $beneficiary->delete();

        AuditLog::log([
            'action' => 'delete',
            'model_type' => Beneficiary::class,
            'model_id' => $id,
            'description' => "Soft-deleted beneficiary {$name}",
        ]);

        return redirect()->route('beneficiaries.index')->with('success', "Beneficiary {$name} removed.");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        if ($request->boolean('delete_all_matching')) {
            $query = Beneficiary::query();

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('government_id_number', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%");
                });
            }

            if ($programCode = $request->input('program')) {
                $query->whereHas('beneficiaryPrograms.program', function ($q) use ($programCode) {
                    $q->where('code', $programCode);
                });
            }

            if ($municipality = $request->input('municipality')) {
                $query->where('municipality', $municipality);
            }

            $count = $query->count();
            $query->delete();

            AuditLog::log([
                'action' => 'bulk_delete_all',
                'model_type' => Beneficiary::class,
                'model_id' => null,
                'description' => "Batch deleted all {$count} matching beneficiaries in database",
            ]);

            return redirect()->route('beneficiaries.index')->with('success', "Successfully deleted all {$count} matching beneficiaries from database.");
        }

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:beneficiaries,id'],
        ]);

        $ids = $request->input('ids');
        $count = count($ids);

        Beneficiary::whereIn('id', $ids)->delete();

        AuditLog::log([
            'action' => 'bulk_delete',
            'model_type' => Beneficiary::class,
            'model_id' => null,
            'description' => "Batch deleted {$count} beneficiaries",
        ]);

        return redirect()->route('beneficiaries.index')->with('success', "Successfully deleted {$count} selected beneficiaries.");
    }

    private function getBukidnonMunicipalities(): array
    {
        return [
            'Baungon', 'Cabanglasan', 'Damulog', 'Dangcagan', 'Don Carlos',
            'Impasugong', 'Kadingilan', 'Kibawe', 'Kitaotao', 'Lantapan',
            'Libona', 'Malaybalay City', 'Maramag', 'Manolo Fortich',
            'Pangantucan', 'Quezon', 'San Fernando', 'Sumilao',
            'Talakag', 'Valencia City',
        ];
    }
}
