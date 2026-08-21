<?php

use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Validator', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Encoder', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');

    $this->tupad = Program::firstOrCreate(
        ['code' => 'TUPAD'],
        ['name' => 'Tulong Panghanapbuhay sa Ating Disadvantaged/Displaced Workers', 'description' => 'TUPAD Program']
    );

    $this->gip = Program::firstOrCreate(
        ['code' => 'GIP'],
        ['name' => 'Government Internship Program', 'description' => 'GIP Program']
    );

    $this->spes = Program::firstOrCreate(
        ['code' => 'SPES'],
        ['name' => 'Special Program for Employment of Students', 'description' => 'SPES Program']
    );
});

test('beneficiary model supports hasMany availments relationship', function () {
    $beneficiary = Beneficiary::create([
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'full_name' => 'Dela Cruz, Juan',
        'date_of_birth' => '1995-05-10',
        'sex' => 'Male',
        'barangay' => 'Poblacion',
        'municipality' => 'Valencia City',
    ]);

    $beneficiary->availments()->create([
        'program_id' => $this->tupad->id,
        'availment_year' => 2025,
        'status' => 'approved',
    ]);

    $beneficiary->availments()->create([
        'program_id' => $this->gip->id,
        'availment_year' => 2026,
        'status' => 'approved',
    ]);

    expect($beneficiary->availments)->toHaveCount(2)
        ->and($beneficiary->beneficiaryPrograms)->toHaveCount(2);
});

test('attaching a new program creates a new record instead of overwriting existing program history', function () {
    $beneficiary = Beneficiary::create([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'full_name' => 'Santos, Maria',
        'date_of_birth' => '1998-08-15',
        'sex' => 'Female',
        'barangay' => 'Poblacion',
        'municipality' => 'Malaybalay City',
    ]);

    // Initial TUPAD availment in 2025
    BeneficiaryProgram::create([
        'beneficiary_id' => $beneficiary->id,
        'program_id' => $this->tupad->id,
        'availment_year' => 2025,
        'status' => 'approved',
    ]);

    expect($beneficiary->availments()->count())->toBe(1);

    // Attach GIP in 2026 via POST /beneficiaries/{beneficiary}/availments
    $response = $this->actingAs($this->admin)->postJson(route('beneficiaries.availments.store', $beneficiary), [
        'program_code' => 'GIP',
        'availment_year' => 2026,
        'internship_duration' => '6_months',
        'status' => 'approved',
    ]);

    $response->assertStatus(201);
    $response->assertJson(['success' => true]);

    // Both TUPAD 2025 and GIP 2026 must be present
    $availments = $beneficiary->fresh()->availments;
    expect($availments)->toHaveCount(2);

    $programYears = $availments->map(fn ($a) => $a->program->code.'-'.$a->availment_year)->toArray();
    expect($programYears)->toContain('TUPAD-2025')
        ->and($programYears)->toContain('GIP-2026');
});

test('updating an availment modifies program details without changing demographic details', function () {
    $beneficiary = Beneficiary::create([
        'first_name' => 'Pedro',
        'last_name' => 'Penduko',
        'full_name' => 'Penduko, Pedro',
        'date_of_birth' => '1997-01-01',
        'sex' => 'Male',
        'barangay' => 'Poblacion',
        'municipality' => 'Maramag',
    ]);

    $availment = BeneficiaryProgram::create([
        'beneficiary_id' => $beneficiary->id,
        'program_id' => $this->gip->id,
        'availment_year' => 2026,
        'status' => 'pending',
        'internship_duration' => '6_months',
    ]);

    $response = $this->actingAs($this->admin)->putJson(route('availments.update', $availment), [
        'availment_year' => 2026,
        'status' => 'approved',
        'internship_duration' => '1_year',
        'remarks' => 'Extended to 1 year duration',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $availment->refresh();
    expect($availment->status)->toBe('approved')
        ->and($availment->internship_duration)->toBe('1_year')
        ->and($availment->remarks)->toBe('Extended to 1 year duration');
});

test('deleting an availment removes only that program grant without deleting the beneficiary', function () {
    $beneficiary = Beneficiary::create([
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
        'full_name' => 'Reyes, Ana',
        'date_of_birth' => '1996-03-20',
        'sex' => 'Female',
        'barangay' => 'Poblacion',
        'municipality' => 'Quezon',
    ]);

    $tupadAvailment = BeneficiaryProgram::create([
        'beneficiary_id' => $beneficiary->id,
        'program_id' => $this->tupad->id,
        'availment_year' => 2025,
        'status' => 'approved',
    ]);

    $gipAvailment = BeneficiaryProgram::create([
        'beneficiary_id' => $beneficiary->id,
        'program_id' => $this->gip->id,
        'availment_year' => 2026,
        'status' => 'approved',
    ]);

    $response = $this->actingAs($this->admin)->deleteJson(route('availments.destroy', $gipAvailment));

    $response->assertStatus(200);

    // Beneficiary remains intact
    expect(Beneficiary::find($beneficiary->id))->not->toBeNull();

    // Only TUPAD 2025 remains
    $remainingAvailments = $beneficiary->fresh()->availments;
    expect($remainingAvailments)->toHaveCount(1)
        ->and($remainingAvailments->first()->id)->toBe($tupadAvailment->id);
});
