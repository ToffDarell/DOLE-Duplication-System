<?php

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\Program;
use App\Models\User;
use App\Services\BeneficiaryDuplicateChecker;
use App\Services\DuplicateDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Validator', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Encoder', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');

    $this->gip = Program::firstOrCreate(
        ['code' => 'GIP'],
        ['name' => 'Government Internship Program', 'description' => 'GIP Program']
    );

    $this->dilp = Program::firstOrCreate(
        ['code' => 'DILP'],
        ['name' => 'DOLE Integrated Livelihood Program', 'description' => 'DILP Program']
    );

    $this->tupad = Program::firstOrCreate(
        ['code' => 'TUPAD'],
        ['name' => 'Tulong Panghanapbuhay sa Ating Disadvantaged/Displaced Workers', 'description' => 'TUPAD Program']
    );

    $this->duplicateService = app(DuplicateDetectionService::class);
    $this->duplicateChecker = app(BeneficiaryDuplicateChecker::class);
});

test('fuzzy duplicate detection flags Caliao, Atheo Jessar when entering Caliao, Atheo with different DOB and municipality', function () {
    // Existing record in Maramag (DILP 2026)
    $existing = Beneficiary::create([
        'first_name' => 'Atheo Jessar',
        'last_name' => 'Caliao',
        'full_name' => 'Caliao, Atheo Jessar',
        'date_of_birth' => '1999-07-09',
        'sex' => 'Male',
        'barangay' => 'Kuya',
        'municipality' => 'Maramag',
        'government_id_number' => '23456754',
        'contact_number' => '09977907712',
    ]);

    BeneficiaryProgram::create([
        'beneficiary_id' => $existing->id,
        'program_id' => $this->dilp->id,
        'availment_year' => 2026,
        'status' => 'approved',
    ]);

    // Candidate payload: Caliao, Atheo in Dangcagan with DOB 2001-07-08
    $candidateData = [
        'first_name' => 'Atheo',
        'last_name' => 'Caliao',
        'date_of_birth' => '2001-07-08',
        'sex' => 'Male',
        'barangay' => 'Poblacion',
        'municipality' => 'Dangcagan',
        'government_id_number' => '4354676543',
        'program_code' => 'GIP',
        'availment_year' => 2025,
    ];

    // 1. Verify BeneficiaryDuplicateChecker service finds the duplicate via token and soundex
    $matches = $this->duplicateChecker->findDuplicates($candidateData);
    expect($matches)->toHaveCount(1)
        ->and($matches->first()->id)->toBe($existing->id);

    // 2. Verify DuplicateDetectionService returns duplicate flag (score >= 75)
    $checkResult = $this->duplicateService->checkDuplicates($candidateData);
    expect($checkResult['has_duplicates'])->toBeTrue()
        ->and($checkResult['flags'])->toHaveCount(1)
        ->and($checkResult['flags'][0]['matched_beneficiary_id'])->toBe($existing->id)
        ->and($checkResult['flags'][0]['match_score'])->toBeGreaterThanOrEqual(75);

    // 3. Verify HTTP API endpoint triggers 409 status code
    $response = $this->actingAs($this->admin)->postJson(route('beneficiaries.check-duplicate'), $candidateData);
    $response->assertStatus(409);
    $response->assertJson([
        'has_duplicates' => true,
    ]);
});

test('merging two beneficiary records correctly transfers past program history and removes secondary record', function () {
    // Primary Record: Caliao, Atheo Jessar with DILP 2026
    $primary = Beneficiary::create([
        'first_name' => 'Atheo Jessar',
        'last_name' => 'Caliao',
        'full_name' => 'Caliao, Atheo Jessar',
        'date_of_birth' => '1999-07-09',
        'sex' => 'Male',
        'barangay' => 'Kuya',
        'municipality' => 'Maramag',
        'government_id_number' => '23456754',
        'contact_number' => '09977907712',
    ]);

    $dilpProgram = BeneficiaryProgram::create([
        'beneficiary_id' => $primary->id,
        'program_id' => $this->dilp->id,
        'availment_year' => 2026,
        'status' => 'approved',
    ]);

    // Secondary Record: Caliao, Atheo with GIP 2025
    $secondary = Beneficiary::create([
        'first_name' => 'Atheo',
        'last_name' => 'Caliao',
        'full_name' => 'Caliao, Atheo',
        'date_of_birth' => '2001-07-08',
        'sex' => 'Male',
        'barangay' => 'Poblacion',
        'municipality' => 'Dangcagan',
        'government_id_number' => '4354676543',
        'contact_number' => '09977907731',
    ]);

    $gipProgram = BeneficiaryProgram::create([
        'beneficiary_id' => $secondary->id,
        'program_id' => $this->gip->id,
        'availment_year' => 2025,
        'status' => 'approved',
    ]);

    // Execute Merge: secondary into primary
    $response = $this->actingAs($this->admin)->postJson(route('beneficiaries.merge'), [
        'primary_id' => $primary->id,
        'secondary_id' => $secondary->id,
        'remarks' => 'Merged duplicate encoded variations for Atheo Caliao',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    // Verify secondary record is removed
    expect(Beneficiary::find($secondary->id))->toBeNull();

    // Verify primary record now owns BOTH DILP 2026 and GIP 2025 programs
    $primaryPrograms = BeneficiaryProgram::where('beneficiary_id', $primary->id)->get();
    expect($primaryPrograms)->toHaveCount(2);

    $programCodes = $primaryPrograms->map(fn ($bp) => $bp->program->code)->toArray();
    expect($programCodes)->toContain('DILP')
        ->and($programCodes)->toContain('GIP');

    // Verify AuditLog was recorded
    $log = AuditLog::where('action', 'BENEFICIARY_MERGED')
        ->where('model_id', $primary->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->description)->toContain("Merged beneficiary ID {$secondary->id}");
});
