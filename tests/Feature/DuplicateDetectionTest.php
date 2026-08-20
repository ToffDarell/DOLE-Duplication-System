<?php

use App\Models\Beneficiary;
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

test('duplicate engine detects exact match and scores 100 points', function () {
    $existing = Beneficiary::create([
        'full_name' => 'JUAN MARCOS DELA CRUZ',
        'first_name' => 'JUAN',
        'middle_name' => 'MARCOS',
        'last_name' => 'DELA CRUZ',
        'date_of_birth' => '1995-05-15',
        'sex' => 'Male',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Casisang',
        'contact_number' => '09171234567',
        'government_id_number' => '123456789',
    ]);

    $payload = [
        'first_name' => 'JUAN',
        'middle_name' => 'MARCOS',
        'last_name' => 'DELA CRUZ',
        'date_of_birth' => '1995-05-15',
        'sex' => 'Male',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Casisang',
        'contact_number' => '09171234567',
        'government_id_number' => '123456789',
    ];

    $result = $this->duplicateService->checkDuplicates($payload);

    expect($result['has_duplicates'])->toBeTrue();
    expect($result['is_exact'])->toBeTrue();
    expect($result['max_score'])->toBe(100);
});

test('duplicate engine calculates fuzzy phonetic match scores correctly', function () {
    Beneficiary::create([
        'full_name' => 'JONAH VALDEZ SANTOS',
        'first_name' => 'JONAH',
        'middle_name' => 'VALDEZ',
        'last_name' => 'SANTOS',
        'date_of_birth' => '1990-10-20',
        'sex' => 'Female',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
        'contact_number' => '09189998888',
    ]);

    // Similar name, same DOB, same address, same contact -> should score >= 70
    $payload = [
        'first_name' => 'JONA',
        'middle_name' => 'VALDEZ',
        'last_name' => 'SANTOS',
        'date_of_birth' => '1990-10-20',
        'sex' => 'Female',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
        'contact_number' => '09189998888',
    ];

    $result = $this->duplicateService->checkDuplicates($payload);

    expect($result['has_duplicates'])->toBeTrue();
    expect($result['max_score'])->toBeGreaterThanOrEqual(70);
});

test('eligibility rules block underage TUPAD applicants and enrolled students', function () {
    $payload = [
        'date_of_birth' => now()->subYears(16)->format('Y-m-d'), // 16 years old
        'is_student' => true,
        'program_code' => 'TUPAD',
        'availment_year' => (int) date('Y'),
    ];

    $errors = $this->eligibilityService->validateEligibility($payload);

    expect($errors)->toHaveKey('date_of_birth');
    expect($errors)->toHaveKey('is_student');
});

test('eligibility rules enforce GIP lifetime limit', function () {
    $beneficiary = Beneficiary::create([
        'full_name' => 'MARIA CLARA DE LOS SANTOS',
        'first_name' => 'MARIA',
        'last_name' => 'DE LOS SANTOS',
        'date_of_birth' => '2000-01-01',
        'sex' => 'Female',
        'municipality' => 'Manolo Fortich',
        'barangay' => 'Tankulan',
    ]);

    $gip = Program::where('code', 'GIP')->first();

    $beneficiary->beneficiaryPrograms()->create([
        'program_id' => $gip->id,
        'availment_year' => 2024,
        'status' => 'approved',
    ]);

    $payload = [
        'date_of_birth' => '2000-01-01',
        'program_code' => 'GIP',
        'availment_year' => 2026,
    ];

    $errors = $this->eligibilityService->validateEligibility($payload, $beneficiary);

    expect($errors)->toHaveKey('program_code');
    expect($errors['program_code'])->toContain('ONCE IN A LIFETIME');
});
