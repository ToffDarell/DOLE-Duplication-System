<?php

use App\Imports\BeneficiariesImport;
use App\Models\Beneficiary;
use App\Models\TupadProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', 'admin@dole-bukidnon.gov.ph')->first();
    $this->actingAs($this->admin);
});

test('accurately imports 3-row mini dataset with row 1-2 merged headers', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    // Row 1: Merged headers
    $sheet->setCellValue('A1', 'No.');
    $sheet->setCellValue('B1', 'Name of Beneficiary');
    $sheet->setCellValue('F1', 'Birthdate (YYYY/MM/DD)');
    $sheet->setCellValue('G1', 'Project Location');
    $sheet->setCellValue('K1', 'Type of ID');
    $sheet->setCellValue('L1', 'ID No.');
    $sheet->setCellValue('M1', 'Contact No.');
    $sheet->setCellValue('N1', 'E-payment Account No.');
    $sheet->setCellValue('O1', 'Type of Beneficiary');
    $sheet->setCellValue('P1', 'Occupation');
    $sheet->setCellValue('Q1', 'Sex');
    $sheet->setCellValue('R1', 'Civil Status');
    $sheet->setCellValue('S1', 'Age');
    $sheet->setCellValue('T1', 'Average monthly Income');
    $sheet->setCellValue('U1', 'Dependent');
    $sheet->setCellValue('V1', 'Relationship to Dependent');

    // Row 2: Sub-headers
    $sheet->setCellValue('B2', 'Last Name');
    $sheet->setCellValue('C2', 'First Name');
    $sheet->setCellValue('D2', 'Middle Name');
    $sheet->setCellValue('E2', 'Extn');
    $sheet->setCellValue('G2', 'Barangay');
    $sheet->setCellValue('H2', 'City/ Municipality');
    $sheet->setCellValue('I2', 'Province');
    $sheet->setCellValue('J2', 'Distri');

    // Row 3: Beneficiary 1
    $sheet->setCellValue('A3', '1');
    $sheet->setCellValue('B3', 'ABANES');
    $sheet->setCellValue('C3', 'GRACE');
    $sheet->setCellValue('D3', 'SAYANAN');
    $sheet->setCellValue('E3', '');
    $sheet->setCellValue('F3', '2000/05/20');
    $sheet->setCellValue('G3', 'MENDIS');
    $sheet->setCellValue('H3', 'PANGANTUCAN');
    $sheet->setCellValue('I3', 'BUKIDNON');
    $sheet->setCellValue('J3', 'IV');
    $sheet->setCellValue('K3', 'NATIONAL ID');
    $sheet->setCellValue('L3', '4380-1354-7052-3087');
    $sheet->setCellValue('M3', 'N/A');
    $sheet->setCellValue('N3', 'N/A');
    $sheet->setCellValue('O3', 'INDIGENOUS PEOPLE');
    $sheet->setCellValue('P3', 'CROP GROWERS');
    $sheet->setCellValue('Q3', 'FEMALE');
    $sheet->setCellValue('R3', 'SINGLE');
    $sheet->setCellValue('S3', '26');
    $sheet->setCellValue('T3', '5000-10000');
    $sheet->setCellValue('U3', 'BENIE DIBION');
    $sheet->setCellValue('V3', 'LIVE-IN PARTNER');

    // Row 4: Beneficiary 2
    $sheet->setCellValue('A4', '2');
    $sheet->setCellValue('B4', 'APA-AP');
    $sheet->setCellValue('C4', 'MARLOU');
    $sheet->setCellValue('D4', 'JEMINEZ');
    $sheet->setCellValue('E4', '');
    $sheet->setCellValue('F4', '1996/11/10');
    $sheet->setCellValue('G4', 'MENDIS');
    $sheet->setCellValue('H4', 'PANGANTUCAN');
    $sheet->setCellValue('I4', 'BUKIDNON');
    $sheet->setCellValue('J4', 'IV');
    $sheet->setCellValue('K4', 'NATIONAL ID');
    $sheet->setCellValue('L4', '5087-1031-5213-5854');
    $sheet->setCellValue('M4', 'N/A');
    $sheet->setCellValue('N4', 'N/A');
    $sheet->setCellValue('O4', 'SELF-EMPLOYED');
    $sheet->setCellValue('P4', 'TRANSPORT WORKERS');
    $sheet->setCellValue('Q4', 'MALE');
    $sheet->setCellValue('R4', 'MARRIED');
    $sheet->setCellValue('S4', '30');
    $sheet->setCellValue('T4', '5000-10000');
    $sheet->setCellValue('U4', 'NERISSA T. APA-AP');
    $sheet->setCellValue('V4', 'WIFE');

    // Row 5: Beneficiary 3
    $sheet->setCellValue('A5', '3');
    $sheet->setCellValue('B5', 'AS-ASON');
    $sheet->setCellValue('C5', 'CHIERENE JOY');
    $sheet->setCellValue('D5', 'MEMPER');
    $sheet->setCellValue('E5', '');
    $sheet->setCellValue('F5', '2003/05/08');
    $sheet->setCellValue('G5', 'MENDIS');
    $sheet->setCellValue('H5', 'PANGANTUCAN');
    $sheet->setCellValue('I5', 'BUKIDNON');
    $sheet->setCellValue('J5', 'IV');
    $sheet->setCellValue('K5', 'POLICE CLEARANCE');
    $sheet->setCellValue('L5', 'TRAXW820251228000374');
    $sheet->setCellValue('M5', 'N/A');
    $sheet->setCellValue('N5', 'N/A');
    $sheet->setCellValue('O5', 'UNDEREMPLOYED');
    $sheet->setCellValue('P5', 'CROP GROWERS');
    $sheet->setCellValue('Q5', 'FEMALE');
    $sheet->setCellValue('R5', 'MARRIED');
    $sheet->setCellValue('S5', '23');
    $sheet->setCellValue('T5', '5000-10000');
    $sheet->setCellValue('U5', 'JAYSON R. AS-ASON');
    $sheet->setCellValue('V5', 'HUSBAND');

    $tempPath = tempnam(sys_get_temp_dir(), 'mini3_').'.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempPath);

    $importer = new BeneficiariesImport('TUPAD');
    $log = $importer->import($tempPath);

    expect($log->imported_rows)->toBe(3);
    expect($log->failed_rows)->toBe(0);

    $ben1 = Beneficiary::where('last_name', 'ABANES')->first();
    expect($ben1)->not->toBeNull();
    expect($ben1->first_name)->toBe('GRACE');
    expect(strtoupper($ben1->barangay))->toBe('MENDIS');
    expect(strtoupper($ben1->municipality))->toBe('PANGANTUCAN');

    $tupadProfile = TupadProfile::whereHas('beneficiaryProgram', fn ($q) => $q->where('beneficiary_id', $ben1->id))->first();
    expect($tupadProfile)->not->toBeNull();
    expect($tupadProfile->occupation)->toBe('CROP GROWERS');
    expect($tupadProfile->dependent_name)->toBe('BENIE DIBION');
    expect(strtoupper($tupadProfile->dependent_relationship))->toBe('LIVE-IN PARTNER');

    @unlink($tempPath);
});
