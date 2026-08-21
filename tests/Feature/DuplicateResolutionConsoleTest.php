<?php

use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\DuplicateFlag;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', 'admin@dole-bukidnon.gov.ph')->first();
    $this->tupad = Program::where('code', 'TUPAD')->first();
});

test('duplicate flags console loads successfully and displays flags', function () {
    $ben1 = Beneficiary::create([
        'full_name' => 'JOHN DARELL DELA CRUZ',
        'first_name' => 'JOHN DARELL',
        'last_name' => 'DELA CRUZ',
        'sex' => 'Male',
        'date_of_birth' => '1999-08-13',
        'civil_status' => 'Single',
        'contact_number' => '09171234567',
        'barangay' => 'Managok',
        'municipality' => 'Malaybalay City',
        'province' => 'Bukidnon',
    ]);

    $ben2 = Beneficiary::create([
        'full_name' => 'TOPE DELA CRUZ',
        'first_name' => 'TOPE',
        'last_name' => 'DELA CRUZ',
        'sex' => 'Male',
        'date_of_birth' => '2001-09-17',
        'civil_status' => 'Single',
        'contact_number' => '09177654321',
        'barangay' => 'Managok',
        'municipality' => 'Malaybalay City',
        'province' => 'Bukidnon',
    ]);

    $flag = DuplicateFlag::create([
        'beneficiary_id' => $ben1->id,
        'matched_beneficiary_id' => $ben2->id,
        'match_score' => 50,
        'match_type' => 'medium',
        'household_match_flag' => true,
        'matched_fields' => [
            'household_surname' => 'Same Surname: Dela Cruz in Barangay Managok',
        ],
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin)->get(route('duplicates.index'));

    $response->assertOk()
        ->assertSee('Duplicate Resolution Console')
        ->assertSee('JOHN DARELL')
        ->assertSee('TOPE')
        ->assertSee('Household Verification')
        ->assertSee('50%');
});

test('validator can resolve a duplicate flag from the console', function () {
    $ben1 = Beneficiary::create([
        'full_name' => 'MARIA SANTOS',
        'first_name' => 'MARIA',
        'last_name' => 'SANTOS',
        'sex' => 'Female',
        'date_of_birth' => '1995-04-12',
        'civil_status' => 'Single',
        'contact_number' => '09171112233',
        'barangay' => 'Poblacion',
        'municipality' => 'Valencia City',
        'province' => 'Bukidnon',
    ]);

    $ben2 = Beneficiary::create([
        'full_name' => 'MARIA SANTOS-CRUZ',
        'first_name' => 'MARIA',
        'last_name' => 'SANTOS-CRUZ',
        'sex' => 'Female',
        'date_of_birth' => '1995-04-12',
        'civil_status' => 'Married',
        'contact_number' => '09179998877',
        'barangay' => 'Poblacion',
        'municipality' => 'Valencia City',
        'province' => 'Bukidnon',
    ]);

    $flag = DuplicateFlag::create([
        'beneficiary_id' => $ben1->id,
        'matched_beneficiary_id' => $ben2->id,
        'match_score' => 75,
        'match_type' => 'high',
        'household_match_flag' => false,
        'matched_fields' => ['first_name' => 'Exact Match', 'last_name' => 'Compound Match'],
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin)->post(route('duplicates.resolve', $flag), [
        'status' => 'resolved_not_duplicate',
        'remarks' => 'Verified distinct individuals via government IDs and Barangay Clearance',
    ]);

    $response->assertRedirect(route('duplicates.index'))
        ->assertSessionHas('success');

    $flag->refresh();
    expect($flag->status)->toBe('resolved_not_duplicate')
        ->and($flag->remarks)->toBe('Verified distinct individuals via government IDs and Barangay Clearance')
        ->and($flag->reviewed_by)->toBe($this->admin->id);
});

test('applicant with duplicate or same-year warning can be saved directly to duplicate console for review', function () {
    // Existing beneficiary enrolled in TUPAD 2026
    $existing = Beneficiary::create([
        'full_name' => 'VERGARA, TOFF BARON',
        'first_name' => 'TOFF BARON',
        'last_name' => 'VERGARA',
        'sex' => 'Male',
        'date_of_birth' => '2004-10-13',
        'civil_status' => 'Single',
        'contact_number' => '09171112233',
        'address' => 'Purok 3',
        'barangay' => 'Camp 1',
        'municipality' => 'Maramag',
    ]);

    BeneficiaryProgram::create([
        'beneficiary_id' => $existing->id,
        'program_id' => $this->tupad->id,
        'availment_year' => 2026,
        'status' => 'approved',
    ]);

    // Submitting a new applicant (sibling / separate home) with log_for_review = 1
    $response = $this->actingAs($this->admin)->postJson(route('beneficiaries.store'), [
        'first_name' => 'TOFF BARON',
        'last_name' => 'VERGARA',
        'sex' => 'Male',
        'date_of_birth' => '2004-10-13',
        'civil_status' => 'Single',
        'contact_number' => '09179998877',
        'address' => 'Purok 5',
        'barangay' => 'Camp 1',
        'municipality' => 'Maramag',
        'program_code' => 'TUPAD',
        'availment_year' => 2026,
        'log_for_review' => 1,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'redirect_url' => route('duplicates.index'),
        ]);

    // Verify record and duplicate flag created in DB
    $newBen = Beneficiary::where('address', 'Purok 5')->first();
    expect($newBen)->not->toBeNull();

    $flag = DuplicateFlag::where('beneficiary_id', $newBen->id)->first();
    expect($flag)->not->toBeNull()
        ->and($flag->status)->toBe('pending')
        ->and($flag->matched_beneficiary_id)->toBe($existing->id)
        ->and($flag->matched_fields)->toHaveKey('purok_insight');
});

test('validator can reject a duplicate or same-household flag from the console', function () {
    $ben1 = Beneficiary::create([
        'full_name' => 'PEDRO SANTOS',
        'first_name' => 'PEDRO',
        'last_name' => 'SANTOS',
        'sex' => 'Male',
        'date_of_birth' => '1989-02-19',
        'civil_status' => 'Single',
        'contact_number' => '09171234567',
        'address' => 'Zone 1',
        'barangay' => 'Casisang',
        'municipality' => 'Malaybalay City',
    ]);

    $bp = BeneficiaryProgram::create([
        'beneficiary_id' => $ben1->id,
        'program_id' => $this->tupad->id,
        'availment_year' => 2026,
        'status' => 'pending',
    ]);

    $ben2 = Beneficiary::create([
        'full_name' => 'MARIA SANTOS',
        'first_name' => 'MARIA',
        'last_name' => 'SANTOS',
        'sex' => 'Female',
        'date_of_birth' => '1999-01-19',
        'civil_status' => 'Single',
        'contact_number' => '09177654321',
        'address' => 'Zone 1',
        'barangay' => 'Casisang',
        'municipality' => 'Malaybalay City',
    ]);

    $flag = DuplicateFlag::create([
        'beneficiary_id' => $ben1->id,
        'matched_beneficiary_id' => $ben2->id,
        'match_score' => 65,
        'match_type' => 'medium',
        'household_match_flag' => true,
        'matched_fields' => [
            'household_surname' => 'Same Surname: Santos in Barangay Casisang',
            'household_address' => 'Identical Address: Zone 1',
        ],
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin)->post(route('duplicates.resolve', $flag), [
        'status' => 'resolved_duplicate',
        'remarks' => 'Confirmed same physical household living at Zone 1 — TUPAD 1-beneficiary-per-household policy applied',
    ]);

    $response->assertRedirect(route('duplicates.index'))
        ->assertSessionHas('success');

    $flag->refresh();
    expect($flag->status)->toBe('resolved_duplicate')
        ->and($flag->remarks)->toContain('Confirmed same physical household')
        ->and($flag->reviewed_by)->toBe($this->admin->id);

    // Pedro (the duplicate) is completely removed from database
    expect(Beneficiary::find($ben1->id))->toBeNull();

    // Maria (the first/original beneficiary) remains registered and intact
    expect(Beneficiary::find($ben2->id))->not->toBeNull()
        ->and(Beneficiary::find($ben2->id)->full_name)->toBe('MARIA SANTOS');
});
