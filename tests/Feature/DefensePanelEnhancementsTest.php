<?php

namespace Tests\Feature;

use App\Exports\BeneficiariesExport;
use App\Models\Beneficiary;
use App\Models\Program;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    Role::firstOrCreate(['name' => 'Validator']);
    Role::firstOrCreate(['name' => 'Encoder']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');

    $this->validator = User::factory()->create();
    $this->validator->assignRole('Validator');

    $this->encoder = User::factory()->create();
    $this->encoder->assignRole('Encoder');

    Program::firstOrCreate(['code' => 'TUPAD'], ['name' => 'Tulong Panghanapbuhay sa Ating Disadvantaged/Displaced Workers']);
    Program::firstOrCreate(['code' => 'SPES'], ['name' => 'Special Program for Employment of Students']);
});

/*
|--------------------------------------------------------------------------
| Item 1: Soft Delete & Archive / Trash System Tests (Panelist: Glaiza Mae Libe)
|--------------------------------------------------------------------------
*/

test('deleting a beneficiary performs a soft delete (archive) and preserves database integrity', function () {
    $beneficiary = Beneficiary::create([
        'full_name' => 'JUAN DELA CRUZ',
        'first_name' => 'JUAN',
        'last_name' => 'DELA CRUZ',
        'sex' => 'Male',
        'date_of_birth' => '1990-05-15',
        'civil_status' => 'Single',
        'contact_number' => '09171234567',
        'barangay' => 'Poblacion',
        'municipality' => 'Malaybalay City',
        'address' => 'Purok 1',
    ]);

    $response = $this->actingAs($this->admin)->delete(route('beneficiaries.destroy', $beneficiary));

    $response->assertRedirect(route('beneficiaries.index'))
        ->assertSessionHas('success');

    // Model is soft-deleted
    expect(Beneficiary::find($beneficiary->id))->toBeNull();
    expect(Beneficiary::withTrashed()->find($beneficiary->id))->not->toBeNull();
    expect(Beneficiary::withTrashed()->find($beneficiary->id)->deleted_at)->not->toBeNull();
});

test('admin can view the dedicated archives page and see soft-deleted records', function () {
    $beneficiary = Beneficiary::create([
        'full_name' => 'MARIA CLARA',
        'first_name' => 'MARIA',
        'last_name' => 'CLARA',
        'sex' => 'Female',
        'date_of_birth' => '1992-08-20',
        'civil_status' => 'Single',
        'contact_number' => '09181234567',
        'barangay' => 'Casisang',
        'municipality' => 'Malaybalay City',
        'address' => 'Purok 3',
    ]);
    $beneficiary->delete(); // Soft delete

    $response = $this->actingAs($this->admin)->get(route('beneficiaries.archived'));

    $response->assertOk()
        ->assertSee('Archived Beneficiaries', false)
        ->assertSee('MARIA CLARA', false)
        ->assertSee('Restore', false);
});

test('admin can restore an archived beneficiary back to the active masterlist', function () {
    $beneficiary = Beneficiary::create([
        'full_name' => 'CRISOSTOMO IBARRA',
        'first_name' => 'CRISOSTOMO',
        'last_name' => 'IBARRA',
        'sex' => 'Male',
        'date_of_birth' => '1988-10-10',
        'civil_status' => 'Single',
        'contact_number' => '09191234567',
        'barangay' => 'Aglayan',
        'municipality' => 'Malaybalay City',
        'address' => 'Purok 2',
    ]);
    $beneficiary->delete();

    $response = $this->actingAs($this->admin)->post(route('beneficiaries.restore', $beneficiary->id));

    $response->assertRedirect(route('beneficiaries.archived'))
        ->assertSessionHas('success');

    expect(Beneficiary::find($beneficiary->id))->not->toBeNull();
    expect(Beneficiary::find($beneficiary->id)->deleted_at)->toBeNull();
});

test('admin can perform bulk restore on archived beneficiaries', function () {
    $ben1 = Beneficiary::create([
        'full_name' => 'ELIAS SALGADO',
        'first_name' => 'ELIAS',
        'last_name' => 'SALGADO',
        'sex' => 'Male',
        'date_of_birth' => '1987-03-12',
        'civil_status' => 'Single',
        'contact_number' => '09201234567',
        'barangay' => 'Sumpong',
        'municipality' => 'Malaybalay City',
        'address' => 'Purok 4',
    ]);
    $ben1->delete();

    $ben2 = Beneficiary::create([
        'full_name' => 'BASILIO SALGADO',
        'first_name' => 'BASILIO',
        'last_name' => 'SALGADO',
        'sex' => 'Male',
        'date_of_birth' => '1998-07-22',
        'civil_status' => 'Single',
        'contact_number' => '09211234567',
        'barangay' => 'Sumpong',
        'municipality' => 'Malaybalay City',
        'address' => 'Purok 4',
    ]);
    $ben2->delete();

    $response = $this->actingAs($this->admin)->post(route('beneficiaries.bulk-restore'), [
        'ids' => [$ben1->id, $ben2->id],
    ]);

    $response->assertRedirect(route('beneficiaries.archived'))
        ->assertSessionHas('success');

    expect(Beneficiary::find($ben1->id))->not->toBeNull();
    expect(Beneficiary::find($ben2->id))->not->toBeNull();
});

test('admin can permanently delete (force delete) an archived beneficiary', function () {
    $beneficiary = Beneficiary::create([
        'full_name' => 'TIAGO DELOS SANTOS',
        'first_name' => 'TIAGO',
        'last_name' => 'DELOS SANTOS',
        'sex' => 'Male',
        'date_of_birth' => '1980-11-11',
        'civil_status' => 'Married',
        'contact_number' => '09221113344',
        'barangay' => 'San Jose',
        'municipality' => 'Malaybalay City',
        'address' => 'Purok 1',
    ]);
    $beneficiary->delete(); // Soft delete

    $response = $this->actingAs($this->admin)->delete(route('beneficiaries.force-delete', $beneficiary->id));

    $response->assertRedirect(route('beneficiaries.archived'))
        ->assertSessionHas('success');

    // Record completely removed from database
    expect(Beneficiary::withTrashed()->find($beneficiary->id))->toBeNull();
});

test('admin can bulk permanently delete multiple archived beneficiaries', function () {
    $ben1 = Beneficiary::create([
        'full_name' => 'LUCAS CORNELIO',
        'first_name' => 'LUCAS',
        'last_name' => 'CORNELIO',
        'sex' => 'Male',
        'date_of_birth' => '1982-01-01',
        'civil_status' => 'Single',
        'contact_number' => '09231112233',
        'barangay' => 'Kalasungay',
        'municipality' => 'Malaybalay City',
        'address' => 'Purok 2',
    ]);
    $ben1->delete();

    $ben2 = Beneficiary::create([
        'full_name' => 'MATEO CORNELIO',
        'first_name' => 'MATEO',
        'last_name' => 'CORNELIO',
        'sex' => 'Male',
        'date_of_birth' => '1984-02-02',
        'civil_status' => 'Single',
        'contact_number' => '09241112233',
        'barangay' => 'Kalasungay',
        'municipality' => 'Malaybalay City',
        'address' => 'Purok 2',
    ]);
    $ben2->delete();

    $response = $this->actingAs($this->admin)->post(route('beneficiaries.bulk-force-delete'), [
        'ids' => [$ben1->id, $ben2->id],
    ]);

    $response->assertRedirect(route('beneficiaries.archived'))
        ->assertSessionHas('success');

    expect(Beneficiary::withTrashed()->find($ben1->id))->toBeNull();
    expect(Beneficiary::withTrashed()->find($ben2->id))->toBeNull();
});

test('admin can permanently delete all matching archived beneficiaries via select-overall', function () {
    $activeKeep = Beneficiary::create([
        'full_name' => 'ACTIVE KEEPER',
        'first_name' => 'ACTIVE',
        'last_name' => 'KEEPER',
        'sex' => 'Male',
        'date_of_birth' => '1990-01-01',
        'civil_status' => 'Single',
        'contact_number' => '09170000001',
        'barangay' => 'Poblacion',
        'municipality' => 'Maramag',
        'address' => 'Purok 1',
    ]);

    $trashedInOtherMuni = Beneficiary::create([
        'full_name' => 'DELTA OTHERTOWN',
        'first_name' => 'DELTA',
        'last_name' => 'OTHERTOWN',
        'sex' => 'Male',
        'date_of_birth' => '1991-02-02',
        'civil_status' => 'Single',
        'contact_number' => '09170000002',
        'barangay' => 'Poblacion',
        'municipality' => 'Maramag',
        'address' => 'Purok 2',
    ]);
    $trashedInOtherMuni->delete();

    foreach (['ALPHA', 'BRAVO', 'CHARLIE'] as $name) {
        $b = Beneficiary::create([
            'full_name' => "{$name} MALAYBALAY",
            'first_name' => $name,
            'last_name' => 'MALAYBALAY',
            'sex' => 'Male',
            'date_of_birth' => '1992-03-03',
            'civil_status' => 'Single',
            'contact_number' => '09170000003',
            'barangay' => 'Casisang',
            'municipality' => 'Malaybalay City',
            'address' => 'Purok 3',
        ]);
        $b->delete();
    }

    $response = $this->actingAs($this->admin)->post(route('beneficiaries.bulk-force-delete'), [
        'delete_all_matching' => '1',
        'municipality' => 'Malaybalay City',
    ]);

    $response->assertRedirect(route('beneficiaries.archived'))
        ->assertSessionHas('success');

    // Every archived Malaybalay City record is gone
    expect(Beneficiary::withTrashed()->where('last_name', 'MALAYBALAY')->count())->toBe(0);

    // Records outside the filter and active records are untouched
    expect(Beneficiary::withTrashed()->find($trashedInOtherMuni->id))->not->toBeNull();
    expect(Beneficiary::find($activeKeep->id))->not->toBeNull();
});

test('encoder can restore all matching archived beneficiaries via select-overall', function () {
    $trashedInOtherMuni = Beneficiary::create([
        'full_name' => 'DELTA OTHERTOWN',
        'first_name' => 'DELTA',
        'last_name' => 'OTHERTOWN',
        'sex' => 'Male',
        'date_of_birth' => '1991-02-02',
        'civil_status' => 'Single',
        'contact_number' => '09170000004',
        'barangay' => 'Poblacion',
        'municipality' => 'Maramag',
        'address' => 'Purok 2',
    ]);
    $trashedInOtherMuni->delete();

    $restoreTargets = collect(['ALPHA', 'BRAVO', 'CHARLIE'])->map(function ($name) {
        $b = Beneficiary::create([
            'full_name' => "{$name} MALAYBALAY",
            'first_name' => $name,
            'last_name' => 'MALAYBALAY',
            'sex' => 'Male',
            'date_of_birth' => '1992-03-03',
            'civil_status' => 'Single',
            'contact_number' => '09170000005',
            'barangay' => 'Casisang',
            'municipality' => 'Malaybalay City',
            'address' => 'Purok 3',
        ]);
        $b->delete();

        return $b;
    });

    $response = $this->actingAs($this->encoder)->post(route('beneficiaries.bulk-restore'), [
        'restore_all_matching' => '1',
        'municipality' => 'Malaybalay City',
    ]);

    $response->assertRedirect(route('beneficiaries.archived'))
        ->assertSessionHas('success');

    // Every matching archived record is back in the active registry
    foreach ($restoreTargets as $target) {
        expect(Beneficiary::find($target->id))->not->toBeNull();
        expect(Beneficiary::find($target->id)->deleted_at)->toBeNull();
    }

    // Records outside the filter stay archived
    expect(Beneficiary::find($trashedInOtherMuni->id))->toBeNull();
    expect(Beneficiary::withTrashed()->find($trashedInOtherMuni->id))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Item 3 & 6: Dashboard Municipal Breakdown & Risk Legend Tests
|--------------------------------------------------------------------------
*/

test('dashboard renders municipal breakdown datasets and risk indicator legend', function () {
    $response = $this->actingAs($this->admin)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Municipal Breakdown', false)
        ->assertSee('Duplicate Distribution', false)
        ->assertSee('Total Beneficiaries per Municipality', false)
        ->assertSee('chartMunicipalBeneficiaries', false)
        ->assertSee('chartMunicipalDuplicates', false)
        ->assertSee('Duplication Risk Level', false)
        ->assertSee('100%', false)
        ->assertSee('85%–99%', false)
        ->assertSee('70%–84%', false)
        ->assertSee('&lt;70%', false);
});

/*
|--------------------------------------------------------------------------
| Item 4 & 5: Multi-Category Filter Report Generator & Excel/PDF Exports
|--------------------------------------------------------------------------
*/

test('report generator console renders multi-category filter inputs and live preview', function () {
    Beneficiary::create([
        'full_name' => 'ANA MANALO',
        'first_name' => 'ANA',
        'last_name' => 'MANALO',
        'sex' => 'Female',
        'date_of_birth' => '2000-01-15',
        'civil_status' => 'Single',
        'contact_number' => '09221234567',
        'barangay' => 'Laligan',
        'municipality' => 'Valencia City',
        'address' => 'Purok 5',
    ]);

    $response = $this->actingAs($this->admin)->get(route('reports.index'));

    $response->assertOk()
        ->assertSee('Custom Beneficiary Report', false)
        ->assertSee('Multi-Category Report Parameters', false)
        ->assertSee('ORDER BY Last Name ASC, First Name ASC', false)
        ->assertSee('ANA MANALO', false)
        ->assertSee('Valencia City', false)
        ->assertSee('Export Excel (.xlsx)', false)
        ->assertSee('Export PDF Report', false);
});

test('api returns distinct barangays for a given municipality', function () {
    Beneficiary::create([
        'full_name' => 'PEDRO PENDUKO',
        'first_name' => 'PEDRO',
        'last_name' => 'PENDUKO',
        'sex' => 'Male',
        'date_of_birth' => '1994-06-18',
        'civil_status' => 'Single',
        'contact_number' => '09231234567',
        'barangay' => 'Mailag',
        'municipality' => 'Valencia City',
        'address' => 'Purok 6',
    ]);

    $response = $this->actingAs($this->admin)->getJson(route('api.barangays', ['municipality' => 'Valencia City']));

    $response->assertOk()
        ->assertJsonFragment(['Mailag']);
});

test('excel export downloads successfully with Maatwebsite Excel and alphabetical sorting', function () {
    Excel::fake();
    Carbon::setTestNow(now());

    Beneficiary::create([
        'full_name' => 'ZACHARY ZULUETA',
        'first_name' => 'ZACHARY',
        'last_name' => 'ZULUETA',
        'sex' => 'Male',
        'date_of_birth' => '1991-03-03',
        'civil_status' => 'Single',
        'contact_number' => '09241234567',
        'barangay' => 'Poblacion',
        'municipality' => 'Malaybalay City',
        'address' => 'Purok 1',
    ]);

    Beneficiary::create([
        'full_name' => 'AARON ALVAREZ',
        'first_name' => 'AARON',
        'last_name' => 'ALVAREZ',
        'sex' => 'Male',
        'date_of_birth' => '1995-05-05',
        'civil_status' => 'Single',
        'contact_number' => '09251234567',
        'barangay' => 'Poblacion',
        'municipality' => 'Malaybalay City',
        'address' => 'Purok 2',
    ]);

    $expectedFilename = 'dole_beneficiaries_export_'.date('Y-m-d_His').'.xlsx';
    $response = $this->actingAs($this->admin)->get(route('export.excel'));

    $response->assertOk();
    Excel::assertDownloaded($expectedFilename, function (BeneficiariesExport $export) {
        $results = $export->query()->get();

        // Check alphabetical sorting: ALVAREZ before ZULUETA
        return $results->first()->last_name === 'ALVAREZ' && $results->last()->last_name === 'ZULUETA';
    });
});

test('pdf export generates successfully with alphabetical order', function () {
    Beneficiary::create([
        'full_name' => 'BEATRICE BUENAVENTURA',
        'first_name' => 'BEATRICE',
        'last_name' => 'BUENAVENTURA',
        'sex' => 'Female',
        'date_of_birth' => '1993-09-09',
        'civil_status' => 'Single',
        'contact_number' => '09261234567',
        'barangay' => 'Barobo',
        'municipality' => 'Valencia City',
        'address' => 'Purok 7',
    ]);

    $response = $this->actingAs($this->admin)->get(route('export.pdf'));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
