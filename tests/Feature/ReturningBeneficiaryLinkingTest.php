<?php

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\Program;
use App\Models\TupadProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', 'admin@dole-bukidnon.gov.ph')->first();
    $this->actingAs($this->admin);
});

test('checkDuplicate identifies returning beneficiary from previous year without blocking as hard error', function () {
    $beneficiary = Beneficiary::create([
        'full_name' => 'MARIA SANTOS CLARA',
        'first_name' => 'MARIA',
        'middle_name' => 'SANTOS',
        'last_name' => 'CLARA',
        'date_of_birth' => '1992-04-12',
        'sex' => 'Female',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Casisang',
        'contact_number' => '09171234567',
    ]);

    $tupad = Program::where('code', 'TUPAD')->first();

    // Availed in 2025
    BeneficiaryProgram::create([
        'beneficiary_id' => $beneficiary->id,
        'program_id' => $tupad->id,
        'availment_year' => 2025,
        'status' => 'approved',
    ]);

    // Check duplicate for 2026 availment
    $response = $this->postJson(route('beneficiaries.check-duplicate'), [
        'first_name' => 'MARIA',
        'middle_name' => 'SANTOS',
        'last_name' => 'CLARA',
        'date_of_birth' => '1992-04-12',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Casisang',
        'program_code' => 'TUPAD',
        'availment_year' => 2026,
    ]);

    $response->assertStatus(409);
    $response->assertJson([
        'has_duplicates' => true,
        'is_returning_beneficiary' => true,
        'status' => 'returning_beneficiary_detected',
        'code' => 'RETURNING_BENEFICIARY',
        'existing_beneficiary_id' => $beneficiary->id,
        'last_availment' => 'TUPAD 2025',
    ]);
});

test('submitting with existing_beneficiary_id links new program availment directly to master profile without creating new beneficiary', function () {
    $beneficiary = Beneficiary::create([
        'full_name' => 'PEDRO PENDUKO REYES',
        'first_name' => 'PEDRO',
        'middle_name' => 'PENDUKO',
        'last_name' => 'REYES',
        'date_of_birth' => '1988-08-20',
        'sex' => 'Male',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
        'contact_number' => '09171112233',
        'address' => 'Old Address',
    ]);

    $tupad = Program::where('code', 'TUPAD')->first();

    // Previous availment in 2025
    BeneficiaryProgram::create([
        'beneficiary_id' => $beneficiary->id,
        'program_id' => $tupad->id,
        'availment_year' => 2025,
        'status' => 'approved',
    ]);

    $initialBeneficiaryCount = Beneficiary::count();

    // Submitting 2026 application linked to existing beneficiary ID
    $payload = [
        'existing_beneficiary_id' => $beneficiary->id,
        'first_name' => 'PEDRO',
        'middle_name' => 'PENDUKO',
        'last_name' => 'REYES',
        'date_of_birth' => '1988-08-20',
        'sex' => 'Male',
        'civil_status' => 'Married',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
        'address' => 'Purok 5 Updated Address',
        'contact_number' => '09189998877',
        'program_code' => 'TUPAD',
        'availment_year' => 2026,
        'project_location_barangay' => 'Poblacion',
        'project_location_municipality' => 'Valencia City',
        'occupation' => 'Carpenter',
    ];

    $response = $this->post(route('beneficiaries.store'), $payload);

    $response->assertRedirect(route('beneficiaries.show', $beneficiary));
    $response->assertSessionHas('success');

    // Verify master table count has NOT increased
    expect(Beneficiary::count())->toBe($initialBeneficiaryCount);

    // Verify master record details updated
    $beneficiary->refresh();
    expect($beneficiary->contact_number)->toBe('09189998877');
    expect($beneficiary->address)->toBe('Purok 5 Updated Address');
    expect($beneficiary->civil_status)->toBe('Married');

    // Verify new BeneficiaryProgram record created for 2026
    $programs = $beneficiary->beneficiaryPrograms;
    expect($programs)->toHaveCount(2);

    $program2026 = $programs->firstWhere('availment_year', 2026);
    expect($program2026)->not->toBeNull();
    expect($program2026->status)->toBe('approved');
    expect($program2026->program_id)->toBe($tupad->id);

    // Verify TupadProfile created
    $tupadProfile = TupadProfile::where('beneficiary_program_id', $program2026->id)->first();
    expect($tupadProfile)->not->toBeNull();
    expect($tupadProfile->occupation)->toBe('Carpenter');

    // Verify AuditLog entry
    $auditLog = AuditLog::where('action', 'link_program')
        ->where('model_id', $beneficiary->id)
        ->first();
    expect($auditLog)->not->toBeNull();
    expect($auditLog->description)->toContain('Linked 2026 TUPAD availment to existing Beneficiary');
});

test('duplicate check blocks same-year re-application with 422 unless calamity override is active', function () {
    $beneficiary = Beneficiary::create([
        'full_name' => 'JUAN BAUTISTA DELA CRUZ',
        'first_name' => 'JUAN',
        'middle_name' => 'BAUTISTA',
        'last_name' => 'DELA CRUZ',
        'date_of_birth' => '1990-01-15',
        'sex' => 'Male',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Casisang',
    ]);

    $tupad = Program::where('code', 'TUPAD')->first();
    $currentYear = (int) date('Y');

    // Already enrolled in TUPAD for current year
    BeneficiaryProgram::create([
        'beneficiary_id' => $beneficiary->id,
        'program_id' => $tupad->id,
        'availment_year' => $currentYear,
        'status' => 'approved',
    ]);

    // Check duplicate for same year without calamity override -> 409
    $response = $this->postJson(route('beneficiaries.check-duplicate'), [
        'first_name' => 'JUAN',
        'middle_name' => 'BAUTISTA',
        'last_name' => 'DELA CRUZ',
        'date_of_birth' => '1990-01-15',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Casisang',
        'program_code' => 'TUPAD',
        'availment_year' => $currentYear,
        'is_calamity_override' => false,
    ]);

    $response->assertStatus(409);
    $response->assertJson([
        'code' => 'SAME_YEAR_AVAILMENT_BLOCKED',
        'is_same_year_conflict' => true,
    ]);
    expect($response->json('message'))->toContain("Beneficiary has already availed of TUPAD for calendar year {$currentYear}");

    // With calamity override -> bypasses same-year block
    $overrideResponse = $this->postJson(route('beneficiaries.check-duplicate'), [
        'first_name' => 'JUAN',
        'middle_name' => 'BAUTISTA',
        'last_name' => 'DELA CRUZ',
        'date_of_birth' => '1990-01-15',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Casisang',
        'program_code' => 'TUPAD',
        'availment_year' => $currentYear,
        'is_calamity_override' => true,
        'calamity_remarks' => 'Flash flood emergency relief re-availment',
    ]);

    // Should not return 409 same-year block
    expect($overrideResponse->json('code'))->not->toBe('SAME_YEAR_AVAILMENT_BLOCKED');
});
