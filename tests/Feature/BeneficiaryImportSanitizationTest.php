<?php

use App\Models\Beneficiary;
use App\Models\Program;
use App\Models\TupadProfile;
use App\Models\User;
use App\Services\BeneficiaryImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', 'admin@dole-bukidnon.gov.ph')->first();
    $this->actingAs($this->admin);
    $this->importService = app(BeneficiaryImportService::class);
});

test('edit view displays imported uppercase values in select dropdowns via fallback and case-matching', function () {
    $tupad = Program::where('code', 'TUPAD')->first();

    $beneficiary = Beneficiary::create([
        'full_name' => 'MARIA CLARA TEST',
        'first_name' => 'MARIA',
        'last_name' => 'CLARA',
        'date_of_birth' => '1993-10-12',
        'sex' => 'Female',
        'civil_status' => 'MARRIED',
        'government_id_type' => 'PhilSys ID',
        'government_id_number' => '15-203605045--9',
        'contact_number' => 'N/A',
        'province' => 'Bukidnon',
        'municipality' => 'Valencia City',
        'barangay' => 'Mt. Nebo',
        'district' => 'District IV',
    ]);

    $bp = $beneficiary->beneficiaryPrograms()->create([
        'program_id' => $tupad->id,
        'availment_year' => 2026,
        'status' => 'approved',
    ]);

    TupadProfile::create([
        'beneficiary_program_id' => $bp->id,
        'beneficiary_type' => 'INDIGENOUS PEOPLE',
        'occupation' => 'Crop Grower',
        'average_monthly_income' => '5000-10000',
        'dependent_name' => 'Jose Clara',
        'dependent_relationship' => 'HUSBAND',
    ]);

    $response = $this->get(route('beneficiaries.edit', $beneficiary));
    $response->assertOk();

    // Verify values rendered in the response HTML with selected state
    $response->assertSee('value="Married" selected', false);
    $response->assertSee('15-203605045--9');
    $response->assertSee('value="N/A"', false);
    $response->assertSee('Valencia City');
    $response->assertSee('Mt. Nebo');
    $response->assertSee('District IV');
    $response->assertSee('value="Indigenous People" selected', false);
    $response->assertSee('value="Husband" selected', false);
});

test('beneficiary controller update allows contact_number as N/A and updates TupadProfile', function () {
    $tupad = Program::where('code', 'TUPAD')->first();

    $beneficiary = Beneficiary::create([
        'full_name' => 'PEDRO PENDUKO',
        'first_name' => 'PEDRO',
        'last_name' => 'PENDUKO',
        'date_of_birth' => '1990-01-01',
        'sex' => 'Male',
        'civil_status' => 'Single',
        'contact_number' => 'N/A',
        'municipality' => 'Pangantucan',
        'barangay' => 'Mendis',
    ]);

    $beneficiary->beneficiaryPrograms()->create([
        'program_id' => $tupad->id,
        'availment_year' => 2026,
        'status' => 'approved',
    ]);

    $response = $this->put(route('beneficiaries.update', $beneficiary), [
        'first_name' => 'PEDRO',
        'last_name' => 'PENDUKO',
        'date_of_birth' => '1990-01-01',
        'sex' => 'Male',
        'civil_status' => 'Single',
        'government_id_type' => 'PhilSys ID',
        'government_id_number' => '1234-5678-9012',
        'contact_number' => 'N/A',
        'province' => 'Bukidnon',
        'municipality' => 'Pangantucan',
        'barangay' => 'Mendis',
        'district' => 'District 4',
        'type_of_beneficiary' => 'Self-Employed',
        'monthly_income' => '₱5,000 - ₱10,000',
        'occupation' => 'Farmer',
        'dependent' => 'Juana Penduko',
        'relationship_dependent' => 'Wife',
    ]);

    $response->assertRedirect(route('beneficiaries.show', $beneficiary));

    $beneficiary->refresh();
    expect($beneficiary->contact_number)->toBe('N/A');
    expect($beneficiary->province)->toBe('Bukidnon');
    expect($beneficiary->district)->toBe('District 4');

    $tupadProfile = TupadProfile::whereHas('beneficiaryProgram', function ($q) use ($beneficiary) {
        $q->where('beneficiary_id', $beneficiary->id);
    })->first();

    expect($tupadProfile)->not->toBeNull();
    expect($tupadProfile->beneficiary_type)->toBe('Self-Employed');
    expect($tupadProfile->occupation)->toBe('Farmer');
    expect($tupadProfile->dependent_name)->toBe('Juana Penduko');
    expect($tupadProfile->dependent_relationship)->toBe('Wife');
});
