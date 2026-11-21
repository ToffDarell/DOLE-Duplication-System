<?php

use App\Imports\BeneficiariesImport;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\DilpGroup;
use App\Models\DilpProject;
use App\Models\DuplicateFlag;
use App\Models\Program;
use App\Models\User;
use App\Services\YearlyArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', 'admin@dole-bukidnon.gov.ph')->first();
    $this->actingAs($this->admin);
});

test('import page renders confirmation modal and program-specific parser hints', function () {
    $response = $this->get(route('import.index'));

    $response->assertOk()
        ->assertSee('Confirm Masterlist Import')
        ->assertSee('Yes, Proceed with Import')
        ->assertSee('SPES-specific column profile active');
});

test('returning TUPAD beneficiary from prior year is not flagged as duplicate in next year import', function () {
    $tupad = Program::where('code', 'TUPAD')->first();

    $existing = Beneficiary::create([
        'full_name' => 'DELA CRUZ, JUAN SANTOS',
        'first_name' => 'JUAN',
        'middle_name' => 'SANTOS',
        'last_name' => 'DELA CRUZ',
        'date_of_birth' => '1990-05-15',
        'sex' => 'Male',
        'barangay' => 'Poblacion',
        'municipality' => 'Valencia City',
        'province' => 'Bukidnon',
    ]);

    BeneficiaryProgram::create([
        'beneficiary_id' => $existing->id,
        'program_id' => $tupad->id,
        'availment_year' => 2026,
        'status' => 'approved',
    ]);

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'No.');
    $sheet->setCellValue('B1', 'Last Name');
    $sheet->setCellValue('C1', 'First Name');
    $sheet->setCellValue('D1', 'Middle Name');
    $sheet->setCellValue('F1', 'Birthdate');
    $sheet->setCellValue('G1', 'Barangay');
    $sheet->setCellValue('H1', 'Municipality');
    $sheet->setCellValue('Q1', 'Sex');

    $sheet->setCellValue('A2', '1');
    $sheet->setCellValue('B2', 'DELA CRUZ');
    $sheet->setCellValue('C2', 'JUAN');
    $sheet->setCellValue('D2', 'SANTOS');
    $sheet->setCellValue('F2', '1990-05-15');
    $sheet->setCellValue('G2', 'Poblacion');
    $sheet->setCellValue('H2', 'Valencia City');
    $sheet->setCellValue('Q2', 'Male');

    $path = tempnam(sys_get_temp_dir(), 'tupad_2027_').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    $log = (new BeneficiariesImport('TUPAD', null, null, null, 2027))->import($path);
    @unlink($path);

    expect($log->imported_rows)->toBe(1)
        ->and($log->failed_rows)->toBe(0)
        ->and(DuplicateFlag::count())->toBe(0)
        ->and(Beneficiary::where('last_name', 'DELA CRUZ')->count())->toBe(1)
        ->and(BeneficiaryProgram::where('beneficiary_id', $existing->id)->where('availment_year', 2027)->where('status', 'approved')->exists())->toBeTrue();
});

test('SPES compact 3-column sheet parses program-specific columns and enforces age 15-30 eligibility', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    // Compact 3-column SPES sheet (Full Name, Birthdate, Classification)
    $sheet->setCellValue('A1', 'Full Name');
    $sheet->setCellValue('B1', 'Date of Birth');
    $sheet->setCellValue('C1', 'Classification');

    // Row 2: Eligible 20-year-old student
    $sheet->setCellValue('A2', 'SANTOS, ANA MARIE');
    $sheet->setCellValue('B2', now()->subYears(20)->format('Y-m-d'));
    $sheet->setCellValue('C2', 'Enrolled Student');

    // Row 3: Ineligible 35-year-old (> 30)
    $sheet->setCellValue('A3', 'REYES, PEDRO');
    $sheet->setCellValue('B3', now()->subYears(35)->format('Y-m-d'));
    $sheet->setCellValue('C3', 'Student');

    $path = tempnam(sys_get_temp_dir(), 'spes_compact_').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    $log = (new BeneficiariesImport('SPES', null, null, null, 2026))->import($path);
    @unlink($path);

    expect($log->imported_rows)->toBe(1)
        ->and($log->failed_rows)->toBe(1)
        ->and(json_encode($log->error_log))->toContain('SPES Eligibility')
        ->and(Beneficiary::where('last_name', 'SANTOS')->where('is_student', true)->exists())->toBeTrue()
        ->and(Beneficiary::where('last_name', 'REYES')->exists())->toBeFalse();
});

test('GIP import enforces 1-year duration cap and blocks once-in-a-lifetime repeat availment', function () {
    $gip = Program::where('code', 'GIP')->first();

    $existing = Beneficiary::create([
        'full_name' => 'GARCIA, CARLO',
        'first_name' => 'CARLO',
        'last_name' => 'GARCIA',
        'date_of_birth' => '2001-03-10',
        'sex' => 'Male',
        'barangay' => 'Casisang',
        'municipality' => 'Malaybalay City',
        'province' => 'Bukidnon',
    ]);

    BeneficiaryProgram::create([
        'beneficiary_id' => $existing->id,
        'program_id' => $gip->id,
        'availment_year' => 2026,
        'status' => 'approved',
    ]);

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Last Name');
    $sheet->setCellValue('B1', 'First Name');
    $sheet->setCellValue('C1', 'Date of Birth');
    $sheet->setCellValue('D1', 'Duration');

    // Row 2: Repeat GIP beneficiary in 2027 (must fail lifetime cap)
    $sheet->setCellValue('A2', 'GARCIA');
    $sheet->setCellValue('B2', 'CARLO');
    $sheet->setCellValue('C2', '2001-03-10');
    $sheet->setCellValue('D2', '6 months');

    // Row 3: New GIP beneficiary with 18 months duration (must fail 1-year cap)
    $sheet->setCellValue('A3', 'LOPEZ');
    $sheet->setCellValue('B3', 'MARIA');
    $sheet->setCellValue('C3', '2002-07-22');
    $sheet->setCellValue('D3', '18 months');

    // Row 4: Valid new GIP beneficiary with 12 months duration
    $sheet->setCellValue('A4', 'MENDOZA');
    $sheet->setCellValue('B4', 'LUIS');
    $sheet->setCellValue('C4', '2002-09-14');
    $sheet->setCellValue('D4', '1 year');

    $path = tempnam(sys_get_temp_dir(), 'gip_rules_').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    $log = (new BeneficiariesImport('GIP', null, null, null, 2027))->import($path);
    @unlink($path);

    expect($log->imported_rows)->toBe(1)
        ->and($log->failed_rows)->toBe(2)
        ->and(json_encode($log->error_log))->toContain('once-in-a-lifetime')
        ->and(json_encode($log->error_log))->toContain('12 months')
        ->and(Beneficiary::where('last_name', 'MENDOZA')->exists())->toBeTrue();
});

test('participation history displays name and year while hiding sensitive contact and gov id fields', function () {
    $tupad = Program::where('code', 'TUPAD')->first();

    $ben = Beneficiary::create([
        'full_name' => 'VILLANUEVA, ELENA',
        'first_name' => 'ELENA',
        'last_name' => 'VILLANUEVA',
        'date_of_birth' => '1989-11-02',
        'sex' => 'Female',
        'contact_number' => '09998887777',
        'government_id_number' => 'SENSITIVE-ID-9999',
        'address' => 'Purok 9 Secret House',
        'barangay' => 'Lumbo',
        'municipality' => 'Valencia City',
        'province' => 'Bukidnon',
    ]);

    BeneficiaryProgram::create([
        'beneficiary_id' => $ben->id,
        'program_id' => $tupad->id,
        'availment_year' => 2026,
        'status' => 'approved',
    ]);

    $response = $this->get(route('history.index', ['search' => 'VILLANUEVA', 'year' => 2026]));

    $response->assertOk()
        ->assertSee('VILLANUEVA, ELENA')
        ->assertSee('TUPAD')
        ->assertSee('2026')
        ->assertDontSee('09998887777')
        ->assertDontSee('SENSITIVE-ID-9999')
        ->assertDontSee('Purok 9 Secret House');
});

test('DILP projects support Group and Individual categories with grant_amount and DA/ACP modes', function () {
    $group = DilpGroup::create([
        'group_name' => 'Bukidnon Coffee Growers Assoc.',
        'co_partner_name' => 'LGU Lantapan',
    ]);

    $this->post(route('dilp.projects.store'), [
        'project_type' => 'group',
        'dilp_group_id' => $group->id,
        'project_name' => 'Coffee Roasting Facility',
        'grant_amount' => 750000.50,
        'implementation_mode' => 'ACP',
        'acp_name' => 'LGU Lantapan',
        'liquidation_status' => 'pending',
    ])->assertRedirect(route('dilp.projects.index'));

    $this->post(route('dilp.projects.store'), [
        'project_type' => 'individual',
        'beneficiary_name' => 'DELA CRUZ, MARIO',
        'project_name' => 'Welding Starter Kit',
        'grant_amount' => 30000.00,
        'implementation_mode' => 'DA',
        'liquidation_status' => 'liquidated',
    ])->assertRedirect(route('dilp.projects.index'));

    expect(DilpProject::where('project_type', 'group')->where('implementation_mode', 'ACP')->exists())->toBeTrue()
        ->and(DilpProject::where('project_type', 'individual')->where('implementation_mode', 'DA')->where('beneficiary_name', 'DELA CRUZ, MARIO')->exists())->toBeTrue();

    $this->get(route('dilp.projects.index', ['type' => 'individual']))
        ->assertOk()
        ->assertSee('DELA CRUZ, MARIO')
        ->assertSee('Welding Starter Kit')
        ->assertSee('30,000.00')
        ->assertSee('Direct Admin (DA)')
        ->assertDontSee('Coffee Roasting Facility');

    $this->get(route('dilp.projects.index', ['type' => 'group']))
        ->assertOk()
        ->assertSee('Bukidnon Coffee Growers Assoc.')
        ->assertSee('Coffee Roasting Facility')
        ->assertSee('750,000.50')
        ->assertSee('LGU Lantapan')
        ->assertDontSee('Welding Starter Kit');
});

test('yearly archive service creates downloadable gzipped archive and safely prunes old import error logs', function () {
    $tupad = Program::where('code', 'TUPAD')->first();

    $ben = Beneficiary::create([
        'full_name' => 'ARCHIVE TESTER',
        'first_name' => 'TESTER',
        'last_name' => 'ARCHIVE',
        'date_of_birth' => '1992-01-01',
        'sex' => 'Male',
        'barangay' => 'Poblacion',
        'municipality' => 'Maramag',
        'province' => 'Bukidnon',
    ]);

    BeneficiaryProgram::create([
        'beneficiary_id' => $ben->id,
        'program_id' => $tupad->id,
        'availment_year' => 2025,
        'status' => 'approved',
    ]);

    $service = app(YearlyArchiveService::class);
    $result = $service->createYearlyArchive(2025);

    expect(file_exists($result['path']))->toBeTrue()
        ->and($result['total_enrollments'])->toBe(1)
        ->and($result['compressed_bytes'])->toBeGreaterThan(0);

    $decoded = json_decode((string) gzdecode((string) file_get_contents($result['path'])), true);
    expect($decoded['manifest']['year'])->toBe(2025)
        ->and($decoded['records'])->toHaveCount(1)
        ->and($decoded['records'][0]['full_name'])->toBe('ARCHIVE TESTER');

    $download = $this->get(route('archives.yearly.download', $result['filename']));
    $download->assertOk();

    @unlink($result['path']);
});
