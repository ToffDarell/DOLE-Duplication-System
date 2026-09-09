<?php

use App\Imports\BeneficiariesImport;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\DuplicateFlag;
use App\Models\Program;
use App\Models\User;
use App\Services\DuplicateDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Validator', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');

    $this->tupad = Program::firstOrCreate(
        ['code' => 'TUPAD'],
        ['name' => 'Tulong Panghanapbuhay sa Ating Disadvantaged/Displaced Workers', 'description' => 'TUPAD Program']
    );

    $this->duplicateService = app(DuplicateDetectionService::class);
});

test('exact first name and last name match scores minimum 75 even with different DOB and profile details', function () {
    $existing = Beneficiary::create([
        'first_name' => 'Rea Mae',
        'last_name' => 'Vence',
        'full_name' => 'REA MAE VENCE',
        'date_of_birth' => '1990-01-01',
        'sex' => 'Female',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
        'government_id_number' => 'ID-11111',
        'contact_number' => '09111111111',
    ]);

    BeneficiaryProgram::create([
        'beneficiary_id' => $existing->id,
        'program_id' => $this->tupad->id,
        'availment_year' => 2024,
        'status' => 'approved',
    ]);

    $payload = [
        'first_name' => 'Rea Mae',
        'last_name' => 'Vence',
        'date_of_birth' => '2005-08-15',
        'sex' => 'Female',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Casisang',
        'government_id_number' => 'ID-99999',
        'contact_number' => '09222222222',
        'program_code' => 'TUPAD',
        'availment_year' => (int) date('Y'),
    ];

    $result = $this->duplicateService->checkDuplicates($payload);

    expect($result['has_duplicates'])->toBeTrue();
    expect($result['max_score'])->toBeGreaterThanOrEqual(75);
    expect($result['flags'])->not->toBeEmpty();

    $flag = $result['flags'][0];
    expect($flag['match_score'])->toBeGreaterThanOrEqual(75);
    expect($flag['is_exact_name_match'])->toBeTrue();
    expect($flag['matched_fields'])->toHaveKey('name');
    expect($flag['matched_fields'])->toHaveKey('same_name_alert');
});

test('processRowsBulk candidate pre-filter bucket includes exact surname and first name matches', function () {
    $existing = Beneficiary::create([
        'first_name' => 'Rea Mae',
        'last_name' => 'Vence',
        'full_name' => 'REA MAE VENCE',
        'date_of_birth' => '1990-01-01',
        'sex' => 'Female',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
    ]);

    $importer = new BeneficiariesImport('TUPAD');
    $reflector = new ReflectionClass($importer);

    $indexMethod = $reflector->getMethod('indexCandidatesByNameSound');
    $indexMethod->setAccessible(true);
    $candidatesMethod = $reflector->getMethod('candidatesForPayload');
    $candidatesMethod->setAccessible(true);

    $index = $indexMethod->invoke($importer, collect([$existing]));

    $payload = [
        'first_name' => 'Rea Mae',
        'last_name' => 'Vence',
    ];

    $pool = $candidatesMethod->invoke($importer, $payload, $index);

    expect($pool->contains('id', $existing->id))->toBeTrue();
});

test('bulk import duplicate scan creates DuplicateFlag and updates beneficiary program status to pending', function () {
    $existing = Beneficiary::create([
        'first_name' => 'Rea Mae',
        'last_name' => 'Vence',
        'full_name' => 'REA MAE VENCE',
        'date_of_birth' => '1990-01-01',
        'sex' => 'Female',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
    ]);

    BeneficiaryProgram::create([
        'beneficiary_id' => $existing->id,
        'program_id' => $this->tupad->id,
        'availment_year' => 2024,
        'status' => 'approved',
    ]);

    $newBeneficiary = Beneficiary::create([
        'first_name' => 'Rea Mae',
        'last_name' => 'Vence',
        'full_name' => 'REA MAE VENCE',
        'date_of_birth' => '2005-08-15',
        'sex' => 'Female',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Casisang',
    ]);

    $newProgram = BeneficiaryProgram::create([
        'beneficiary_id' => $newBeneficiary->id,
        'program_id' => $this->tupad->id,
        'availment_year' => (int) date('Y'),
        'status' => 'approved',
    ]);

    $importer = new BeneficiariesImport('TUPAD');
    $reflector = new ReflectionClass($importer);
    $scanMethod = $reflector->getMethod('runDeferredDuplicateScan');
    $scanMethod->setAccessible(true);

    $payload = [
        'first_name' => 'Rea Mae',
        'last_name' => 'Vence',
        'date_of_birth' => '2005-08-15',
        'sex' => 'Female',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Casisang',
        'program_code' => 'TUPAD',
        'availment_year' => (int) date('Y'),
    ];

    $scanMethod->invoke($importer, [$newBeneficiary->id => $payload]);

    $newProgram->refresh();
    expect($newProgram->status)->toBe('pending');

    $flag = DuplicateFlag::where('beneficiary_id', $newBeneficiary->id)->first();
    expect($flag)->not->toBeNull();
    expect($flag->match_score)->toBeGreaterThanOrEqual(75);
    expect($flag->status)->toBe('pending');
    expect($flag->matched_beneficiary_id)->toBe($existing->id);
});

test('normalizeNameString cleans trailing punctuation and whitespace variations correctly', function () {
    expect(DuplicateDetectionService::normalizeNameString('GONZALES, MARIA CLARA .'))->toBe('gonzales, maria clara');
    expect(DuplicateDetectionService::normalizeNameString('SANTO, JUAN CARLOS .'))->toBe('santo, juan carlos');
    expect(DuplicateDetectionService::normalizeNameString('  Gonzales, Maria Clara  '))->toBe('gonzales, maria clara');
    expect(DuplicateDetectionService::normalizeNameString('GONZALES .'))->toBe('gonzales');
    expect(DuplicateDetectionService::normalizeNameString('MARIA CLARA .'))->toBe('maria clara');
});

test('trailing punctuation name variations trigger duplicate match with minimum 75 score and same_name_alert', function () {
    $existing = Beneficiary::create([
        'first_name' => 'Maria Clara',
        'last_name' => 'Gonzales',
        'full_name' => 'GONZALES, MARIA CLARA',
        'date_of_birth' => '1985-05-20',
        'sex' => 'Female',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
    ]);

    $payload = [
        'first_name' => 'MARIA CLARA .',
        'last_name' => 'GONZALES .',
        'date_of_birth' => '2000-10-10', // Different DOB
        'sex' => 'Female',
        'municipality' => 'Maramag',
        'barangay' => 'Anahawon',
        'program_code' => 'TUPAD',
        'availment_year' => 2026,
    ];

    $result = $this->duplicateService->checkDuplicates($payload);

    expect($result['has_duplicates'])->toBeTrue();
    expect($result['max_score'])->toBeGreaterThanOrEqual(75);
    expect($result['flags'][0]['is_exact_name_match'])->toBeTrue();
    expect($result['flags'][0]['match_score'])->toBeGreaterThanOrEqual(75);
    expect($result['flags'][0]['matched_fields'])->toHaveKey('same_name_alert');
});

test('profile show view displays bidirectional duplicate banner when viewing matched beneficiary record', function () {
    $beneficiaryA = Beneficiary::create([
        'first_name' => 'Juan Carlos',
        'last_name' => 'Santo',
        'full_name' => 'SANTO, JUAN CARLOS',
        'date_of_birth' => '1992-03-15',
        'sex' => 'Male',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
    ]);

    $beneficiaryB = Beneficiary::create([
        'first_name' => 'JUAN CARLOS .',
        'last_name' => 'SANTO .',
        'full_name' => 'SANTO, JUAN CARLOS .',
        'date_of_birth' => '1999-11-20',
        'sex' => 'Male',
        'municipality' => 'Quezon',
        'barangay' => 'Libertad',
    ]);

    DuplicateFlag::create([
        'beneficiary_id' => $beneficiaryB->id,
        'matched_beneficiary_id' => $beneficiaryA->id,
        'match_score' => 75,
        'match_type' => 'medium',
        'matched_fields' => ['same_name_alert' => 'Exact Name Match'],
        'status' => 'pending',
    ]);

    // Test viewing Beneficiary A (who is the matched_beneficiary_id, not beneficiary_id)
    $responseA = $this->actingAs($this->admin)->get(route('beneficiaries.show', $beneficiaryA));
    $responseA->assertOk();
    $responseA->assertSee('Duplicate Alerts &amp; Conflicts', false);
    $responseA->assertSee('75% Match');
    $responseA->assertSee($beneficiaryB->full_name);
    $responseA->assertDontSee('Clean Beneficiary Record');

    // Test viewing Beneficiary B (who is the beneficiary_id)
    $responseB = $this->actingAs($this->admin)->get(route('beneficiaries.show', $beneficiaryB));
    $responseB->assertOk();
    $responseB->assertSee('Duplicate Alerts &amp; Conflicts', false);
    $responseB->assertSee('75% Match');
    $responseB->assertSee($beneficiaryA->full_name);
    $responseB->assertDontSee('Clean Beneficiary Record');
});

test('first name token soundex, near birthdate typo, and location surname bonus flag Toffe Baron vs Toff Vergara in Maramag', function () {
    $existing = Beneficiary::create([
        'first_name' => 'Toff',
        'last_name' => 'Vergara',
        'full_name' => 'VERGARA, TOFF',
        'date_of_birth' => '2004-10-13',
        'sex' => 'Male',
        'municipality' => 'Maramag',
        'province' => 'Bukidnon',
        'barangay' => 'Poblacion',
        'government_id_number' => 'ID-VERGARA-1',
    ]);

    BeneficiaryProgram::create([
        'beneficiary_id' => $existing->id,
        'program_id' => $this->tupad->id,
        'availment_year' => 2024,
        'status' => 'approved',
    ]);

    $payload = [
        'first_name' => 'Toffe Baron',
        'last_name' => 'Vergara',
        'date_of_birth' => '2004-10-10',
        'sex' => 'Male',
        'municipality' => 'Maramag',
        'province' => 'Bukidnon',
        'barangay' => 'North Poblacion',
        'government_id_number' => 'ID-VERGARA-2',
        'program_code' => 'TUPAD',
        'availment_year' => (int) date('Y'),
    ];

    $result = $this->duplicateService->checkDuplicates($payload);

    expect($result['has_duplicates'])->toBeTrue();
    expect($result['max_score'])->toBeGreaterThanOrEqual(75);
    expect($result['flags'])->not->toBeEmpty();

    $flag = $result['flags'][0];
    expect($flag['match_score'])->toBeGreaterThanOrEqual(75);
    expect($flag['matched_fields'])->toHaveKey('name');
    expect($flag['matched_fields']['name'])->toContain('35 pts');
    expect($flag['matched_fields'])->toHaveKey('dob');
    expect($flag['matched_fields']['dob'])->toContain('Near Birthdate Match');
    expect($flag['matched_fields']['dob'])->toContain('20 pts');

    // Create the duplicate flag and verify profile show does NOT show 'Clean Beneficiary Record'
    $newBeneficiary = Beneficiary::create([
        'first_name' => 'Toffe Baron',
        'last_name' => 'Vergara',
        'full_name' => 'VERGARA, TOFFE BARON',
        'date_of_birth' => '2004-10-10',
        'sex' => 'Male',
        'municipality' => 'Maramag',
        'province' => 'Bukidnon',
        'barangay' => 'North Poblacion',
        'government_id_number' => 'ID-VERGARA-2',
    ]);

    $this->duplicateService->recordDuplicateFlags($newBeneficiary, $result['flags'], 'pending');

    $response = $this->actingAs($this->admin)->get(route('beneficiaries.show', $newBeneficiary));
    $response->assertOk();
    $response->assertSee('Duplicate Alerts &amp; Conflicts', false);
    $response->assertDontSee('Clean Beneficiary Record');
});
