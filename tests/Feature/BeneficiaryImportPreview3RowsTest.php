<?php

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

test('preview-rows endpoint accurately detects start row 3 and end row 5 for 3-row dataset', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    // Row 1: Merged headers
    $sheet->setCellValue('A1', 'No.');
    $sheet->setCellValue('B1', 'Name of Beneficiary');
    $sheet->setCellValue('F1', 'Birthdate (YYYY/MM/DD)');
    $sheet->setCellValue('G1', 'Project Location');

    // Row 2: Sub-headers
    $sheet->setCellValue('B2', 'Last Name');
    $sheet->setCellValue('C2', 'First Name');
    $sheet->setCellValue('D2', 'Middle Name');
    $sheet->setCellValue('E2', 'Extn');

    // Row 3: Beneficiary 1
    $sheet->setCellValue('A3', '1');
    $sheet->setCellValue('B3', 'ABANES');
    $sheet->setCellValue('C3', 'GRACE');
    $sheet->setCellValue('F3', '2000/05/20');

    // Row 4: Beneficiary 2
    $sheet->setCellValue('A4', '2');
    $sheet->setCellValue('B4', 'APA-AP');
    $sheet->setCellValue('C4', 'MARLOU');
    $sheet->setCellValue('F4', '1996/11/10');

    // Row 5: Beneficiary 3
    $sheet->setCellValue('A5', '3');
    $sheet->setCellValue('B5', 'AS-ASON');
    $sheet->setCellValue('C5', 'CHIERENE JOY');
    $sheet->setCellValue('F5', '2003/05/08');

    $tempPath = tempnam(sys_get_temp_dir(), 'prev3_').'.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempPath);

    $uploadedFile = new UploadedFile($tempPath, '3rows.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $response = $this->postJson(route('import.preview-rows'), [
        'file' => $uploadedFile,
        'program_code' => 'TUPAD',
    ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['success'])->toBeTrue();
    expect($data['detected_start_row'])->toBe(3);
    expect($data['detected_end_row'])->toBe(5);
    expect($data['total_rows_found'])->toBe(3);
    expect($data['header_row'])->toBe(2);

    @unlink($tempPath);
});
