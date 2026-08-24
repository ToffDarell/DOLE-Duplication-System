<?php

use App\Models\Beneficiary;
use App\Models\DuplicateFlag;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', 'admin@dole-bukidnon.gov.ph')->first();
    $this->actingAs($this->admin);
});

test('duplicate flags can be filtered by program', function () {
    $tupad = Program::where('code', 'TUPAD')->first();
    $spes = Program::where('code', 'SPES')->first();

    $ben1 = Beneficiary::create([
        'full_name' => 'JUAN TUPAD CRISOSTOMO',
        'first_name' => 'JUAN',
        'last_name' => 'CRISOSTOMO',
        'date_of_birth' => '1995-01-01',
        'sex' => 'Male',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Poblacion',
    ]);
    $ben1->beneficiaryPrograms()->create(['program_id' => $tupad->id, 'availment_year' => 2026, 'status' => 'pending']);

    $ben2 = Beneficiary::create([
        'full_name' => 'JUAN SPES CRISOSTOMO',
        'first_name' => 'JUAN',
        'last_name' => 'CRISOSTOMO',
        'date_of_birth' => '1995-01-01',
        'sex' => 'Male',
        'municipality' => 'Valencia City',
        'barangay' => 'Poblacion',
    ]);
    $ben2->beneficiaryPrograms()->create(['program_id' => $spes->id, 'availment_year' => 2026, 'status' => 'pending']);

    $flag1 = DuplicateFlag::create([
        'beneficiary_id' => $ben1->id,
        'matched_beneficiary_id' => $ben2->id,
        'match_score' => 95,
        'match_type' => 'fuzzy_soundex',
        'status' => 'pending',
    ]);

    // Query with TUPAD program filter
    $response = $this->get(route('duplicates.index', ['program' => 'TUPAD']));
    $response->assertOk();
    $response->assertSee('JUAN TUPAD CRISOSTOMO');
    $response->assertViewHas('counts');

    // Query with DILP program filter (should not match flag1)
    $responseDilp = $this->get(route('duplicates.index', ['program' => 'DILP']));
    $responseDilp->assertOk();
    $responseDilp->assertDontSee('Flag #'.$flag1->id);
});

test('duplicate resolution console calculates distinct count badges and filters by category', function () {
    $tupad = Program::where('code', 'TUPAD')->first();

    $ben1 = Beneficiary::create([
        'full_name' => 'MARIA FIRST SANTOS',
        'first_name' => 'MARIA',
        'last_name' => 'SANTOS',
        'date_of_birth' => '1996-03-15',
        'sex' => 'Female',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Poblacion',
    ]);
    $ben1->beneficiaryPrograms()->create(['program_id' => $tupad->id, 'availment_year' => 2026, 'status' => 'pending']);

    $ben2 = Beneficiary::create([
        'full_name' => 'MARIA TARGET SANTOS',
        'first_name' => 'MARIA',
        'last_name' => 'SANTOS',
        'date_of_birth' => '1996-03-15',
        'sex' => 'Female',
        'municipality' => 'Malaybalay City',
        'barangay' => 'Poblacion',
    ]);
    $ben2->beneficiaryPrograms()->create(['program_id' => $tupad->id, 'availment_year' => 2026, 'status' => 'pending']);

    // Flag 1: First Name match
    DuplicateFlag::create([
        'beneficiary_id' => $ben1->id,
        'matched_beneficiary_id' => $ben2->id,
        'match_score' => 88,
        'match_type' => 'first_name',
        'matched_fields' => ['first_name' => 'Exact First Name match'],
        'status' => 'pending',
    ]);

    // Flag 2: Surname match
    DuplicateFlag::create([
        'beneficiary_id' => $ben1->id,
        'matched_beneficiary_id' => $ben2->id,
        'match_score' => 85,
        'match_type' => 'surname',
        'matched_fields' => ['last_name' => 'Exact Surname match'],
        'status' => 'pending',
    ]);

    // Flag 3: Address / Household match
    DuplicateFlag::create([
        'beneficiary_id' => $ben1->id,
        'matched_beneficiary_id' => $ben2->id,
        'match_score' => 70,
        'match_type' => 'address',
        'household_match_flag' => true,
        'status' => 'pending',
    ]);

    // Flag 4: Resolved / Approved
    DuplicateFlag::create([
        'beneficiary_id' => $ben1->id,
        'matched_beneficiary_id' => $ben2->id,
        'match_score' => 90,
        'match_type' => 'high',
        'status' => 'resolved_not_duplicate',
    ]);

    // Flag 5: Removed
    DuplicateFlag::create([
        'beneficiary_id' => $ben1->id,
        'matched_beneficiary_id' => $ben2->id,
        'match_score' => 100,
        'match_type' => 'exact',
        'status' => 'resolved_duplicate',
    ]);

    $response = $this->get(route('duplicates.index', ['program' => 'TUPAD']));
    $response->assertOk();
    $counts = $response->viewData('counts');

    expect($counts['all'])->toBeGreaterThanOrEqual(5);
    expect($counts['first_name'])->toBeGreaterThanOrEqual(1);
    expect($counts['surname'])->toBeGreaterThanOrEqual(1);
    expect($counts['address'])->toBeGreaterThanOrEqual(1);
    expect($counts['resolved'])->toBeGreaterThanOrEqual(1);
    expect($counts['removed'])->toBeGreaterThanOrEqual(1);

    // Test filter tabs
    $resFirst = $this->get(route('duplicates.index', ['filter' => 'first_name', 'program' => 'TUPAD']));
    $resFirst->assertOk();

    $resSurname = $this->get(route('duplicates.index', ['filter' => 'surname', 'program' => 'TUPAD']));
    $resSurname->assertOk();

    $resAddr = $this->get(route('duplicates.index', ['filter' => 'address', 'program' => 'TUPAD']));
    $resAddr->assertOk();

    $resResolved = $this->get(route('duplicates.index', ['filter' => 'resolved', 'program' => 'TUPAD']));
    $resResolved->assertOk();

    $resRemoved = $this->get(route('duplicates.index', ['filter' => 'removed', 'program' => 'TUPAD']));
    $resRemoved->assertOk();
});

test('preview-rows endpoint returns detected start and end rows with beneficiary count', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    // Header on Row 3
    $sheet->setCellValue('A3', 'No.');
    $sheet->setCellValue('B3', 'Last Name');
    $sheet->setCellValue('C3', 'First Name');
    $sheet->setCellValue('D3', 'Date of Birth');

    // Row 4
    $sheet->setCellValue('A4', '1');
    $sheet->setCellValue('B4', 'MENDOZA');
    $sheet->setCellValue('C4', 'CARLOS');
    $sheet->setCellValue('D4', '2001-08-20');

    // Row 5
    $sheet->setCellValue('A5', '2');
    $sheet->setCellValue('B5', 'MENDOZA');
    $sheet->setCellValue('C5', 'ALTHEA');
    $sheet->setCellValue('D5', '2003-02-14');

    // Row 6 Footer
    $sheet->setCellValue('A6', 'TOTAL BENEFICIARIES: 2');

    $tempPath = tempnam(sys_get_temp_dir(), 'prev_test_').'.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempPath);

    $uploadedFile = new UploadedFile($tempPath, 'masterlist.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $response = $this->postJson(route('import.preview-rows'), [
        'file' => $uploadedFile,
        'program_code' => 'TUPAD',
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'detected_start_row' => 4,
        'detected_end_row' => 5,
        'total_rows_found' => 2,
    ]);

    @unlink($tempPath);
});
