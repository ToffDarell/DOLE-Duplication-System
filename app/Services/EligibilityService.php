<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\DilpProject;
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

        if (! $beneficiary && ! empty($data['existing_beneficiary_id'])) {
            $beneficiary = Beneficiary::find($data['existing_beneficiary_id']);
        }

        $dob = isset($data['date_of_birth']) ? Carbon::parse($data['date_of_birth']) : ($beneficiary?->date_of_birth);
        $age = $dob ? $dob->age : null;

        $programCode = strtoupper($data['program_code'] ?? '');
        $programYear = (int) ($data['availment_year'] ?? date('Y'));

        $isStudent = filter_var($data['is_student'] ?? ($beneficiary?->is_student ?? false), FILTER_VALIDATE_BOOLEAN);
        $isGovEmp = filter_var($data['is_government_employee'] ?? ($beneficiary?->is_government_employee ?? false), FILTER_VALIDATE_BOOLEAN);
        $isCalamityOverride = filter_var($data['is_calamity_override'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $program = Program::where('code', $programCode)->first();
        if (! $program) {
            return ['program_code' => 'Invalid program selected.'];
        }

        // Global Government Employee Rule: Block across ALL programs (TUPAD, SPES, DILP, GIP)
        if ($isGovEmp) {
            $errors['is_government_employee'] = 'Government employees are not eligible for DOLE assistance programs (TUPAD, SPES, DILP, GIP).';
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

                // TUPAD Household Limit Check (1 worker per household per cycle/year)
                $inputAddress = trim($data['address'] ?? ($beneficiary?->address ?? ''));
                $inputBarangay = trim($data['barangay'] ?? ($beneficiary?->barangay ?? ''));
                $inputMunicipality = trim($data['municipality'] ?? ($beneficiary?->municipality ?? ''));

                if (! empty($inputAddress) && ! empty($inputBarangay)) {
                    $householdQuery = Beneficiary::whereRaw('LOWER(TRIM(barangay)) = ?', [mb_strtolower($inputBarangay)])
                        ->whereRaw('LOWER(TRIM(address)) = ?', [mb_strtolower($inputAddress)]);

                    if (! empty($inputMunicipality)) {
                        $householdQuery->whereRaw('LOWER(TRIM(municipality)) = ?', [mb_strtolower($inputMunicipality)]);
                    }

                    if ($beneficiary) {
                        $householdQuery->where('id', '!=', $beneficiary->id);
                    }

                    $otherHouseholdIds = $householdQuery->pluck('id');
                    if ($otherHouseholdIds->isNotEmpty()) {
                        $hasHouseholdAvailment = BeneficiaryProgram::whereIn('beneficiary_id', $otherHouseholdIds)
                            ->where('program_id', $program->id)
                            ->where('availment_year', $programYear)
                            ->whereIn('status', ['pending', 'approved'])
                            ->exists();

                        if ($hasHouseholdAvailment) {
                            $errors['household_limit'] = 'Household Limit Warning: A relative in this house is already enrolled in this TUPAD cycle.';
                        }
                    }
                }
                break;

            case 'SPES':
                $spesErrors = $this->checkSpesEligibility($data, $beneficiary, $age);
                $errors = array_merge($errors, $spesErrors);
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

                // DILP Liquidation Gatekeeping: Check overdue or unliquidated projects for ACP group or applicant
                if (! empty($data['dilp_group_id'])) {
                    $hasOverdueProject = DilpProject::where('dilp_group_id', $data['dilp_group_id'])
                        ->where(function ($q) {
                            $q->whereIn('liquidation_status', ['overdue', 'Overdue', 'unliquidated', 'Unliquidated'])
                                ->orWhere(function ($sub) {
                                    $sub->where('liquidation_status', '!=', 'liquidated')
                                        ->whereNotNull('end_date')
                                        ->where('end_date', '<', now());
                                });
                        })
                        ->exists();

                    if ($hasOverdueProject) {
                        $errors['liquidation_status'] = 'DILP Liquidation Gatekeeping: The Accredited Co-Partner (ACP) has overdue/unliquidated past projects. New group project creation is blocked.';
                    }
                }

                if ($beneficiary) {
                    $userGroupIds = $beneficiary->beneficiaryPrograms()
                        ->whereNotNull('dilp_group_id')
                        ->pluck('dilp_group_id');

                    if ($userGroupIds->isNotEmpty()) {
                        $hasOverdue = DilpProject::whereIn('dilp_group_id', $userGroupIds)
                            ->where(function ($q) {
                                $q->whereIn('liquidation_status', ['overdue', 'Overdue', 'unliquidated', 'Unliquidated'])
                                    ->orWhere(function ($sub) {
                                        $sub->where('liquidation_status', '!=', 'liquidated')
                                            ->whereNotNull('end_date')
                                            ->where('end_date', '<', now());
                                    });
                            })
                            ->exists();

                        if ($hasOverdue) {
                            $errors['liquidation_status'] = 'DILP Liquidation Gatekeeping: The applicant is associated with an unliquidated/overdue DILP project.';
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

                // GIP: Education & Internship Duration Caps
                $education = strtolower($data['educational_attainment'] ?? ($data['education'] ?? ''));
                $durationInput = $data['internship_duration'] ?? null;
                $durationMonths = isset($data['internship_duration_months']) ? (int) $data['internship_duration_months'] : null;

                if ($durationMonths === null && $durationInput) {
                    if ($durationInput === '6_months') {
                        $durationMonths = 6;
                    } elseif ($durationInput === '1_year') {
                        $durationMonths = 12;
                    } elseif (is_numeric($durationInput)) {
                        $durationMonths = (int) $durationInput;
                    }
                }

                if ($durationMonths !== null) {
                    $isHighSchool = str_contains($education, 'high school') || str_contains($education, 'secondary') || str_contains($education, 'hs');

                    if ($isHighSchool && $durationMonths > 6) {
                        $errors['internship_duration'] = 'High School graduates are capped at a maximum of 6 months internship duration for GIP.';
                    } elseif ($durationMonths > 12) {
                        $errors['internship_duration'] = 'Internship duration for GIP cannot exceed 12 months (1 year).';
                    }
                }
                break;
        }

        // Once per year rule for TUPAD, SPES, DILP (with Calamity Override bypass)
        if (in_array($programCode, ['TUPAD', 'SPES', 'DILP'])) {
            if ($beneficiary) {
                $hasAvailedThisYear = BeneficiaryProgram::where('beneficiary_id', $beneficiary->id)
                    ->where('program_id', $program->id)
                    ->where('availment_year', $programYear)
                    ->exists();

                if ($hasAvailedThisYear) {
                    if ($isCalamityOverride) {
                        AuditLog::log([
                            'action' => 'calamity_override',
                            'model_type' => Beneficiary::class,
                            'model_id' => $beneficiary->id,
                            'description' => "Calamity override invoked for beneficiary ID {$beneficiary->id} for {$programCode} in {$programYear}. Remarks: ".($data['calamity_remarks'] ?? 'Emergency/Calamity re-application'),
                        ]);
                    } else {
                        $errors['availment_year'] = "Beneficiary has already availed of {$programCode} for calendar year {$programYear}. Program rules restrict availment to once per year.";
                    }
                }
            }
        }

        // Cross-Program Simultaneous Availment Checks
        if ($beneficiary) {
            $crossConflicts = [
                'TUPAD' => ['SPES', 'GIP', 'DILP'],
                'SPES' => ['TUPAD', 'GIP'],
                'GIP' => ['TUPAD', 'SPES'],
                'DILP' => ['TUPAD'],
            ];

            $conflictingCodes = $crossConflicts[$programCode] ?? [];
            if (! empty($conflictingCodes)) {
                $conflictingPrograms = Program::whereIn('code', $conflictingCodes)->pluck('id', 'code');

                foreach ($conflictingPrograms as $conflictCode => $conflictProgramId) {
                    $hasConflict = BeneficiaryProgram::where('beneficiary_id', $beneficiary->id)
                        ->where('program_id', $conflictProgramId)
                        ->where('availment_year', $programYear)
                        ->whereIn('status', ['pending', 'approved'])
                        ->exists();

                    if ($hasConflict) {
                        if ($conflictCode === 'TUPAD') {
                            $errors['program_code'] = 'Beneficiary has an active, uncompleted TUPAD project. Cross-program simultaneous availment is restricted.';
                        } elseif ($conflictCode === 'DILP') {
                            $errors['program_code'] = 'Beneficiary has an active DILP project. Cross-program simultaneous availment is restricted.';
                        } else {
                            $errors['program_code'] = "Cannot enroll in {$programCode} — beneficiary is currently enrolled in {$conflictCode} for {$programYear}. Cross-program simultaneous availment is not allowed.";
                        }

                        break;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Check SPES specific eligibility rules.
     */
    public function checkSpesEligibility(array $data, ?Beneficiary $beneficiary = null, ?int $age = null): array
    {
        $errors = [];

        if ($age === null && (isset($data['date_of_birth']) || $beneficiary?->date_of_birth)) {
            $dob = isset($data['date_of_birth']) ? Carbon::parse($data['date_of_birth']) : $beneficiary->date_of_birth;
            $age = $dob?->age;
        }

        if ($age !== null && ($age < 15 || $age > 30)) {
            $errors['date_of_birth'] = 'SPES beneficiaries must be between 15 and 30 years old (current age: '.$age.').';
        }

        $isEnrolled = ! empty($data['is_enrolled']) || ! empty($data['currently_enrolled']) || ! empty($data['is_student']) || ($beneficiary?->is_student);
        $isOsy = ! empty($data['is_osy']) || ! empty($data['out_of_school_youth']) || ! empty($data['is_out_of_school_youth'])
            || (isset($data['beneficiary_type']) && in_array($data['beneficiary_type'], ['Youth / Student', 'Out-of-School Youth', 'Out of School Youth']));
        $isGraduating = ! empty($data['is_graduating_student']) || ! empty($data['graduating_college_student']) || ! empty($data['is_graduating_college']) || ! empty($data['is_graduating']);

        // Allow pass if ANY of these three conditions are met
        if (! $isEnrolled && ! $isOsy && ! $isGraduating) {
            $errors['is_student'] = 'SPES applicants must be currently enrolled students, registered out-of-school youth, or graduating students.';
        }

        return $errors;
    }
}
