<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\DuplicateFlag;
use App\Models\Program;
use App\Models\User;
use App\Services\DuplicateDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    Role::firstOrCreate(['name' => 'Validator']);
    Role::firstOrCreate(['name' => 'Encoder']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');

    Program::firstOrCreate(['code' => 'TUPAD'], ['name' => 'Tulong Panghanapbuhay sa Ating Disadvantaged/Displaced Workers']);
    Program::firstOrCreate(['code' => 'SPES'], ['name' => 'Special Program for Employment of Students']);
    Program::firstOrCreate(['code' => 'DILP'], ['name' => 'DOLE Integrated Livelihood Program']);
    Program::firstOrCreate(['code' => 'GIP'], ['name' => 'Government Internship Program']);
});

/*
|--------------------------------------------------------------------------
| Item 1: Duplicate Engine Updates (Middle Name & Graceful DOB Degradation)
|--------------------------------------------------------------------------
*/

test('duplicate engine awards +10 pts for exact middle name and +8 pts for fuzzy middle name', function () {
    $existing = Beneficiary::create([
        'full_name' => 'JUAN BAUTISTA DELA CRUZ',
        'first_name' => 'JUAN',
        'middle_name' => 'BAUTISTA',
        'last_name' => 'DELA CRUZ',
        'date_of_birth' => '1990-05-15',
        'sex' => 'Male',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Poblacion',
    ]);

    $service = app(DuplicateDetectionService::class);

    // Exact middle name match
    $exactResult = $service->checkDuplicates([
        'first_name' => 'JUAN',
        'middle_name' => 'BAUTISTA',
        'last_name' => 'DELA CRUZ',
        'date_of_birth' => '1990-05-15',
    ]);

    expect($exactResult['has_duplicates'])->toBeTrue();
    $topExact = $exactResult['top_match'];
    expect($topExact['matched_fields'])->toHaveKey('middle_name');
    expect($topExact['matched_fields']['middle_name'])->toContain('Exact Middle Name match (10 pts)');

    // Fuzzy middle name match (e.g. minor typo "Bautisto" vs "Bautista")
    $fuzzyResult = $service->checkDuplicates([
        'first_name' => 'JUAN',
        'middle_name' => 'BAUTISTO',
        'last_name' => 'DELA CRUZ',
        'date_of_birth' => '1990-05-15',
    ]);

    expect($fuzzyResult['has_duplicates'])->toBeTrue();
    $topFuzzy = $fuzzyResult['top_match'];
    expect($topFuzzy['matched_fields'])->toHaveKey('middle_name');
    expect($topFuzzy['matched_fields']['middle_name'])->toContain('Fuzzy Middle Name match (8 pts)');
});

test('duplicate engine gracefully degrades DOB score for single-digit typo and inverted month-day', function () {
    $existing = Beneficiary::create([
        'full_name' => 'MARIA SANTOS',
        'first_name' => 'MARIA',
        'last_name' => 'SANTOS',
        'date_of_birth' => '1995-04-05',
        'sex' => 'Female',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
    ]);

    $service = app(DuplicateDetectionService::class);

    // Inverted Month-Day (1995-05-04 vs 1995-04-05) -> 24 pts
    $invertedResult = $service->checkDuplicates([
        'first_name' => 'MARIA',
        'last_name' => 'SANTOS',
        'date_of_birth' => '1995-05-04',
    ]);

    expect($invertedResult['has_duplicates'])->toBeTrue();
    $topInverted = $invertedResult['top_match'];
    expect($topInverted['matched_fields'])->toHaveKey('dob');
    expect($topInverted['matched_fields']['dob'])->toContain('Inverted Month-Day Date of Birth (24 pts)');

    // Single-digit typo (1995-04-06 vs 1995-04-05) -> 22 pts
    $typoResult = $service->checkDuplicates([
        'first_name' => 'MARIA',
        'last_name' => 'SANTOS',
        'date_of_birth' => '1995-04-06',
    ]);

    expect($typoResult['has_duplicates'])->toBeTrue();
    $topTypo = $typoResult['top_match'];
    expect($topTypo['matched_fields'])->toHaveKey('dob');
    expect($topTypo['matched_fields']['dob'])->toContain('Near Date of Birth Match (Single-digit typo detected) (22 pts)');
});

/*
|--------------------------------------------------------------------------
| Item 2: Duplicate Resolution Console Filtering
|--------------------------------------------------------------------------
*/

test('duplicate resolution console filters flags by municipality, barangay, sex, and risk level', function () {
    $benMuniA = Beneficiary::create([
        'full_name' => 'ALICE MALAYBALAY',
        'first_name' => 'ALICE',
        'last_name' => 'MALAYBALAY',
        'date_of_birth' => '1992-01-01',
        'sex' => 'Female',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Casisang',
    ]);

    $benMuniB = Beneficiary::create([
        'full_name' => 'BOB VALENCIA',
        'first_name' => 'BOB',
        'last_name' => 'VALENCIA',
        'date_of_birth' => '1990-02-02',
        'sex' => 'Male',
        'municipality' => 'Valencia City',
        'barangay' => 'Laligan',
    ]);

    $flag1 = DuplicateFlag::create([
        'beneficiary_id' => $benMuniA->id,
        'matched_beneficiary_id' => $benMuniB->id,
        'match_score' => 95,
        'match_type' => 'high',
        'status' => 'pending',
    ]);

    $flag2 = DuplicateFlag::create([
        'beneficiary_id' => $benMuniB->id,
        'matched_beneficiary_id' => $benMuniA->id,
        'match_score' => 75,
        'match_type' => 'medium',
        'status' => 'pending',
    ]);

    // Filter by Municipality Malaybalay City
    $resMuni = $this->actingAs($this->admin)->get(route('duplicates.index', ['municipality' => 'Malaybalay City']));
    $resMuni->assertOk()
        ->assertSee('ALICE MALAYBALAY', false);

    // Filter by Sex: Female
    $resSex = $this->actingAs($this->admin)->get(route('duplicates.index', ['sex' => 'Female']));
    $resSex->assertOk()
        ->assertSee('ALICE MALAYBALAY', false);

    // Filter by Risk Level: High (85-99)
    $resRisk = $this->actingAs($this->admin)->get(route('duplicates.index', ['risk_level' => 'high']));
    $resRisk->assertOk()
        ->assertSee('95%', false);
});

/*
|--------------------------------------------------------------------------
| Item 3: Navigation & Quick Filter Links
|--------------------------------------------------------------------------
*/

test('sidebar renders links for TUPAD, SPES, and GIP programs', function () {
    $response = $this->actingAs($this->admin)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('program=TUPAD', false)
        ->assertSee('program=SPES', false)
        ->assertSee('program=GIP', false);
});

test('beneficiaries masterlist displays GIP quick filter pill', function () {
    $response = $this->actingAs($this->admin)->get(route('beneficiaries.index'));

    $response->assertOk()
        ->assertSee('program=GIP', false)
        ->assertSee('>GIP<', false);
});
