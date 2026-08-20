<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\Program;
use Carbon\Carbon;

class EligibilityService
{
    /**
     * Validate program eligibility for a beneficiary.
     *
     * @param  array  $data  Input data
     * @param  Beneficiary|null  $beneficiary  Existing beneficiary if updating/adding program
     * @return array Array of error messages (empty if eligible)
     */
    public function validateEligibility(array $data, ?Beneficiary $beneficiary = null): array
    {
        $errors = [];

        $dob = isset($data['date_of_birth']) ? Carbon::parse($data['date_of_birth']) : ($beneficiary?->date_of_birth);
        $age = $dob ? $dob->age : null;

        $programCode = strtoupper($data['program_code'] ?? '');
        $programYear = (int) ($data['availment_year'] ?? date('Y'));

        $isStudent = filter_var($data['is_student'] ?? ($beneficiary?->is_student ?? false), FILTER_VALIDATE_BOOLEAN);
        $isGovEmp = filter_var($data['is_government_employee'] ?? ($beneficiary?->is_government_employee ?? false), FILTER_VALIDATE_BOOLEAN);

        $program = Program::where('code', $programCode)->first();
        if (! $program) {
            return ['program_code' => 'Invalid program selected.'];
        }

        // Unique Gov ID check
        if (! empty($data['government_id_number'])) {
            $govIdQuery = Beneficiary::where('government_id_number', trim($data['government_id_number']));
            if ($beneficiary) {
                $govIdQuery->where('id', '!=', $beneficiary->id);
            }
            if ($govIdQuery->exists()) {
                $errors['government_id_number'] = 'The government ID number is already registered to another beneficiary.';
            }
        }

        // Check Program Specific Rules
        switch ($programCode) {
            case 'TUPAD':
                if ($age !== null && $age < 18) {
                    $errors['date_of_birth'] = 'TUPAD beneficiaries must be at least 18 years old (current age: '.$age.').';
                }
                if ($isStudent) {
                    $errors['is_student'] = 'Currently enrolled students are not eligible for TUPAD.';
                }
                if ($isGovEmp) {
                    $errors['is_government_employee'] = 'Government employees are not eligible for TUPAD.';
                }
                break;

            case 'SPES':
                if ($age !== null && ($age < 15 || $age > 30)) {
                    $errors['date_of_birth'] = 'SPES beneficiaries must be between 15 and 30 years old (current age: '.$age.').';
                }
                if ($isGovEmp) {
                    $errors['is_government_employee'] = 'Government employees are not eligible for SPES.';
                }
                break;

            case 'DILP':
                // Check if beneficiary is already in an active group if group enrollment
                if (($data['enrollment_type'] ?? 'individual') === 'group' && ! empty($data['dilp_group_id'])) {
                    if ($beneficiary) {
                        $activeGroupCount = BeneficiaryProgram::where('beneficiary_id', $beneficiary->id)
                            ->where('program_id', $program->id)
                            ->where('enrollment_type', 'group')
                            ->whereNotNull('dilp_group_id')
                            ->where('dilp_group_id', '!=', $data['dilp_group_id'])
                            ->whereIn('status', ['pending', 'approved'])
                            ->count();

                        if ($activeGroupCount > 0) {
                            $errors['dilp_group_id'] = 'A beneficiary cannot be enrolled in more than 1 active DILP group at a time.';
                        }
                    }
                }
                break;

            case 'GIP':
                // GIP: once ever (lifetime) rule
                if ($beneficiary) {
                    $gipExists = BeneficiaryProgram::where('beneficiary_id', $beneficiary->id)
                        ->where('program_id', $program->id)
                        ->exists();

                    if ($gipExists) {
                        $errors['program_code'] = 'GIP (Government Internship Program) is a ONCE IN A LIFETIME program. This beneficiary has already participated.';
                    }
                }
                break;
        }

        // Once per year rule for TUPAD, SPES, DILP
        if (in_array($programCode, ['TUPAD', 'SPES', 'DILP'])) {
            if ($beneficiary) {
                $hasAvailedThisYear = BeneficiaryProgram::where('beneficiary_id', $beneficiary->id)
                    ->where('program_id', $program->id)
                    ->where('availment_year', $programYear)
                    ->exists();

                if ($hasAvailedThisYear) {
                    $errors['availment_year'] = "Beneficiary has already availed of {$programCode} for the year {$programYear}. Program rules restrict availment to once per year.";
                }
            }
        }

        return $errors;
    }
}
