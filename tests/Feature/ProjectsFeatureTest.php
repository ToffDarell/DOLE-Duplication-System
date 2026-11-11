<?php

use App\Imports\BeneficiariesImport;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\Program;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', 'admin@dole-bukidnon.gov.ph')->first();
    $this->actingAs($this->admin);
    $this->tupad = Program::where('code', 'TUPAD')->first();
});

test('projects page lists programs and project beneficiaries', function () {
    $project = Project::factory()->create(['program_id' => $this->tupad->id, 'name' => 'TUPAD Mendis 2026']);
    $beneficiary = Beneficiary::create([
        'first_name' => 'Juan', 'last_name' => 'Cruz', 'full_name' => 'Cruz, Juan',
        'date_of_birth' => '1990-01-01', 'sex' => 'Male', 'barangay' => 'Mendis', 'municipality' => 'Pangantucan',
    ]);
    BeneficiaryProgram::create([
        'beneficiary_id' => $beneficiary->id, 'program_id' => $this->tupad->id,
        'project_id' => $project->id, 'availment_year' => 2026, 'status' => 'approved',
    ]);

    $this->get(route('projects.index', ['program' => 'TUPAD']))
        ->assertOk()->assertSee('TUPAD Mendis 2026');

    $this->get(route('projects.show', $project))
        ->assertOk()->assertSee('Cruz, Juan');
});

test('project beneficiaries roster filters by barangay and status', function () {
    $project = Project::factory()->create(['program_id' => $this->tupad->id, 'name' => 'TUPAD Valencia 2026']);

    $ben1 = Beneficiary::create([
        'first_name' => 'Maria', 'last_name' => 'Santos', 'full_name' => 'Santos, Maria',
        'date_of_birth' => '1995-05-10', 'sex' => 'Female', 'barangay' => 'Bagontaas', 'municipality' => 'Valencia City',
    ]);
    $ben2 = Beneficiary::create([
        'first_name' => 'Pedro', 'last_name' => 'Penduko', 'full_name' => 'Penduko, Pedro',
        'date_of_birth' => '1992-02-14', 'sex' => 'Male', 'barangay' => 'Lumbo', 'municipality' => 'Valencia City',
    ]);

    BeneficiaryProgram::create([
        'beneficiary_id' => $ben1->id, 'program_id' => $this->tupad->id,
        'project_id' => $project->id, 'availment_year' => 2026, 'status' => 'approved',
    ]);
    BeneficiaryProgram::create([
        'beneficiary_id' => $ben2->id, 'program_id' => $this->tupad->id,
        'project_id' => $project->id, 'availment_year' => 2026, 'status' => 'pending',
    ]);

    // Check with barangay filter
    $this->get(route('projects.show', ['project' => $project->id, 'barangay' => 'Bagontaas']))
        ->assertOk()
        ->assertSee('Santos, Maria')
        ->assertDontSee('Penduko, Pedro');

    // Check with status filter
    $this->get(route('projects.show', ['project' => $project->id, 'status' => 'pending']))
        ->assertOk()
        ->assertSee('Penduko, Pedro')
        ->assertDontSee('Santos, Maria');
});

test('project details and implementation dates can be updated', function () {
    $project = Project::factory()->create([
        'program_id' => $this->tupad->id,
        'name' => 'Original Project Title',
        'municipality' => 'Pangantucan',
        'year' => 2026,
    ]);

    $response = $this->put(route('projects.update', $project), [
        'program_id' => $this->tupad->id,
        'name' => 'Updated Project Title',
        'implementation_type' => 'ACP / Co-Partner (LGU)',
        'municipality' => 'Valencia City',
        'target_beneficiaries' => 500,
        'year' => 2026,
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-15',
        'description' => 'Updated implementation details',
    ]);

    $response->assertRedirect(route('projects.show', $project));
    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Updated Project Title',
        'implementation_type' => 'ACP / Co-Partner (LGU)',
        'municipality' => 'Valencia City',
        'target_beneficiaries' => 500,
    ]);
});

test('import links every beneficiary to the chosen project', function () {
    $project = Project::factory()->create(['program_id' => $this->tupad->id]);

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'No.');
    $sheet->setCellValue('B1', 'Last Name');
    $sheet->setCellValue('C1', 'First Name');
    $sheet->setCellValue('F1', 'Birthdate (YYYY/MM/DD)');
    $sheet->setCellValue('G1', 'Barangay');
    $sheet->setCellValue('H1', 'City/ Municipality');
    $sheet->setCellValue('Q1', 'Sex');
    $sheet->setCellValue('A2', '1');
    $sheet->setCellValue('B2', 'ABANES');
    $sheet->setCellValue('C2', 'GRACE');
    $sheet->setCellValue('F2', '2000/05/20');
    $sheet->setCellValue('G2', 'MENDIS');
    $sheet->setCellValue('H2', 'PANGANTUCAN');
    $sheet->setCellValue('Q2', 'FEMALE');

    $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    $log = (new BeneficiariesImport('TUPAD', null, null, $project->id))->import($path);

    expect($log->imported_rows)->toBe(1)
        ->and(BeneficiaryProgram::where('project_id', $project->id)->count())->toBe(1);
});
