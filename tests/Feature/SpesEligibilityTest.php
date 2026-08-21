<?php

use App\Models\Beneficiary;
use App\Models\Program;
use App\Models\User;
use App\Services\EligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Validator', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Encoder', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');

    $this->spes = Program::firstOrCreate(
        ['code' => 'SPES'],
        ['name' => 'Special Program for Employment of Students', 'description' => 'SPES Program']
    );

    $this->eligibilityService = app(EligibilityService::class);
});

test('spes eligibility passes for graduating college student John Claude Bendanillo', function () {
    $payload = [
        'first_name' => 'John Claude',
        'last_name' => 'Bendanillo',
        'date_of_birth' => '2000-10-19', // 25 years old
        'sex' => 'Male',
        'program_code' => 'SPES',
        'availment_year' => 2026,
        'barangay' => 'Poblacion',
        'municipality' => 'Valencia City',
        'is_graduating_student' => true,
        'is_student' => false,
        'is_osy' => false,
    ];

    $errors = $this->eligibilityService->validateEligibility($payload);

    expect($errors)->toBeEmpty();
});

test('spes eligibility service checkSpesEligibility method accepts is_graduating_student', function () {
    $payload = [
        'date_of_birth' => '2000-10-19',
        'is_graduating_student' => true,
    ];

    $errors = $this->eligibilityService->checkSpesEligibility($payload);

    expect($errors)->toBeEmpty();
});

test('spes eligibility fails if applicant is not enrolled, not osy, and not graduating', function () {
    $payload = [
        'first_name' => 'Non',
        'last_name' => 'Student',
        'date_of_birth' => '2000-10-19',
        'sex' => 'Male',
        'program_code' => 'SPES',
        'availment_year' => 2026,
        'barangay' => 'Poblacion',
        'municipality' => 'Valencia City',
        'is_graduating_student' => false,
        'is_student' => false,
        'is_osy' => false,
    ];

    $errors = $this->eligibilityService->validateEligibility($payload);

    expect($errors)->toHaveKey('is_student')
        ->and($errors['is_student'])->toContain('SPES applicants must be currently enrolled students, registered out-of-school youth, or graduating students');
});

test('spes registration endpoint successfully creates record for graduating college student', function () {
    $response = $this->actingAs($this->admin)->postJson(route('beneficiaries.store'), [
        'first_name' => 'John Claude',
        'middle_name' => 'T',
        'last_name' => 'Bendanillo',
        'date_of_birth' => '2000-10-19',
        'sex' => 'Male',
        'civil_status' => 'Single',
        'contact_number' => '09171234567',
        'address' => 'Purok 1',
        'barangay' => 'Poblacion',
        'municipality' => 'Valencia City',
        'program_code' => 'SPES',
        'availment_year' => 2026,
        'is_graduating_student' => 1,
    ]);

    $response->assertStatus(201);
    $response->assertJson(['success' => true]);

    $beneficiary = Beneficiary::where('first_name', 'John Claude')
        ->where('last_name', 'Bendanillo')
        ->first();

    expect($beneficiary)->not->toBeNull()
        ->and($beneficiary->is_graduating_college)->toBeTrue()
        ->and($beneficiary->is_student)->toBeTrue();
});

test('check duplicate pre-check does not return eligibility restriction for graduating student', function () {
    $response = $this->actingAs($this->admin)->postJson(route('beneficiaries.check-duplicate'), [
        'first_name' => 'John Claude',
        'last_name' => 'Bendanillo',
        'date_of_birth' => '2000-10-19',
        'program_code' => 'SPES',
        'availment_year' => 2026,
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
        'address' => 'Purok 1',
        'is_graduating_student' => 1,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'has_duplicates' => false,
    ]);
});
