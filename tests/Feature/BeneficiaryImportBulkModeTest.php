<?php

use App\Imports\BeneficiariesImport;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\DuplicateFlag;
use App\Models\SystemSetting;
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

function bulkModeSheet(array $rows): string
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    // Row 1: merged headers
    $sheet->setCellValue('A1', 'No.');
    $sheet->setCellValue('B1', 'Name of Beneficiary');
    $sheet->setCellValue('F1', 'Birthdate (YYYY/MM/DD)');
    $sheet->setCellValue('G1', 'Project Location');
    $sheet->setCellValue('K1', 'Type of ID');
    $sheet->setCellValue('L1', 'ID No.');
    $sheet->setCellValue('Q1', 'Sex');
    $sheet->setCellValue('R1', 'Civil Status');

    // Row 2: sub-headers
    $sheet->setCellValue('B2', 'Last Name');
    $sheet->setCellValue('C2', 'First Name');
    $sheet->setCellValue('D2', 'Middle Name');
    $sheet->setCellValue('E2', 'Extn');
    $sheet->setCellValue('G2', 'Barangay');
    $sheet->setCellValue('H2', 'City/ Municipality');
    $sheet->setCellValue('I2', 'Province');

    $r = 3;
    foreach ($rows as $row) {
        $sheet->setCellValue("A{$r}", (string) ($r - 2));
        $sheet->setCellValue("B{$r}", $row['last']);
        $sheet->setCellValue("C{$r}", $row['first']);
        $sheet->setCellValue("D{$r}", $row['middle'] ?? '');
        $sheet->setCellValue("F{$r}", $row['dob']);
        $sheet->setCellValue("G{$r}", $row['barangay'] ?? 'Poblacion');
        $sheet->setCellValue("H{$r}", $row['municipality'] ?? 'Malaybalay City');
        $sheet->setCellValue("I{$r}", 'BUKIDNON');
        $sheet->setCellValue("K{$r}", 'NATIONAL ID');
        $sheet->setCellValue("L{$r}", $row['gov_id'] ?? 'N/A');
        $sheet->setCellValue("Q{$r}", $row['sex'] ?? 'MALE');
        $sheet->setCellValue("R{$r}", 'SINGLE');
        $r++;
    }

    $path = tempnam(sys_get_temp_dir(), 'bulk_').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return $path;
}

test('imports above the bulk threshold use the fast batched path and still flag duplicates afterwards', function () {
    SystemSetting::set('bulk_import_threshold', '5');

    $path = bulkModeSheet([
        ['last' => 'SANTOS', 'first' => 'JUAN', 'dob' => '1990/01/01', 'gov_id' => '1111-2222-3333'],
        ['last' => 'SANTOS', 'first' => 'JUAN', 'dob' => '1990/01/01', 'gov_id' => '1111-2222-3333'], // exact duplicate of row above
        ['last' => 'REYES', 'first' => 'MARIA', 'dob' => '1995/03/15', 'gov_id' => '4444-5555-6666', 'sex' => 'FEMALE'],
        ['last' => 'CRUZ', 'first' => 'PEDRO', 'dob' => '1988/07/20', 'gov_id' => '7777-8888-9999'],
        ['last' => 'LIM', 'first' => 'ANNA', 'dob' => '2000/11/11', 'gov_id' => '1212-3434-5656', 'sex' => 'FEMALE'],
    ]);

    $log = (new BeneficiariesImport('TUPAD'))->import($path);

    expect($log->imported_rows)->toBe(5);
    expect($log->failed_rows)->toBe(0);
    expect(Beneficiary::count())->toBe(5);

    // The batched path leaves a note in the import log
    expect(json_encode($log->error_log))->toContain('Fast bulk import');

    // Deferred duplicate scan still ran: the SANTOS pair is flagged + downgraded
    expect(DuplicateFlag::count())->toBeGreaterThanOrEqual(1);

    $santosIds = Beneficiary::where('last_name', 'SANTOS')->pluck('id');
    expect(BeneficiaryProgram::whereIn('beneficiary_id', $santosIds)->where('status', 'pending')->count())
        ->toBe($santosIds->count());

    // Unique rows stay approved
    $uniqueIds = Beneficiary::whereIn('last_name', ['REYES', 'CRUZ', 'LIM'])->pluck('id');
    expect(BeneficiaryProgram::whereIn('beneficiary_id', $uniqueIds)->where('status', 'approved')->count())
        ->toBe(3);

    @unlink($path);
});

test('imports below the bulk threshold keep using the per-row path', function () {
    SystemSetting::set('bulk_import_threshold', '50');

    $path = bulkModeSheet([
        ['last' => 'ABANES', 'first' => 'GRACE', 'dob' => '2000/05/20', 'gov_id' => '1000-2000-3000', 'sex' => 'FEMALE'],
        ['last' => 'APAAP', 'first' => 'MARLOU', 'dob' => '1996/11/10', 'gov_id' => '4000-5000-6000'],
    ]);

    $log = (new BeneficiariesImport('TUPAD'))->import($path);

    expect($log->imported_rows)->toBe(2);
    expect($log->failed_rows)->toBe(0);
    expect(json_encode($log->error_log))->not->toContain('Fast bulk import');

    @unlink($path);
});
