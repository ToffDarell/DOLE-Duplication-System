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
        $query = Beneficiary::with(['beneficiaryPrograms.program', 'creator', 'duplicateFlags', 'matchedDuplicateFlags']);

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
        try {
            $data = $request->all();
            if (isset($data['first_name'])) {
                $data['first_name'] = trim($data['first_name']);
            }
            if (isset($data['last_name'])) {
                $data['last_name'] = trim($data['last_name']);
            }
            if (isset($data['middle_name'])) {
                $data['middle_name'] = trim($data['middle_name']);
            }

            $dupResult = $this->duplicateService->checkDuplicates($data, $request->input('exclude_id'));
            $householdResult = $this->duplicateService->checkHouseholdDuplicates($data, $request->input('exclude_id'));

            $allFlags = array_merge($dupResult['flags'], $householdResult['flags']);
            $hasDuplicates = count($allFlags) > 0;
            $maxScore = 0;
            foreach ($allFlags as $flag) {
                if (($flag['match_score'] ?? 0) > $maxScore) {
                    $maxScore = $flag['match_score'];
                }
            }

            $isCalamityOverride = filter_var($data['is_calamity_override'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $programCode = strtoupper($data['program_code'] ?? 'TUPAD');
            $availYear = (int) ($data['availment_year'] ?? date('Y'));

            // Check if any match already availed in THIS program in the current year
            $sameYearMatch = null;
            foreach ($dupResult['flags'] as $flag) {
                if (! empty($flag['same_program_current_year']) || isset($flag['matched_fields']['same_program'])) {
                    $sameYearMatch = $flag;
                    break;
                }
            }

            if ($sameYearMatch && ! $isCalamityOverride) {
                return response()->json([
                    'success' => false,
                    'status' => 'same_year_conflict',
                    'code' => 'SAME_YEAR_AVAILMENT_BLOCKED',
                    'message' => "Beneficiary has already availed of {$programCode} for calendar year {$availYear}.",
                    'has_duplicates' => true,
                    'is_same_year_conflict' => true,
                    'existing_beneficiary_id' => $sameYearMatch['matched_beneficiary_id'] ?? null,
                    'existing_beneficiary_name' => $sameYearMatch['matched_beneficiary_name'] ?? ($sameYearMatch['matched_beneficiary'] ? $sameYearMatch['matched_beneficiary']->full_name : null),
                    'existing_dob' => $sameYearMatch['existing_dob'] ?? null,
                    'input_dob' => $sameYearMatch['input_dob'] ?? null,
                    'last_availment' => $sameYearMatch['last_availment'] ?? "{$programCode} {$availYear}",
                    'flags' => $allFlags,
                    'duplicates' => $allFlags,
                    'max_score' => $maxScore,
                    'is_exact' => $dupResult['is_exact'] ?? false,
                    'cross_program_conflicts' => $dupResult['cross_program_conflicts'] ?? [],
                ], 409);
            }

            // Run eligibility checks to catch household limits and all policy restrictions
            $eligibilityErrors = $this->eligibilityService->validateEligibility($data);

            if (! empty($eligibilityErrors)) {
                if (isset($eligibilityErrors['household_limit'])) {
                    $inputAddress = trim($data['address'] ?? '');
                    $inputBarangay = trim($data['barangay'] ?? '');
                    $householdMatches = Beneficiary::with('beneficiaryPrograms.program')
                        ->whereRaw('LOWER(TRIM(barangay)) = ?', [mb_strtolower($inputBarangay)])
                        ->whereRaw('LOWER(TRIM(address)) = ?', [mb_strtolower($inputAddress)])
                        ->get();

                    foreach ($householdMatches as $hm) {
                        $allFlags[] = [
                            'matched_beneficiary' => $hm,
                            'matched_beneficiary_id' => $hm->id,
                            'matched_beneficiary_name' => $hm->full_name,
                            'existing_dob' => $hm->date_of_birth ? $hm->date_of_birth->format('Y-m-d') : 'N/A',
                            'input_dob' => $data['date_of_birth'] ?? null,
                            'match_score' => 85,
                            'match_type' => 'high',
                            'is_household_match' => true,
                            'matched_fields' => [
                                'household' => "Household Limit: Same Barangay ({$hm->barangay}) & Address ({$hm->address}) enrolled in {$availYear}",
                            ],
                            'is_exact_name_match' => false,
                            'is_same_name_diff_dob' => false,
                            'is_same_name_diff_identity' => false,
                            'is_returning_beneficiary' => false,
                            'last_availment' => "TUPAD {$availYear}",
                        ];
                    }

                    return response()->json([
                        'success' => false,
                        'status' => 'household_limit_detected',
                        'code' => 'HOUSEHOLD_LIMIT_WARNING',
                        'message' => $eligibilityErrors['household_limit'],
                        'has_duplicates' => true,
                        'is_household_limit' => true,
                        'existing_beneficiary_id' => $householdMatches->first()?->id,
                        'existing_beneficiary_name' => $householdMatches->first()?->full_name,
                        'flags' => $allFlags,
                        'duplicates' => $allFlags,
                        'max_score' => 85,
                        'is_exact' => false,
                        'cross_program_conflicts' => $dupResult['cross_program_conflicts'] ?? [],
                    ], 409);
                }

                $firstMsg = reset($eligibilityErrors);

                return response()->json([
                    'success' => false,
                    'status' => 'eligibility_restriction',
                    'code' => 'ELIGIBILITY_RESTRICTION',
                    'message' => $firstMsg,
                    'has_duplicates' => $hasDuplicates,
                    'is_eligibility_conflict' => true,
                    'eligibility_errors' => array_values($eligibilityErrors),
                    'flags' => $allFlags,
                    'duplicates' => $allFlags,
                    'max_score' => $maxScore,
                    'is_exact' => $dupResult['is_exact'] ?? false,
                    'cross_program_conflicts' => $dupResult['cross_program_conflicts'] ?? [],
                ], 409);
            }

            $isReturning = $dupResult['is_returning_beneficiary'] ?? false;
            $returningMatch = $dupResult['returning_match'] ?? null;
            $isSameNameDiffIdentity = $dupResult['is_same_name_diff_identity'] ?? false;
            $topMatch = $dupResult['top_match'] ?? ($dupResult['flags'][0] ?? null);

            if ($hasDuplicates) {
                $status = 'duplicate_detected';
                $code = 'DUPLICATE_ENTRY';
                $message = 'Potential duplicate beneficiary or household match detected.';

                if ($isReturning) {
                    $status = 'returning_beneficiary_detected';
                    $code = 'RETURNING_BENEFICIARY';
                    $message = 'Existing profile found for returning beneficiary.';
                } elseif ($isSameNameDiffIdentity && $topMatch) {
                    $status = 'same_name_detected';
                    $code = 'SAME_NAME_RECORD';
                    $message = sprintf(
                        'An existing beneficiary named "%s" already exists in the system (DOB: %s vs Entered: %s). Please verify if this is an encoding typo or two distinct individuals.',
                        $topMatch['matched_beneficiary_name'] ?? 'Existing Record',
                        $topMatch['existing_dob'] ?? 'N/A',
                        $topMatch['input_dob'] ?? 'N/A'
                    );
                }

                return response()->json([
                    'success' => false,
                    'status' => $status,
                    'code' => $code,
                    'message' => $message,
                    'has_duplicates' => true,
                    'is_returning_beneficiary' => $isReturning,
                    'is_same_name_diff_identity' => $isSameNameDiffIdentity,
                    'existing_beneficiary_id' => $topMatch ? $topMatch['matched_beneficiary_id'] : null,
                    'existing_beneficiary_name' => $topMatch ? $topMatch['matched_beneficiary_name'] : null,
                    'existing_dob' => $topMatch['existing_dob'] ?? null,
                    'input_dob' => $topMatch['input_dob'] ?? null,
                    'last_availment' => $topMatch['last_availment'] ?? null,
                    'flags' => $allFlags,
                    'duplicates' => $allFlags,
                    'max_score' => $maxScore,
                    'is_exact' => $dupResult['is_exact'] ?? false,
                    'cross_program_conflicts' => $dupResult['cross_program_conflicts'] ?? [],
                ], 409);
            }

            return response()->json([
                'success' => true,
                'has_duplicates' => false,
                'flags' => [],
                'duplicates' => [],
                'max_score' => 0,
                'is_exact' => false,
                'cross_program_conflicts' => [],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'code' => 'CHECK_DUPLICATE_ERROR',
                'message' => 'An error occurred during duplicate check: '.$e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreBeneficiaryRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $existingBeneficiaryId = $request->input('existing_beneficiary_id');

        // Handle Linking to Existing Master Profile
        if (! empty($existingBeneficiaryId)) {
            $beneficiary = Beneficiary::findOrFail($existingBeneficiaryId);

            // 1. Eligibility Check for existing beneficiary
            $eligibilityErrors = $this->eligibilityService->validateEligibility($data, $beneficiary);
            if (! empty($eligibilityErrors)) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'status' => 'eligibility_failed',
                        'message' => reset($eligibilityErrors),
                        'errors' => $eligibilityErrors,
                    ], 422);
                }

                return back()->withErrors($eligibilityErrors)->withInput();
            }

            DB::beginTransaction();
            try {
                // Update master profile with any updated details
                $beneficiary->update([
                    'contact_number' => $data['contact_number'] ?? $beneficiary->contact_number,
                    'address' => $data['address'] ?? $beneficiary->address,
                    'barangay' => $data['barangay'] ?? $beneficiary->barangay,
                    'municipality' => $data['municipality'] ?? $beneficiary->municipality,
                    'civil_status' => $data['civil_status'] ?? $beneficiary->civil_status,
                    'is_senior_citizen' => filter_var($data['is_senior_citizen'] ?? $beneficiary->is_senior_citizen, FILTER_VALIDATE_BOOLEAN),
                    'is_pwd' => filter_var($data['is_pwd'] ?? $beneficiary->is_pwd, FILTER_VALIDATE_BOOLEAN),
                    'is_student' => filter_var($data['is_student'] ?? ($data['is_enrolled'] ?? ($data['is_graduating_student'] ?? $beneficiary->is_student)), FILTER_VALIDATE_BOOLEAN),
                    'is_government_employee' => filter_var($data['is_government_employee'] ?? $beneficiary->is_government_employee, FILTER_VALIDATE_BOOLEAN),
                    'is_graduating_college' => filter_var($data['is_graduating_student'] ?? ($data['is_graduating_college'] ?? $beneficiary->is_graduating_college), FILTER_VALIDATE_BOOLEAN),
                ]);

                // Attach new BeneficiaryProgram record directly to master profile
                $program = Program::where('code', $data['program_code'])->firstOrFail();

                $beneficiaryProgram = BeneficiaryProgram::create([
                    'beneficiary_id' => $beneficiary->id,
                    'program_id' => $program->id,
                    'availment_year' => $data['availment_year'],
                    'enrollment_type' => $data['enrollment_type'] ?? null,
                    'dilp_group_id' => $data['dilp_group_id'] ?? null,
                    'internship_duration' => $data['internship_duration'] ?? null,
                    'is_calamity_override' => filter_var($data['is_calamity_override'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'calamity_remarks' => $data['calamity_remarks'] ?? null,
                    'status' => 'approved',
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

                AuditLog::log([
                    'action' => 'link_program',
                    'model_type' => Beneficiary::class,
                    'model_id' => $beneficiary->id,
                    'description' => "Linked {$data['availment_year']} {$program->code} availment to existing Beneficiary {$beneficiary->full_name} (ID: {$beneficiary->id})",
                ]);

                DB::commit();

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => "Successfully linked {$data['availment_year']} {$program->code} availment to returning beneficiary {$beneficiary->full_name}.",
                        'beneficiary_id' => $beneficiary->id,
                        'beneficiary' => $beneficiary,
                        'redirect_url' => route('beneficiaries.show', $beneficiary),
                    ], 200);
                }

                return redirect()->route('beneficiaries.show', $beneficiary)
                    ->with('success', "Successfully linked {$data['availment_year']} {$program->code} availment to returning beneficiary {$beneficiary->full_name}.");
            } catch (\Throwable $e) {
                DB::rollBack();
                report($e);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'An error occurred while linking program: '.$e->getMessage(),
                    ], 500);
                }

                return back()->with('error', 'Failed to link program: '.$e->getMessage())->withInput();
            }
        }

        // 1. Eligibility Check
        $confirmOverride = $request->boolean('confirm_override')
            || $request->boolean('override_duplicate')
            || filter_var($request->input('confirm_override', 0), FILTER_VALIDATE_BOOLEAN)
            || filter_var($request->input('override_duplicate', 0), FILTER_VALIDATE_BOOLEAN);
        $isLogForReview = $request->boolean('log_for_review')
            || filter_var($request->input('log_for_review', 0), FILTER_VALIDATE_BOOLEAN);

        if (! empty($eligibilityErrors)) {
            $isHousehold = isset($eligibilityErrors['household_limit']);
            $isSameYear = isset($eligibilityErrors['availment_year']);

            // If user explicitly chose to log for review or override with permission:
            if (($isLogForReview || $confirmOverride) && ($isHousehold || $isSameYear)) {
                AuditLog::log([
                    'action' => $confirmOverride ? 'override_duplicate' : 'flag_for_review',
                    'model_type' => Beneficiary::class,
                    'description' => $confirmOverride
                        ? 'Validator approved registration despite warning. Remarks: '.($request->input('override_remarks') ?? 'Verified separate unit')
                        : 'Registration queued in Duplicate Resolution Console for review',
                ]);
            } else {
                if ($request->wantsJson()) {
                    if ($isHousehold || $isSameYear) {
                        return response()->json([
                            'success' => false,
                            'status' => $isHousehold ? 'household_limit_detected' : 'same_year_conflict',
                            'code' => $isHousehold ? 'HOUSEHOLD_LIMIT_WARNING' : 'SAME_YEAR_AVAILMENT_BLOCKED',
                            'message' => reset($eligibilityErrors),
                            'is_household_limit' => $isHousehold,
                            'is_same_year_conflict' => $isSameYear,
                            'has_duplicates' => true,
                            'errors' => $eligibilityErrors,
                        ], 409);
                    }

                    return response()->json([
                        'success' => false,
                        'status' => 'eligibility_failed',
                        'message' => reset($eligibilityErrors),
                        'errors' => $eligibilityErrors,
                    ], 422);
                }

                return back()->withErrors($eligibilityErrors)->withInput();
            }
        }

        // 2. Pre-Save Duplicate Check (Combines identity & household checks)
        $dupResult = $this->duplicateService->checkDuplicates($data);
        $householdResult = $this->duplicateService->checkHouseholdDuplicates($data);
        $allFlags = array_merge($dupResult['flags'], $householdResult['flags']);

        $hasDuplicates = count($allFlags) > 0;

        if ($hasDuplicates && ! $confirmOverride && ! $isLogForReview) {
            $maxScore = 0;
            foreach ($allFlags as $flag) {
                if (($flag['match_score'] ?? 0) > $maxScore) {
                    $maxScore = $flag['match_score'];
                }
            }

            $isReturning = $dupResult['is_returning_beneficiary'] ?? false;
            $returningMatch = $dupResult['returning_match'] ?? null;

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => $isReturning ? 'returning_beneficiary_detected' : 'duplicate_detected',
                    'code' => $isReturning ? 'RETURNING_BENEFICIARY' : 'DUPLICATE_ENTRY',
                    'message' => $isReturning ? 'Existing profile found for returning beneficiary.' : 'Potential duplicate beneficiary or household match detected.',
                    'duplicates' => $allFlags,
                    'flags' => $allFlags,
                    'max_score' => $maxScore,
                    'is_exact' => $dupResult['is_exact'] ?? false,
                    'is_returning_beneficiary' => $isReturning,
                    'existing_beneficiary_id' => $returningMatch ? $returningMatch['matched_beneficiary_id'] : null,
                    'last_availment' => $returningMatch['last_availment'] ?? null,
                ], 409);
            }

            return back()->withInput()->with('duplicate_flags', $allFlags);
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
                'is_student' => filter_var($data['is_student'] ?? ($data['is_enrolled'] ?? ($data['is_graduating_student'] ?? false)), FILTER_VALIDATE_BOOLEAN),
                'is_government_employee' => filter_var($data['is_government_employee'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_graduating_college' => filter_var($data['is_graduating_student'] ?? ($data['is_graduating_college'] ?? ($data['graduating_college_student'] ?? false)), FILTER_VALIDATE_BOOLEAN),
                'created_by' => auth()->id(),
            ]);

            // Link Program - if registered with duplicate flag or logged for review, set status to 'pending'
            $program = Program::where('code', $data['program_code'])->firstOrFail();
            $enrollmentStatus = ($hasDuplicates && ! $confirmOverride) ? 'pending' : 'approved';

            $beneficiaryProgram = BeneficiaryProgram::create([
                'beneficiary_id' => $beneficiary->id,
                'program_id' => $program->id,
                'availment_year' => $data['availment_year'],
                'enrollment_type' => $data['enrollment_type'] ?? null,
                'dilp_group_id' => $data['dilp_group_id'] ?? null,
                'internship_duration' => $data['internship_duration'] ?? null,
                'is_calamity_override' => filter_var($data['is_calamity_override'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'calamity_remarks' => $data['calamity_remarks'] ?? null,
                'status' => $enrollmentStatus,
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

            // Save duplicate flags if any duplicates exist or if logged for review
            if ($hasDuplicates) {
                $flagStatus = $confirmOverride ? 'overridden' : 'pending';
                $flagRemarks = $confirmOverride ? ($request->input('override_remarks') ?? 'Validator Override Approved') : 'Queued for Validator Review';
                $reviewerId = $confirmOverride ? auth()->id() : null;

                $this->duplicateService->recordDuplicateFlags($beneficiary, $allFlags, $flagStatus, $flagRemarks, $reviewerId);
            }

            AuditLog::log([
                'action' => 'create',
                'model_type' => Beneficiary::class,
                'model_id' => $beneficiary->id,
                'description' => "Registered new beneficiary {$beneficiary->full_name} for {$program->code} ({$data['availment_year']})".($hasDuplicates ? ' (Flagged Duplicates)' : ''),
            ]);

            DB::commit();

            $redirectUrl = ($hasDuplicates || $isLogForReview) ? route('duplicates.index') : route('beneficiaries.show', $beneficiary);
            $successMsg = ($hasDuplicates || $isLogForReview)
                ? ($confirmOverride ? "Beneficiary {$beneficiary->full_name} registered and override approved." : "Beneficiary {$beneficiary->full_name} registered and sent to Duplicate Resolution Console for review.")
                : 'Beneficiary registered successfully.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'beneficiary_id' => $beneficiary->id,
                    'beneficiary' => $beneficiary,
                    'redirect_url' => $redirectUrl,
                ], 201);
            }

            return redirect($redirectUrl)->with('success', $successMsg);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while saving beneficiary: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to save beneficiary: '.$e->getMessage())->withInput();
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
        $beneficiary->load(['beneficiaryPrograms.program', 'beneficiaryPrograms.tupadProfile', 'beneficiaryPrograms.dilpGroup']);

        return view('beneficiaries.edit', compact('beneficiary', 'programs', 'dilpGroups', 'municipalities'));
    }

    public function storeAvailment(Request $request, Beneficiary $beneficiary): RedirectResponse|JsonResponse
    {
        return app(AvailmentController::class)->store($request, $beneficiary);
    }

    public function updateAvailment(Request $request, BeneficiaryProgram $availment): RedirectResponse|JsonResponse
    {
        return app(AvailmentController::class)->update($request, $availment);
    }

    public function destroyAvailment(BeneficiaryProgram $availment): RedirectResponse|JsonResponse
    {
        return app(AvailmentController::class)->destroy($availment);
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

        // Synchronize pending duplicate flags address detail if modified
        $pendingFlags = DuplicateFlag::where(function ($q) use ($beneficiary) {
            $q->where('beneficiary_id', $beneficiary->id)
                ->orWhere('matched_beneficiary_id', $beneficiary->id);
        })->where('household_match_flag', true)->get();

        foreach ($pendingFlags as $pFlag) {
            $mf = $pFlag->matched_fields ?? [];
            if (isset($mf['household_address'])) {
                $mf['household_address'] = $pFlag->getHouseholdAddressDetail();
                $pFlag->update(['matched_fields' => $mf]);
            }
        }

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
            'description' => "Permanently deleted beneficiary {$name}",
        ]);

        return redirect()->route('beneficiaries.index')->with('success', "Beneficiary {$name} permanently deleted.");
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
                'description' => "Permanently deleted all {$count} matching beneficiaries from database",
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
            'description' => "Permanently deleted {$count} beneficiaries from database",
        ]);

        return redirect()->route('beneficiaries.index')->with('success', "Successfully deleted {$count} selected beneficiaries from database.");
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
