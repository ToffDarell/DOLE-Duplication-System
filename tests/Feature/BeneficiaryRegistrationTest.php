<?php

use App\Models\DilpGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', 'admin@dole-bukidnon.gov.ph')->first();
    $this->actingAs($this->admin);
});

test('can register TUPAD beneficiary', function () {
    $response = $this->post(route('beneficiaries.store'), [
        'program_code' => 'TUPAD',
        'availment_year' => 2026,
        'first_name' => 'PEDRO',
        'middle_name' => 'SANTOS',
        'last_name' => 'GARCIA',
        'date_of_birth' => '1990-04-12',
        'sex' => 'Male',
        'civil_status' => 'Married',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Casisang',
        'contact_number' => '09171112222',
        'epayment_account_no' => '09171112222',
        'beneficiary_type' => 'Underemployed',
        'average_monthly_income' => '5000-10000',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('beneficiaries', [
        'first_name' => 'PEDRO',
        'last_name' => 'GARCIA',
        'municipality' => 'Malaybalay City',
    ]);
});

test('can register SPES student beneficiary', function () {
    $response = $this->post(route('beneficiaries.store'), [
        'program_code' => 'SPES',
        'availment_year' => 2026,
        'first_name' => 'MARIA',
        'middle_name' => 'CLARA',
        'last_name' => 'REYES',
        'date_of_birth' => '2005-08-20',
        'sex' => 'Female',
        'civil_status' => 'Single',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
        'contact_number' => '09183334444',
        'is_student' => 1,
        'is_graduating_college' => 1,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('beneficiaries', [
        'first_name' => 'MARIA',
        'last_name' => 'REYES',
        'is_student' => 1,
        'is_graduating_college' => 1,
    ]);
});

test('can register DILP individual and group beneficiary', function () {
    $group = DilpGroup::create([
        'group_name' => 'Bukidnon Women Farmers Association',
        'municipality' => 'Lantapan',
        'barangay' => 'Poblacion',
    ]);

    $response = $this->post(route('beneficiaries.store'), [
        'program_code' => 'DILP',
        'availment_year' => 2026,
        'first_name' => 'JUANA',
        'middle_name' => 'LORENZO',
        'last_name' => 'TUPAZ',
        'date_of_birth' => '1985-11-05',
        'sex' => 'Female',
        'civil_status' => 'Married',
        'municipality' => 'Lantapan',
        'barangay' => 'Poblacion',
        'contact_number' => '09195556666',
        'enrollment_type' => 'group',
        'dilp_group_id' => $group->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('beneficiaries', [
        'first_name' => 'JUANA',
        'last_name' => 'TUPAZ',
    ]);
});

test('can register GIP intern beneficiary', function () {
    $response = $this->post(route('beneficiaries.store'), [
        'program_code' => 'GIP',
        'availment_year' => 2026,
        'first_name' => 'CARLOS',
        'middle_name' => 'IGNACIO',
        'last_name' => 'RAMOS',
        'date_of_birth' => '2001-03-15',
        'sex' => 'Male',
        'civil_status' => 'Single',
        'municipality' => 'Manolo Fortich',
        'barangay' => 'Tankulan',
        'contact_number' => '09177778888',
        'internship_duration' => '1_year',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('beneficiaries', [
        'first_name' => 'CARLOS',
        'last_name' => 'RAMOS',
    ]);
});
