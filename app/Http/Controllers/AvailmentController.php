<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\Program;
use App\Models\TupadProfile;
use App\Services\EligibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvailmentController extends Controller
{
    public function __construct(
        protected EligibilityService $eligibilityService
    ) {}

    /**
     * Attach a new program availment to an existing beneficiary.
     */
    public function store(Request $request, Beneficiary $beneficiary): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'program_code' => ['nullable', 'string', 'in:TUPAD,SPES,DILP,GIP'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'availment_year' => ['required', 'integer', 'min:2020', 'max:'.(date('Y') + 1)],
            'enrollment_type' => ['nullable', 'in:individual,group'],
            'dilp_group_id' => ['nullable', 'exists:dilp_groups,id'],
            'internship_duration' => ['nullable', 'string', 'in:6_months,1_year'],
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
            'is_calamity_override' => ['nullable', 'boolean'],
            'calamity_remarks' => ['nullable', 'string', 'max:500'],
            'remarks' => ['nullable', 'string', 'max:500'],

            // Optional TUPAD specific
            'project_location_barangay' => ['nullable', 'string', 'max:100'],
            'project_location_municipality' => ['nullable', 'string', 'max:100'],
            'project_location_province' => ['nullable', 'string', 'max:100'],
            'beneficiary_type' => ['nullable', 'string', 'max:100'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'average_monthly_income' => ['nullable', 'string', 'max:50'],
        ]);

        $program = ! empty($data['program_id'])
            ? Program::findOrFail($data['program_id'])
            : Program::where('code', $data['program_code'])->firstOrFail();

        $availmentYear = (int) $data['availment_year'];

        // 1. Eligibility Check
        $eligibilityData = array_merge($beneficiary->toArray(), [
            'program_code' => $program->code,
            'availment_year' => $availmentYear,
            'is_calamity_override' => (bool) ($data['is_calamity_override'] ?? false),
            'calamity_remarks' => $data['calamity_remarks'] ?? null,
            'enrollment_type' => $data['enrollment_type'] ?? null,
            'dilp_group_id' => $data['dilp_group_id'] ?? null,
            'internship_duration' => $data['internship_duration'] ?? null,
        ]);

        $eligibilityErrors = $this->eligibilityService->validateEligibility($eligibilityData, $beneficiary);
        if (! empty($eligibilityErrors)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => reset($eligibilityErrors),
                    'errors' => $eligibilityErrors,
                ], 422);
            }

            return back()->withErrors($eligibilityErrors)->withInput();
        }

        // 2. Prevent duplicate program + year combination
        $existingAvailment = BeneficiaryProgram::where('beneficiary_id', $beneficiary->id)
            ->where('program_id', $program->id)
            ->where('availment_year', $availmentYear)
            ->first();

        if ($existingAvailment) {
            $msg = "Beneficiary {$beneficiary->full_name} is already registered in {$program->code} for the {$availmentYear} cycle.";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg)->withInput();
        }

        DB::beginTransaction();
        try {
            $availment = $beneficiary->beneficiaryPrograms()->create([
                'program_id' => $program->id,
                'availment_year' => $availmentYear,
                'enrollment_type' => $data['enrollment_type'] ?? ($program->code === 'DILP' ? 'individual' : null),
                'dilp_group_id' => $data['dilp_group_id'] ?? null,
                'internship_duration' => $data['internship_duration'] ?? null,
                'status' => $data['status'] ?? 'approved',
                'is_calamity_override' => (bool) ($data['is_calamity_override'] ?? false),
                'calamity_remarks' => $data['calamity_remarks'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'reviewed_by' => auth()->id(),
            ]);

            // Save TUPAD profile if applicable
            if ($program->code === 'TUPAD') {
                TupadProfile::create([
                    'beneficiary_program_id' => $availment->id,
                    'project_location_barangay' => $data['project_location_barangay'] ?? $beneficiary->barangay,
                    'project_location_municipality' => $data['project_location_municipality'] ?? $beneficiary->municipality,
                    'project_location_province' => $data['project_location_province'] ?? 'Bukidnon',
                    'beneficiary_type' => $data['beneficiary_type'] ?? 'Underemployed',
                    'occupation' => $data['occupation'] ?? 'Laborer',
                    'average_monthly_income' => $data['average_monthly_income'] ?? 'Below 5,000',
                ]);
            }

            AuditLog::log([
                'action' => 'AVAILMENT_CREATED',
                'model_type' => Beneficiary::class,
                'model_id' => $beneficiary->id,
                'description' => "Attached {$program->code} ({$availmentYear}) to {$beneficiary->full_name}",
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully attached {$program->code} ({$availmentYear}) to {$beneficiary->full_name}.",
                    'availment' => $availment->load('program'),
                ], 201);
            }

            return back()->with('success', "Successfully attached {$program->code} ({$availmentYear}) grant to {$beneficiary->full_name}.");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to save availment: '.$e->getMessage()], 500);
            }

            return back()->with('error', 'Failed to save availment: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Update an individual program availment.
     */
    public function update(Request $request, BeneficiaryProgram $availment): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'availment_year' => ['required', 'integer', 'min:2020', 'max:'.(date('Y') + 1)],
            'status' => ['required', 'string', 'in:pending,approved,rejected'],
            'enrollment_type' => ['nullable', 'in:individual,group'],
            'dilp_group_id' => ['nullable', 'exists:dilp_groups,id'],
            'internship_duration' => ['nullable', 'string', 'in:6_months,1_year'],
            'is_calamity_override' => ['nullable', 'boolean'],
            'calamity_remarks' => ['nullable', 'string', 'max:500'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $availment->update([
            'availment_year' => $data['availment_year'],
            'status' => $data['status'],
            'enrollment_type' => $data['enrollment_type'] ?? $availment->enrollment_type,
            'dilp_group_id' => $data['dilp_group_id'] ?? $availment->dilp_group_id,
            'internship_duration' => $data['internship_duration'] ?? $availment->internship_duration,
            'is_calamity_override' => (bool) ($data['is_calamity_override'] ?? false),
            'calamity_remarks' => $data['calamity_remarks'] ?? $availment->calamity_remarks,
            'remarks' => $data['remarks'] ?? $availment->remarks,
        ]);

        AuditLog::log([
            'action' => 'AVAILMENT_UPDATED',
            'model_type' => BeneficiaryProgram::class,
            'model_id' => $availment->id,
            'description' => "Updated {$availment->program?->code} ({$availment->availment_year}) availment for beneficiary ID {$availment->beneficiary_id}",
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Program availment updated successfully.',
                'availment' => $availment->fresh(['program', 'dilpGroup']),
            ]);
        }

        return back()->with('success', 'Program availment updated successfully.');
    }

    /**
     * Delete an individual program availment.
     */
    public function destroy(BeneficiaryProgram $availment): RedirectResponse|JsonResponse
    {
        $beneficiary = $availment->beneficiary;
        $programCode = $availment->program?->code ?? 'Program';
        $year = $availment->availment_year;

        $availment->delete();

        AuditLog::log([
            'action' => 'AVAILMENT_DELETED',
            'model_type' => Beneficiary::class,
            'model_id' => $beneficiary?->id,
            'description' => "Removed {$programCode} ({$year}) availment from {$beneficiary?->full_name}",
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Removed {$programCode} ({$year}) availment successfully.",
            ]);
        }

        return back()->with('success', "Removed {$programCode} ({$year}) program availment from profile.");
    }
}
