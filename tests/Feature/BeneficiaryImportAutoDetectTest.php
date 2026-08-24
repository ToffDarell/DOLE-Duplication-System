<?php

use App\Imports\BeneficiariesImport;
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

test('auto detects header and start row for any DOLE program masterlist', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    // Simulate Title metadata on rows 1 to 3
    $sheet->setCellValue('A1', 'DEPARTMENT OF LABOR AND EMPLOYMENT');
    $sheet->setCellValue('A2', 'DOLE BUKIDNON FIELD OFFICE');
    $sheet->setCellValue('A3', 'SPECIAL PROGRAM FOR EMPLOYMENT OF STUDENTS (SPES) MASTERLIST');

    // Simulate Header on Row 4
    $sheet->setCellValue('A4', 'No.');
    $sheet->setCellValue('B4', 'Last Name');
    $sheet->setCellValue('C4', 'First Name');
    $sheet->setCellValue('D4', 'Middle Name');
    $sheet->setCellValue('E4', 'Date of Birth');
    $sheet->setCellValue('F4', 'Sex');
    $sheet->setCellValue('G4', 'Barangay');
    $sheet->setCellValue('H4', 'Municipality');

    // Simulate Beneficiary Data on Row 5
    $sheet->setCellValue('A5', '1');
    $sheet->setCellValue('B5', 'DELA CRUZ');
    $sheet->setCellValue('C5', 'JUAN');
    $sheet->setCellValue('D5', 'SANTOS');
    $sheet->setCellValue('E5', '2004-05-12');
    $sheet->setCellValue('F5', 'Male');
    $sheet->setCellValue('G5', 'Poblacion');
    $sheet->setCellValue('H5', 'Valencia City');

    // Simulate Footer / Totals on Row 6
    $sheet->setCellValue('A6', 'TOTAL BENEFICIARIES: 1');
    $sheet->setCellValue('B6', 'PREPARED BY: SPES FOCAL PERSON');

    $tempPath = tempnam(sys_get_temp_dir(), 'dole_test_').'.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempPath);

    $import = new BeneficiariesImport('SPES');
    $log = $import->import($tempPath);

    expect($log->imported_rows)->toBe(1);
    expect($log->failed_rows)->toBe(0);

    @unlink($tempPath);
});
