<?php

use App\Models\Beneficiary;
use App\Models\DilpGroup;
use App\Models\DilpProject;
use App\Models\Program;
use App\Models\User;
use App\Services\DuplicateDetectionService;
use App\Services\EligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', 'admin@dole-bukidnon.gov.ph')->first();
    $this->duplicateService = app(DuplicateDetectionService::class);
    $this->eligibilityService = app(EligibilityService::class);
});

test('detects transposed first and last names with matching date of birth and triggers duplicate alert', function () {
    // Existing record: Juan Cruz, DOB: 1990-08-15
    Beneficiary::create([
        'full_name' => 'JUAN CRUZ',
        'first_name' => 'JUAN',
        'last_name' => 'CRUZ',
        'date_of_birth' => '1990-08-15',
        'sex' => 'Male',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Casisang',
        'contact_number' => '09171234567',
    ]);

    // Transposed payload: First: Cruz, Last: Juan, same DOB
    $payload = [
        'first_name' => 'Cruz',
        'last_name' => 'Juan',
        'date_of_birth' => '1990-08-15',
        'sex' => 'Male',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Casisang',
        'contact_number' => '09171234567',
        'program_code' => 'TUPAD',
        'availment_year' => 2026,
    ];

    $result = $this->duplicateService->checkDuplicates($payload);

    expect($result['has_duplicates'])->toBeTrue();
    expect($result['max_score'])->toBeGreaterThanOrEqual(75);
    expect($result['flags'][0]['is_transposed_name_match'])->toBeTrue();
});

test('detects maiden and married compound surname variations with matching date of birth', function () {
    // Existing record: Maria Santos, DOB: 1995-05-15
    Beneficiary::create([
        'full_name' => 'MARIA SANTOS',
        'first_name' => 'MARIA',
        'last_name' => 'SANTOS',
        'date_of_birth' => '1995-05-15',
        'sex' => 'Female',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
    ]);

    // Married Name payload: Maria Santos-Cruz, same DOB
    $payload = [
        'first_name' => 'Maria',
        'last_name' => 'Santos-Cruz',
        'date_of_birth' => '1995-05-15',
        'sex' => 'Female',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
        'program_code' => 'TUPAD',
        'availment_year' => 2026,
    ];

    $result = $this->duplicateService->checkDuplicates($payload);

    expect($result['has_duplicates'])->toBeTrue();
    expect($result['max_score'])->toBeGreaterThanOrEqual(75);
    expect($result['flags'][0]['is_compound_surname_match'])->toBeTrue();
});

test('enforces TUPAD household limit of one worker per household per cycle', function () {
    $tupad = Program::where('code', 'TUPAD')->first();

    // Family Member 1: already approved in 2026 at House No. 12, Brgy. Managok
    $member1 = Beneficiary::create([
        'full_name' => 'PEDRO ROA PENDUKO',
        'first_name' => 'PEDRO',
        'last_name' => 'PENDUKO',
        'date_of_birth' => '1980-01-01',
        'sex' => 'Male',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Managok',
        'address' => 'House No. 12',
    ]);

    $member1->beneficiaryPrograms()->create([
        'program_id' => $tupad->id,
        'availment_year' => 2026,
        'status' => 'approved',
    ]);

    // Family Member 2: different person applying for 2026 at the same exact House No. 12, Brgy. Managok
    $payload = [
        'first_name' => 'JUANA',
        'last_name' => 'PENDUKO',
        'date_of_birth' => '1985-02-02',
        'sex' => 'Female',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Managok',
        'address' => 'House No. 12',
        'program_code' => 'TUPAD',
        'availment_year' => 2026,
    ];

    $errors = $this->eligibilityService->validateEligibility($payload);

    expect($errors)->toHaveKey('household_limit');
    expect($errors['household_limit'])->toContain('Household Limit Warning: A relative in this house is already enrolled in this TUPAD cycle.');
});

test('blocks simultaneous active enrollment across TUPAD and DILP within the same year', function () {
    $tupad = Program::where('code', 'TUPAD')->first();

    $beneficiary = Beneficiary::create([
        'full_name' => 'ROBERTO DELA VEGA',
        'first_name' => 'ROBERTO',
        'last_name' => 'DELA VEGA',
        'date_of_birth' => '1992-03-10',
        'sex' => 'Male',
        'municipality' => 'Maramag',
        'barangay' => 'Base Camp',
    ]);

    // Beneficiary already has active 2026 TUPAD availment
    $beneficiary->beneficiaryPrograms()->create([
        'program_id' => $tupad->id,
        'availment_year' => 2026,
        'status' => 'approved',
    ]);

    // Beneficiary attempts to simultaneously enroll in DILP in 2026
    $payload = [
        'program_code' => 'DILP',
        'availment_year' => 2026,
        'enrollment_type' => 'individual',
    ];

    $errors = $this->eligibilityService->validateEligibility($payload, $beneficiary);

    expect($errors)->toHaveKey('program_code');
    expect($errors['program_code'])->toContain('active, uncompleted TUPAD project');
});

test('blocks DILP group enrollment when ACP has overdue unliquidated projects', function () {
    $group = DilpGroup::create([
        'group_name' => 'Bukidnon Farmers Cooperative',
        'co_partner_name' => 'Bukidnon Livelihood Association',
    ]);

    // Create overdue project for this group
    DilpProject::create([
        'dilp_group_id' => $group->id,
        'project_name' => 'Corn Production Grant 2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'liquidation_status' => 'overdue',
    ]);

    $payload = [
        'program_code' => 'DILP',
        'availment_year' => 2026,
        'enrollment_type' => 'group',
        'dilp_group_id' => $group->id,
    ];

    $errors = $this->eligibilityService->validateEligibility($payload);

    expect($errors)->toHaveKey('liquidation_status');
    expect($errors['liquidation_status'])->toContain('DILP Liquidation Gatekeeping');
});

test('caps GIP duration to 6 months for high school graduates and allows up to 12 months for college graduates', function () {
    // 1. High School graduate attempting 12 months -> blocked
    $hsPayload = [
        'program_code' => 'GIP',
        'availment_year' => 2026,
        'educational_attainment' => 'High School Graduate',
        'internship_duration' => '1_year',
    ];

    $hsErrors = $this->eligibilityService->validateEligibility($hsPayload);
    expect($hsErrors)->toHaveKey('internship_duration');
    expect($hsErrors['internship_duration'])->toContain('capped at a maximum of 6 months');

    // 2. College graduate attempting 12 months -> allowed
    $collegePayload = [
        'program_code' => 'GIP',
        'availment_year' => 2026,
        'educational_attainment' => 'College Graduate',
        'internship_duration' => '1_year',
    ];

    $collegeErrors = $this->eligibilityService->validateEligibility($collegePayload);
    expect($collegeErrors)->not->toHaveKey('internship_duration');
});
