<?php

use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\Program;
use App\Services\DuplicateDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed programs
    Program::firstOrCreate(['code' => 'TUPAD'], ['name' => 'Tulong Panghanapbuhay sa Ating Disadvantaged/Displaced Workers']);
    Program::firstOrCreate(['code' => 'SPES'], ['name' => 'Special Program for Employment of Students']);
});

/*
|--------------------------------------------------------------------------
| Household Duplicate Detection Tests
|--------------------------------------------------------------------------
|
| Scenario: Two siblings (Toff Darell Vergara & Kleent Harris Vergara)
| share the same surname and live in the same Barangay, BUT reside at
| different physical addresses (Sitio A vs Sitio B). Under DOLE TUPAD
| rules, they are separate households and are ALLOWED to avail.
|
*/

test('siblings with same surname and same barangay but different addresses trigger medium-confidence household flag', function () {
    $tupad = Program::where('code', 'TUPAD')->first();

    // Existing sibling already enrolled in TUPAD
    $existingSibling = Beneficiary::create([
        'full_name' => 'VERGARA, TOFF DARELL',
        'first_name' => 'TOFF DARELL',
        'last_name' => 'VERGARA',
        'date_of_birth' => '2000-01-15',
        'sex' => 'Male',
        'barangay' => 'Casisang',
        'municipality' => 'Malaybalay City',
        'address' => 'Sitio A, Purok 3',
    ]);

    BeneficiaryProgram::create([
        'beneficiary_id' => $existingSibling->id,
        'program_id' => $tupad->id,
        'availment_year' => (int) date('Y'),
        'status' => 'approved',
    ]);

    // New sibling payload — different first name, different address, same surname + barangay
    $newSiblingPayload = [
        'first_name' => 'KLEENT HARRIS',
        'last_name' => 'VERGARA',
        'date_of_birth' => '2002-06-20',
        'sex' => 'Male',
        'barangay' => 'Casisang',
        'municipality' => 'Malaybalay City',
        'address' => 'Sitio B, Purok 5',
        'program_code' => 'TUPAD',
        'availment_year' => (int) date('Y'),
    ];

    $service = app(DuplicateDetectionService::class);

    // 1. Identity check should NOT flag them (different people entirely)
    $identityResult = $service->checkDuplicates($newSiblingPayload);
    // They might or might not meet the identity threshold — but let's verify household
    // detection works independently regardless.

    // 2. Household check SHOULD flag them
    $householdResult = $service->checkHouseholdDuplicates($newSiblingPayload);

    expect($householdResult['has_household_flags'])->toBeTrue()
        ->and($householdResult['flags'])->toHaveCount(1);

    $flag = $householdResult['flags'][0];

    // Score should be 50 (different addresses = medium-low, not hard block)
    expect($flag['match_score'])->toBe(50)
        ->and($flag['match_type'])->toBe('medium')
        ->and($flag['household_match_flag'])->toBeTrue()
        ->and($flag['matched_beneficiary_id'])->toBe($existingSibling->id);

    // Matched fields should contain household-specific keys
    expect($flag['matched_fields'])->toHaveKeys([
        'household_surname',
        'household_program',
        'household_address',
        'household_action',
    ]);

    // Address field should indicate different addresses
    expect($flag['matched_fields']['household_address'])->toContain('Different addresses');
});

test('siblings at the SAME address trigger higher household score', function () {
    $tupad = Program::where('code', 'TUPAD')->first();

    $existing = Beneficiary::create([
        'full_name' => 'VERGARA, TOFF DARELL',
        'first_name' => 'TOFF DARELL',
        'last_name' => 'VERGARA',
        'date_of_birth' => '2000-01-15',
        'sex' => 'Male',
        'barangay' => 'Casisang',
        'municipality' => 'Malaybalay City',
        'address' => 'Purok 3, Sitio Mahayag',
    ]);

    BeneficiaryProgram::create([
        'beneficiary_id' => $existing->id,
        'program_id' => $tupad->id,
        'availment_year' => (int) date('Y'),
        'status' => 'approved',
    ]);

    $payload = [
        'first_name' => 'KLEENT HARRIS',
        'last_name' => 'VERGARA',
        'date_of_birth' => '2002-06-20',
        'sex' => 'Male',
        'barangay' => 'Casisang',
        'municipality' => 'Malaybalay City',
        'address' => 'Purok 3, Sitio Mahayag', // SAME address
        'program_code' => 'TUPAD',
        'availment_year' => (int) date('Y'),
    ];

    $service = app(DuplicateDetectionService::class);
    $result = $service->checkHouseholdDuplicates($payload);

    expect($result['has_household_flags'])->toBeTrue();

    $flag = $result['flags'][0];

    // Same address = higher score (65) indicating likely same household
    expect($flag['match_score'])->toBe(65)
        ->and($flag['matched_fields']['household_address'])->toContain('SAME ADDRESS');
});

test('household check does NOT trigger for non-TUPAD programs', function () {
    $spes = Program::where('code', 'SPES')->first();

    $existing = Beneficiary::create([
        'full_name' => 'VERGARA, TOFF DARELL',
        'first_name' => 'TOFF DARELL',
        'last_name' => 'VERGARA',
        'date_of_birth' => '2000-01-15',
        'sex' => 'Male',
        'barangay' => 'Casisang',
        'municipality' => 'Malaybalay City',
        'address' => 'Sitio A',
    ]);

    BeneficiaryProgram::create([
        'beneficiary_id' => $existing->id,
        'program_id' => $spes->id,
        'availment_year' => (int) date('Y'),
        'status' => 'approved',
    ]);

    $payload = [
        'first_name' => 'KLEENT HARRIS',
        'last_name' => 'VERGARA',
        'date_of_birth' => '2002-06-20',
        'sex' => 'Male',
        'barangay' => 'Casisang',
        'municipality' => 'Malaybalay City',
        'address' => 'Sitio B',
        'program_code' => 'SPES',
        'availment_year' => (int) date('Y'),
    ];

    $service = app(DuplicateDetectionService::class);
    $result = $service->checkHouseholdDuplicates($payload);

    // Household check is TUPAD-only
    expect($result['has_household_flags'])->toBeFalse();
});

test('household check does NOT trigger for different barangays', function () {
    $tupad = Program::where('code', 'TUPAD')->first();

    $existing = Beneficiary::create([
        'full_name' => 'VERGARA, TOFF DARELL',
        'first_name' => 'TOFF DARELL',
        'last_name' => 'VERGARA',
        'date_of_birth' => '2000-01-15',
        'sex' => 'Male',
        'barangay' => 'Casisang',
        'municipality' => 'Malaybalay City',
        'address' => 'Sitio A',
    ]);

    BeneficiaryProgram::create([
        'beneficiary_id' => $existing->id,
        'program_id' => $tupad->id,
        'availment_year' => (int) date('Y'),
        'status' => 'approved',
    ]);

    $payload = [
        'first_name' => 'KLEENT HARRIS',
        'last_name' => 'VERGARA',
        'date_of_birth' => '2002-06-20',
        'sex' => 'Male',
        'barangay' => 'Sumpong', // Different barangay
        'municipality' => 'Malaybalay City',
        'address' => 'Sitio B',
        'program_code' => 'TUPAD',
        'availment_year' => (int) date('Y'),
    ];

    $service = app(DuplicateDetectionService::class);
    $result = $service->checkHouseholdDuplicates($payload);

    // Different barangay = no household flag
    expect($result['has_household_flags'])->toBeFalse();
});

test('household check does NOT trigger when existing sibling has no TUPAD enrollment for same year', function () {
    $tupad = Program::where('code', 'TUPAD')->first();

    $existing = Beneficiary::create([
        'full_name' => 'VERGARA, TOFF DARELL',
        'first_name' => 'TOFF DARELL',
        'last_name' => 'VERGARA',
        'date_of_birth' => '2000-01-15',
        'sex' => 'Male',
        'barangay' => 'Casisang',
        'municipality' => 'Malaybalay City',
        'address' => 'Sitio A',
    ]);

    // Enrolled in TUPAD but for PREVIOUS year
    BeneficiaryProgram::create([
        'beneficiary_id' => $existing->id,
        'program_id' => $tupad->id,
        'availment_year' => (int) date('Y') - 1,
        'status' => 'approved',
    ]);

    $payload = [
        'first_name' => 'KLEENT HARRIS',
        'last_name' => 'VERGARA',
        'date_of_birth' => '2002-06-20',
        'sex' => 'Male',
        'barangay' => 'Casisang',
        'municipality' => 'Malaybalay City',
        'address' => 'Sitio B',
        'program_code' => 'TUPAD',
        'availment_year' => (int) date('Y'),
    ];

    $service = app(DuplicateDetectionService::class);
    $result = $service->checkHouseholdDuplicates($payload);

    // Previous year enrollment = no flag for current year
    expect($result['has_household_flags'])->toBeFalse();
});
