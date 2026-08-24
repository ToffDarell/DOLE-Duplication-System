<?php

namespace App\Imports;

use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\ImportLog;
use App\Models\Program;
use App\Models\TupadProfile;
use App\Services\BeneficiaryImportService;
use App\Services\DuplicateDetectionService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BeneficiariesImport implements ToCollection, WithChunkReading
{
    protected string $programCode = 'TUPAD';

    protected ?int $customStartRow = null;

    protected ?int $customEndRow = null;

    protected ?BeneficiaryImportService $importService = null;

    protected ?DuplicateDetectionService $duplicateService = null;

    public function __construct(string $programCode = 'TUPAD', ?int $startRow = null, ?int $endRow = null)
    {
        $this->programCode = strtoupper($programCode);
        $this->customStartRow = ($startRow !== null && $startRow > 0) ? $startRow : null;
        $this->customEndRow = ($endRow !== null && $endRow > 0) ? $endRow : null;
        $this->importService = app(BeneficiaryImportService::class);
        $this->duplicateService = app(DuplicateDetectionService::class);
    }

    /**
     * Chunk size for Maatwebsite batch reading.
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Maatwebsite ToCollection handler.
     */
    public function collection(Collection $rows): void
    {
        // Handled via dynamic spreadsheet reader for flexible multi-level header resolution
    }

    /**
     * Execute dynamic import from Excel/CSV file with robust multi-level header resolution.
     *
     * @throws Exception
     */
    public function import(string $filePath): ImportLog
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $fileName = basename($filePath);
        $importLog = ImportLog::create([
            'user_id' => auth()->id(),
            'filename' => $fileName,
            'status' => 'processing',
            'total_rows' => 0,
            'imported_rows' => 0,
            'failed_rows' => 0,
            'error_log' => [],
        ]);

        $program = Program::where('code', $this->programCode)->first();
        if (! $program) {
            $importLog->update([
                'status' => 'failed',
                'error_log' => [['row' => 0, 'error' => "Target program '{$this->programCode}' not found in database"]],
            ]);

            return $importLog;
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = (int) $worksheet->getHighestRow();

        // 1. Dynamic Header & Start Row Detection
        $detection = $this->detectStartRowAndHeaders($worksheet);
        if ($detection['header_row'] === null && $this->customStartRow === null) {
            throw new Exception("Unable to locate valid table header in the uploaded sheet. Required columns like 'Last Name' / 'First Name' were not found.");
        }

        $columnMap = $detection['column_map'];
        $effectiveStartRow = $this->customStartRow ?? $detection['start_row'];
        $effectiveEndRow = ($this->customEndRow !== null && $this->customEndRow <= $highestRow) ? $this->customEndRow : $highestRow;

        $imported = 0;
        $failed = 0;
        $consecutiveEmpty = 0;
        $errors = [];

        // 2. Data loop starting immediately after detected header
        for ($row = $effectiveStartRow; $row <= $effectiveEndRow; $row++) {
            // Check footer text or non-beneficiary boundary
            if ($this->isFooterRow($worksheet, $row)) {
                break;
            }

            $lastNameRaw = $this->getCellValue($worksheet, $columnMap, 'last_name', $row, 'B');
            $firstNameRaw = $this->getCellValue($worksheet, $columnMap, 'first_name', $row, 'C');

            $lastName = $this->cleanString($lastNameRaw);
            $firstName = $this->cleanString($firstNameRaw);

            // Stop reading on empty rows (graceful handling for 1 to 3 rows or small datasets)
            if (empty($lastName) && empty($firstName)) {
                $consecutiveEmpty++;
                if ($consecutiveEmpty >= 3) {
                    break;
                }

                continue;
            }

            // Skip repeated page header rows
            if ($this->isHeaderRow($lastName, $firstName)) {
                continue;
            }

            $consecutiveEmpty = 0;
            $importLog->increment('total_rows');

            try {
                if (empty($lastName) || empty($firstName)) {
                    throw new Exception('Beneficiary Last Name and First Name are required');
                }

                $middleName = $this->cleanString($this->getCellValue($worksheet, $columnMap, 'middle_name', $row, 'D'));
                $suffix = $this->cleanString($this->getCellValue($worksheet, $columnMap, 'suffix', $row, 'E'));

                // Date of Birth & Age Calculation
                $dobRaw = $this->getCellValue($worksheet, $columnMap, 'date_of_birth', $row, 'F');
                $dob = $this->parseDate($dobRaw);
                if (! $dob) {
                    throw new Exception("Invalid or missing Date of Birth: '".($dobRaw ?? 'blank')."'");
                }

                $age = $dob->age;
                $isSenior = $age >= 60;

                // Sex normalization
                $sexRaw = strtolower((string) $this->getCellValue($worksheet, $columnMap, 'sex', $row, 'Q'));
                $sex = str_starts_with(trim($sexRaw), 'f') ? 'Female' : 'Male';

                // Normalized fields with "N/A" fallback for empty cells
                $civilStatus = $this->fallbackNA($this->normalizeCivilStatus($this->cleanString($this->getCellValue($worksheet, $columnMap, 'civil_status', $row, 'R'))));
                $govIdType = $this->fallbackNA($this->normalizeGovIdType($this->cleanString($this->getCellValue($worksheet, $columnMap, 'government_id_type', $row, 'K'))));
                $govIdNumber = $this->fallbackNA($this->cleanString($this->getCellValue($worksheet, $columnMap, 'government_id_number', $row, 'L')));
                $contactNumber = $this->fallbackNA($this->normalizeContact($this->getCellValue($worksheet, $columnMap, 'contact_number', $row, 'M')));

                $barangay = $this->fallbackNA($this->cleanString($this->getCellValue($worksheet, $columnMap, 'barangay', $row, 'G')), 'Central');
                $municipality = $this->fallbackNA($this->cleanString($this->getCellValue($worksheet, $columnMap, 'municipality', $row, 'H')), 'Pangantucan');
                $province = $this->fallbackNA($this->cleanString($this->getCellValue($worksheet, $columnMap, 'province', $row, 'I')), 'Bukidnon');
                $district = $this->cleanString($this->getCellValue($worksheet, $columnMap, 'district', $row, 'J'));
                $address = $this->cleanString($this->getCellValue($worksheet, $columnMap, 'address', $row, ''));

                // TUPAD Specific Fields
                $epayment = $this->fallbackNA($this->cleanString($this->getCellValue($worksheet, $columnMap, 'epayment_account_no', $row, 'N')));
                $benType = $this->fallbackNA($this->cleanString($this->getCellValue($worksheet, $columnMap, 'beneficiary_type', $row, 'O')));
                $occupation = $this->fallbackNA($this->cleanString($this->getCellValue($worksheet, $columnMap, 'occupation', $row, 'P')));
                $monthlyIncome = $this->fallbackNA($this->cleanString($this->getCellValue($worksheet, $columnMap, 'average_monthly_income', $row, 'T')));
                $dependentName = $this->fallbackNA($this->cleanString($this->getCellValue($worksheet, $columnMap, 'dependent_name', $row, 'U')));
                $dependentRel = $this->fallbackNA($this->cleanString($this->getCellValue($worksheet, $columnMap, 'dependent_relationship', $row, 'V')));

                $payload = [
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'suffix' => $suffix,
                    'date_of_birth' => $dob->format('Y-m-d'),
                    'sex' => $sex,
                    'civil_status' => $civilStatus,
                    'government_id_type' => $govIdType,
                    'government_id_number' => $govIdNumber,
                    'contact_number' => $contactNumber,
                    'address' => $address,
                    'barangay' => $barangay,
                    'municipality' => $municipality,
                    'province' => $province,
                    'district' => $district,
                    'program_code' => $this->programCode,
                    'availment_year' => (int) date('Y'),
                ];

                // Check Duplicates & Household match
                $dupCheck = $this->duplicateService->checkDuplicates($payload);
                $householdCheck = $this->duplicateService->checkHouseholdDuplicates($payload);

                DB::transaction(function () use ($payload, $firstName, $middleName, $lastName, $suffix, $dob, $isSenior, $program, $province, $district, $epayment, $benType, $occupation, $monthlyIncome, $dependentName, $dependentRel, $dupCheck, $householdCheck) {
                    $fullName = Beneficiary::buildFullName($firstName, $middleName, $lastName, $suffix);

                    $beneficiary = Beneficiary::create([
                        'full_name' => $fullName,
                        'first_name' => $firstName,
                        'middle_name' => $middleName,
                        'last_name' => $lastName,
                        'suffix' => $suffix,
                        'date_of_birth' => $dob->format('Y-m-d'),
                        'sex' => $payload['sex'],
                        'civil_status' => $payload['civil_status'],
                        'government_id_type' => $payload['government_id_type'],
                        'government_id_number' => $payload['government_id_number'],
                        'contact_number' => $payload['contact_number'],
                        'address' => $payload['address'],
                        'barangay' => $payload['barangay'],
                        'municipality' => $payload['municipality'],
                        'province' => $province,
                        'district' => $district,
                        'is_senior_citizen' => $isSenior,
                        'created_by' => auth()->id(),
                    ]);

                    $hasFlags = $dupCheck['has_duplicates'] || $householdCheck['has_household_flags'];
                    $beneficiaryProgram = BeneficiaryProgram::create([
                        'beneficiary_id' => $beneficiary->id,
                        'program_id' => $program->id,
                        'availment_year' => (int) date('Y'),
                        'status' => $hasFlags ? 'pending' : 'approved',
                    ]);

                    if ($program->code === 'TUPAD') {
                        TupadProfile::create([
                            'beneficiary_program_id' => $beneficiaryProgram->id,
                            'project_location_barangay' => $payload['barangay'],
                            'project_location_municipality' => $payload['municipality'],
                            'project_location_province' => $province,
                            'project_location_district' => $district,
                            'epayment_account_no' => $epayment,
                            'beneficiary_type' => $benType,
                            'occupation' => $occupation,
                            'average_monthly_income' => $monthlyIncome,
                            'dependent_name' => $dependentName,
                            'dependent_relationship' => $dependentRel,
                        ]);
                    }

                    if ($dupCheck['has_duplicates']) {
                        $this->duplicateService->recordDuplicateFlags($beneficiary, $dupCheck['flags']);
                    }

                    if ($householdCheck['has_household_flags']) {
                        $this->duplicateService->recordDuplicateFlags($beneficiary, $householdCheck['flags']);
                    }
                });

                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'row' => $row,
                    'name' => "{$lastName}, {$firstName}",
                    'error' => $e->getMessage(),
                ];
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $status = $failed === 0 ? 'completed' : ($imported > 0 ? 'completed' : 'failed');
        $importLog->update([
            'status' => $status,
            'imported_rows' => $imported,
            'failed_rows' => $failed,
            'error_log' => $errors,
        ]);

        return $importLog;
    }

    /**
     * Auto-detect header row and starting data row by scanning rows 1 to 50.
     * Supports both single-row headers and multi-level merged headers.
     *
     * @return array{start_row: int, header_row: ?int, column_map: array}
     */
    public function detectStartRowAndHeaders(Worksheet $worksheet): array
    {
        $highestRow = (int) $worksheet->getHighestRow();
        $scanMaxRow = min(50, $highestRow);
        $headerRow = null;

        for ($r = 1; $r <= $scanMaxRow; $r++) {
            $rowValues = [];
            for ($col = 1; $col <= 26; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $val = trim((string) $worksheet->getCell($colLetter.$r)->getValue());
                if (! empty($val)) {
                    $rowValues[] = strtolower($val);
                }
            }
            $rowText = implode(' ', $rowValues);

            if (empty($rowText)) {
                continue;
            }

            $hasName = str_contains($rowText, 'last name')
                || str_contains($rowText, 'first name')
                || str_contains($rowText, 'surname')
                || str_contains($rowText, 'given name')
                || str_contains($rowText, 'name of beneficiary')
                || str_contains($rowText, 'beneficiary name');

            $hasStructure = (str_contains($rowText, 'no.') || str_contains($rowText, 'no'))
                && (str_contains($rowText, 'sex') || str_contains($rowText, 'birth') || str_contains($rowText, 'dob') || str_contains($rowText, 'barangay'));

            if ($hasName || $hasStructure) {
                $headerRow = $r;

                // Check for multi-level sub-header row (e.g. Annex D rows 14 & 15)
                if ($r < $scanMaxRow) {
                    $nextRowVals = [];
                    for ($c = 1; $c <= 26; $c++) {
                        $cLetter = Coordinate::stringFromColumnIndex($c);
                        $v = trim((string) $worksheet->getCell($cLetter.($r + 1))->getValue());
                        if (! empty($v)) {
                            $nextRowVals[] = strtolower($v);
                        }
                    }
                    $nextText = implode(' ', $nextRowVals);
                    if (str_contains($nextText, 'last name') || str_contains($nextText, 'first name') || str_contains($nextText, 'middle name')) {
                        $headerRow = $r + 1;
                    }
                }

                break;
            }
        }

        $startRow = $headerRow !== null ? ($headerRow + 1) : 2;
        $mappingScanRow = $headerRow ?? min(20, $highestRow);
        $columnMap = $this->resolveColumnMap($worksheet, $mappingScanRow);

        return [
            'start_row' => $startRow,
            'header_row' => $headerRow,
            'column_map' => $columnMap,
        ];
    }

    /**
     * Map column letters dynamically across rows 1 through header row.
     */
    protected function resolveColumnMap(Worksheet $worksheet, int $scanMaxRow): array
    {
        $mapping = [];
        $maxCol = 30;

        for ($col = 1; $col <= $maxCol; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $combinedArr = [];
            for ($r = 1; $r <= $scanMaxRow; $r++) {
                $cellVal = trim((string) $worksheet->getCell($colLetter.$r)->getValue());
                if (! empty($cellVal)) {
                    $combinedArr[] = $cellVal;
                }
            }
            $combined = strtolower(implode(' ', $combinedArr));

            if (empty($combined)) {
                continue;
            }

            if (str_contains($combined, 'birthdate') || str_contains($combined, 'date of birth') || str_contains($combined, 'dob')) {
                $mapping['date_of_birth'] = $colLetter;
            } elseif (str_contains($combined, 'last name') || str_contains($combined, 'surname') || str_contains($combined, 'apelyido')) {
                $mapping['last_name'] = $colLetter;
            } elseif (str_contains($combined, 'first name') || str_contains($combined, 'given name') || str_contains($combined, 'pangalan')) {
                $mapping['first_name'] = $colLetter;
            } elseif (str_contains($combined, 'middle name') || str_contains($combined, 'middle initial')) {
                $mapping['middle_name'] = $colLetter;
            } elseif (str_contains($combined, 'suffix') || str_contains($combined, 'extension')) {
                $mapping['suffix'] = $colLetter;
            } elseif (str_contains($combined, 'barangay') || str_contains($combined, 'brgy')) {
                $mapping['barangay'] = $colLetter;
            } elseif (str_contains($combined, 'city') || str_contains($combined, 'municipality')) {
                $mapping['municipality'] = $colLetter;
            } elseif (str_contains($combined, 'province') || str_contains($combined, 'prov')) {
                $mapping['province'] = $colLetter;
            } elseif (str_contains($combined, 'district')) {
                $mapping['district'] = $colLetter;
            } elseif (str_contains($combined, 'type of id') || str_contains($combined, 'id type')) {
                $mapping['government_id_type'] = $colLetter;
            } elseif (str_contains($combined, 'id no') || str_contains($combined, 'id number') || str_contains($combined, 'government id number')) {
                $mapping['government_id_number'] = $colLetter;
            } elseif (str_contains($combined, 'contact') || str_contains($combined, 'mobile') || str_contains($combined, 'phone')) {
                $mapping['contact_number'] = $colLetter;
            } elseif (str_contains($combined, 'e-payment') || str_contains($combined, 'epayment') || str_contains($combined, 'account no')) {
                $mapping['epayment_account_no'] = $colLetter;
            } elseif (str_contains($combined, 'type of beneficiary') || str_contains($combined, 'beneficiary type') || str_contains($combined, 'sector')) {
                $mapping['beneficiary_type'] = $colLetter;
            } elseif (str_contains($combined, 'occupation') || str_contains($combined, 'work') || str_contains($combined, 'hanapbuhay')) {
                $mapping['occupation'] = $colLetter;
            } elseif (str_contains($combined, 'relationship to dependent') || str_contains($combined, 'relationship')) {
                $mapping['dependent_relationship'] = $colLetter;
            } elseif (str_contains($combined, 'dependent') || str_contains($combined, 'name of dependent')) {
                $mapping['dependent_name'] = $colLetter;
            } elseif (str_contains($combined, 'sex') || str_contains($combined, 'gender')) {
                $mapping['sex'] = $colLetter;
            } elseif (str_contains($combined, 'civil status') || str_contains($combined, 'marital status')) {
                $mapping['civil_status'] = $colLetter;
            } elseif (str_contains($combined, 'average monthly income') || str_contains($combined, 'income') || str_contains($combined, 'salary')) {
                $mapping['average_monthly_income'] = $colLetter;
            } elseif (str_contains($combined, 'address') || str_contains($combined, 'purok') || str_contains($combined, 'sitio')) {
                $mapping['address'] = $colLetter;
            } elseif (str_contains($combined, 'no.') || str_contains($combined, 'no')) {
                $mapping['no'] = $colLetter;
            }
        }

        return $mapping;
    }

    /**
     * Check if a row represents footer, legend, summary, or sign-off text.
     */
    public function isFooterRow(Worksheet $worksheet, int $row): bool
    {
        $texts = [];
        for ($col = 1; $col <= 26; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $val = trim((string) $worksheet->getCell($colLetter.$row)->getValue());
            if (! empty($val)) {
                $texts[] = $val;
            }
        }

        $combined = strtolower(implode(' ', $texts));
        if (empty($combined)) {
            return false;
        }

        $keywords = [
            'total', 'total number', 'grand total', 'sub-total', 'sub total', 'total beneficiaries',
            'approved by', 'approved:', 'prepared by', 'prepared:', 'prepared and certified',
            'certified correct', 'certified true', 'certified by', 'noted by', 'notes:', 'legend:',
            'tupad focal person', 'focal person', 'provincial director', 'regional director',
            'signature', 'verified by', 'birthdate: year/month/day', 'birthdate:', 'yyyy/mm/dd',
        ];

        foreach ($keywords as $kw) {
            if (str_contains($combined, $kw)) {
                return true;
            }
        }

        return false;
    }

    protected function isHeaderRow(?string $lastName, ?string $firstName): bool
    {
        $combined = strtolower(trim("{$lastName} {$firstName}"));
        if (empty($combined)) {
            return false;
        }

        $headerTerms = ['last name', 'first name', 'middle name', 'surname', 'given name', 'name of beneficiary'];
        foreach ($headerTerms as $term) {
            if (str_contains($combined, $term)) {
                return true;
            }
        }

        return false;
    }

    protected function getCellValue(Worksheet $worksheet, array $mapping, string $key, int $row, string $fallback): mixed
    {
        $col = $mapping[$key] ?? $fallback;
        if (empty($col)) {
            return null;
        }

        return $worksheet->getCell($col.$row)->getValue();
    }

    protected function cleanString(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }
        $str = trim((string) $val);
        if ($str === '') {
            return null;
        }
        $upper = strtoupper($str);
        if (in_array($upper, ['N/A', 'N / A', 'NA', 'NONE', '-', 'NULL', 'N.A.', 'NOT APPLICABLE'])) {
            return null;
        }

        return $str;
    }

    protected function fallbackNA(?string $val, string $default = 'N/A'): string
    {
        return empty($val) ? $default : $val;
    }

    protected function normalizeContact(mixed $val): ?string
    {
        $str = $this->cleanString($val);
        if (! $str) {
            return 'N/A';
        }
        $digits = preg_replace('/[^0-9]/', '', $str);
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            return '0'.$digits;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            return $digits;
        }

        return $str;
    }

    protected function normalizeCivilStatus(?string $val): ?string
    {
        if (! $val) {
            return null;
        }
        $upper = strtoupper($val);

        return match ($upper) {
            'SINGLE' => 'Single',
            'MARRIED' => 'Married',
            'WIDOW', 'WIDOWED' => 'Widowed',
            'SEPARATED' => 'Separated',
            default => ucwords(strtolower($val)),
        };
    }

    protected function normalizeGovIdType(?string $val): ?string
    {
        if (! $val) {
            return null;
        }
        $upper = strtoupper($val);
        if (str_contains($upper, 'PHILSYS') || str_contains($upper, 'NATIONAL ID')) {
            return 'PhilSys ID';
        }
        if (str_contains($upper, 'UMID')) {
            return 'UMID';
        }
        if (str_contains($upper, 'SSS')) {
            return 'SSS ID';
        }
        if (str_contains($upper, 'GSIS')) {
            return 'GSIS ID';
        }
        if (str_contains($upper, 'VOTER')) {
            return "Voter's ID";
        }
        if (str_contains($upper, 'DRIVER')) {
            return "Driver's License";
        }

        return ucwords(strtolower($val));
    }

    protected function parseDate(mixed $raw): ?Carbon
    {
        if (empty($raw)) {
            return null;
        }
        $str = trim((string) $raw);
        if (empty($str)) {
            return null;
        }

        if (is_numeric($str) && (float) $str > 1000) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject((float) $str));
            } catch (\Throwable) {
                // fallback
            }
        }

        try {
            $norm = str_replace('/', '-', $str);

            return Carbon::parse($norm);
        } catch (\Throwable) {
            return null;
        }
    }
}
