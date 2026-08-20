<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\ImportLog;
use App\Models\Program;
use App\Models\TupadProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class BeneficiaryImportService
{
    public function __construct(
        protected DuplicateDetectionService $duplicateService
    ) {}

    /**
     * Import beneficiaries from Excel/CSV file using dynamic header resolution.
     */
    public function import(string $filePath, string $programCode = 'TUPAD', int $startRow = 16): ImportLog
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

        $program = Program::where('code', strtoupper($programCode))->first();
        if (! $program) {
            $importLog->update([
                'status' => 'failed',
                'error_log' => [['row' => 0, 'error' => "Invalid program code {$programCode}"]],
            ]);

            return $importLog;
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();

        // Resolving Header Column Mapping from Rows 14 and 15
        $columnMap = $this->resolveHeaderMapping($worksheet);

        $imported = 0;
        $failed = 0;
        $skippedFooterRows = 0;
        $errors = [];

        // Data loop starting from specified row (e.g. 16 for Annex D)
        for ($row = $startRow; $row <= $highestRow; $row++) {
            $noVal = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'no', $row, 'A'));
            $lastName = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'last_name', $row, 'B'));
            $firstName = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'first_name', $row, 'C'));
            $middleName = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'middle_name', $row, 'D'));
            $suffix = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'suffix', $row, 'E'));

            // Check if this row is a legend / footer / notes section at the end of the sheet
            if ($this->isFooterOrLegendRow($noVal, $lastName, $firstName)) {
                $skippedFooterRows++;
                // Stop processing further data rows once footer section is reached
                break;
            }

            // Skip table header text rows gracefully (e.g. "Last Name", "Name of Beneficiary")
            if ($this->isHeaderRow($lastName, $firstName)) {
                continue;
            }

            // Skip completely empty blank rows gracefully without error logging
            if (empty($lastName) && empty($firstName) && empty($noVal)) {
                continue;
            }

            $importLog->increment('total_rows');

            try {
                if (empty($lastName) || empty($firstName)) {
                    throw new \Exception('Beneficiary Last Name and First Name are required');
                }

                $rawDob = $this->getMappedValue($worksheet, $columnMap, 'date_of_birth', $row, 'F');
                $dob = $this->parseDate($rawDob);

                if (! $dob) {
                    $cleanDobStr = $this->cleanValue($rawDob) ?? 'blank';
                    throw new \Exception("Invalid Date of Birth: '{$cleanDobStr}'");
                }

                $sexVal = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'sex', $row, 'Q'));
                $sex = 'Male';
                if ($sexVal) {
                    $sexLower = strtolower($sexVal);
                    if (str_starts_with($sexLower, 'f')) {
                        $sex = 'Female';
                    }
                }

                $civilStatus = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'civil_status', $row, 'R'));
                $govIdType = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'government_id_type', $row, 'K'));
                $govIdNumber = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'government_id_number', $row, 'L'));
                $contact = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'contact_number', $row, 'M'));
                $barangay = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'barangay', $row, 'G')) ?? 'Central';
                $municipality = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'municipality', $row, 'H')) ?? 'Malaybalay City';
                $address = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'address', $row, 'I'));

                // Annex D TUPAD extra fields
                $epayment = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'epayment_account_no', $row, 'N'));
                $benType = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'beneficiary_type', $row, 'O'));
                $occupation = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'occupation', $row, 'P'));
                $monthlyIncome = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'average_monthly_income', $row, 'T'));

                // Prepare Beneficiary Payload
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
                    'contact_number' => $contact,
                    'address' => $address,
                    'barangay' => $barangay,
                    'municipality' => $municipality,
                    'program_code' => $programCode,
                    'availment_year' => (int) date('Y'),
                ];

                $age = $dob->age;
                $isSenior = $age >= 60;

                // Perform Duplicate Check before saving
                $dupCheck = $this->duplicateService->checkDuplicates($payload);
                $householdCheck = $this->duplicateService->checkHouseholdDuplicates($payload);

                DB::transaction(function () use ($payload, $firstName, $middleName, $lastName, $suffix, $dob, $isSenior, $program, $epayment, $benType, $occupation, $monthlyIncome, $dupCheck, $householdCheck) {
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
                        'is_senior_citizen' => $isSenior,
                        'created_by' => auth()->id(),
                    ]);

                    $hasDupOrHousehold = $dupCheck['has_duplicates'] || $householdCheck['has_household_flags'];
                    $beneficiaryProgram = BeneficiaryProgram::create([
                        'beneficiary_id' => $beneficiary->id,
                        'program_id' => $program->id,
                        'availment_year' => (int) date('Y'),
                        'status' => $hasDupOrHousehold ? 'pending' : 'approved',
                    ]);

                    if ($program->code === 'TUPAD') {
                        TupadProfile::create([
                            'beneficiary_program_id' => $beneficiaryProgram->id,
                            'project_location_barangay' => $payload['barangay'],
                            'project_location_municipality' => $payload['municipality'],
                            'project_location_province' => 'Bukidnon',
                            'epayment_account_no' => $epayment,
                            'beneficiary_type' => $benType,
                            'occupation' => $occupation,
                            'average_monthly_income' => $monthlyIncome,
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

        $importLog->update([
            'imported_rows' => $imported,
            'failed_rows' => $failed,
            'error_log' => array_merge(
                [['info' => 'Column Mapping: '.json_encode($columnMap), 'skipped_footer_rows' => $skippedFooterRows]],
                $errors
            ),
            'status' => $failed === 0 ? 'completed' : ($imported > 0 ? 'completed' : 'failed'),
        ]);

        AuditLog::log([
            'action' => 'import',
            'model_type' => ImportLog::class,
            'model_id' => $importLog->id,
            'description' => "Imported {$imported} rows from {$fileName} for {$programCode} with {$failed} errors ({$skippedFooterRows} footer notes skipped)",
        ]);

        return $importLog;
    }

    /**
     * Dynamically map column letters by scanning row 14 & row 15 headers.
     */
    protected function resolveHeaderMapping($worksheet): array
    {
        $mapping = [];
        $maxCol = 25; // A to Y

        for ($col = 1; $col <= $maxCol; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $val14 = strtolower(trim((string) $worksheet->getCell($colLetter.'14')->getValue()));
            $val15 = strtolower(trim((string) $worksheet->getCell($colLetter.'15')->getValue()));
            $combined = trim("{$val14} {$val15}");

            if (empty($combined)) {
                continue;
            }

            if (str_contains($combined, 'birthdate') || str_contains($combined, 'date of birth') || str_contains($combined, 'dob')) {
                $mapping['date_of_birth'] = $colLetter;
            } elseif (str_contains($combined, 'last name') || str_contains($combined, 'surname')) {
                $mapping['last_name'] = $colLetter;
            } elseif (str_contains($combined, 'first name') || str_contains($combined, 'given name')) {
                $mapping['first_name'] = $colLetter;
            } elseif (str_contains($combined, 'middle name')) {
                $mapping['middle_name'] = $colLetter;
            } elseif (str_contains($combined, 'extension') || str_contains($combined, 'suffix')) {
                $mapping['suffix'] = $colLetter;
            } elseif (str_contains($combined, 'barangay') || str_contains($combined, 'brgy')) {
                $mapping['barangay'] = $colLetter;
            } elseif (str_contains($combined, 'city') || str_contains($combined, 'municipality')) {
                $mapping['municipality'] = $colLetter;
            } elseif (str_contains($combined, 'type of id') || str_contains($combined, 'id type')) {
                $mapping['government_id_type'] = $colLetter;
            } elseif (str_contains($combined, 'id no') || str_contains($combined, 'id number')) {
                $mapping['government_id_number'] = $colLetter;
            } elseif (str_contains($combined, 'contact') || str_contains($combined, 'mobile')) {
                $mapping['contact_number'] = $colLetter;
            } elseif (str_contains($combined, 'e-payment') || str_contains($combined, 'epayment') || str_contains($combined, 'account no')) {
                $mapping['epayment_account_no'] = $colLetter;
            } elseif (str_contains($combined, 'type of beneficiary') || str_contains($combined, 'beneficiary type')) {
                $mapping['beneficiary_type'] = $colLetter;
            } elseif (str_contains($combined, 'occupation')) {
                $mapping['occupation'] = $colLetter;
            } elseif (str_contains($combined, 'sex') || str_contains($combined, 'gender')) {
                $mapping['sex'] = $colLetter;
            } elseif (str_contains($combined, 'civil status') || str_contains($combined, 'marital status')) {
                $mapping['civil_status'] = $colLetter;
            } elseif (str_contains($combined, 'average monthly income') || str_contains($combined, 'income')) {
                $mapping['average_monthly_income'] = $colLetter;
            } elseif (str_contains($combined, 'no.') || str_contains($combined, 'no')) {
                $mapping['no'] = $colLetter;
            }
        }

        return $mapping;
    }

    /**
     * Retrieve cell value using resolved mapping or fallback column letter.
     */
    protected function getMappedValue($worksheet, array $mapping, string $key, int $row, string $fallbackCol): mixed
    {
        $colLetter = $mapping[$key] ?? $fallbackCol;

        return $worksheet->getCell($colLetter.$row)->getValue();
    }

    /**
     * Check if row is a footer, legend, or notes section at end of table.
     */
    protected function isFooterOrLegendRow(?string $no, ?string $lastName, ?string $firstName): bool
    {
        $texts = array_filter([$no, $lastName, $firstName]);
        $combined = strtolower(implode(' ', $texts));

        if (empty($combined)) {
            return false;
        }

        $keywords = [
            'birthdate:', 'civil status:', 'prepared and certified', 'certified true',
            'note:', 'legend:', 'approved by:', 'noted by:', 'yyyy/mm/dd', 'total number',
            'focal person', 'provincial director', 'field office', 'regional director', 'signature',
            'prepared by',
        ];

        foreach ($keywords as $kw) {
            if (str_contains($combined, $kw)) {
                return true;
            }
        }

        // If 'no' column is present and contains legend text instead of a row number
        if ($no !== null && (str_contains(strtolower($no), 'birthdate') || str_contains(strtolower($no), 'civil status') || str_contains(strtolower($no), 'prepared') || str_contains(strtolower($no), 'focal'))) {
            return true;
        }

        return false;
    }

    /**
     * Check if row contains table header text (e.g. "Last Name", "First Name").
     */
    protected function isHeaderRow(?string $lastName, ?string $firstName): bool
    {
        $combined = strtolower(trim("{$lastName} {$firstName}"));
        if (empty($combined)) {
            return false;
        }

        $headerTerms = [
            'last name', 'first name', 'middle name', 'surname', 'given name',
            'name of beneficiary', 'beneficiary name', 'extension', 'suffix',
        ];

        foreach ($headerTerms as $term) {
            if (str_contains($combined, $term)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clean and normalize raw cell values:
     * - Trims leading/trailing whitespace
     * - Converts literal strings "N/A", "N / A", "NA", "NONE", "-", "NULL", "N.A.", "NOT APPLICABLE" to null on ALL fields!
     */
    protected function cleanValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        $upper = strtoupper($string);
        if (in_array($upper, ['N/A', 'N / A', 'NA', 'NONE', '-', 'NULL', 'N.A.', 'NOT APPLICABLE'])) {
            return null;
        }

        return $string;
    }

    /**
     * Parse date of birth string, Excel serial date integer, or formatted string (YYYY/MM/DD).
     */
    protected function parseDate(mixed $raw): ?Carbon
    {
        if (empty($raw)) {
            return null;
        }

        $rawStr = trim((string) $raw);
        if (empty($rawStr)) {
            return null;
        }

        // Excel numeric serial date (e.g. 36705 -> 2000-06-29)
        if (is_numeric($rawStr) && (float) $rawStr > 1000) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject((float) $rawStr));
            } catch (\Throwable) {
                // fall through
            }
        }

        // Explicit YYYY/MM/DD or YYYY-MM-DD format
        try {
            if (preg_match('/^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/', $rawStr)) {
                $normalized = str_replace('/', '-', $rawStr);

                return Carbon::parse($normalized);
            }

            if (preg_match('/^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}$/', $rawStr)) {
                $normalized = str_replace('/', '-', $rawStr);

                return Carbon::parse($normalized);
            }

            return Carbon::parse($rawStr);
        } catch (\Throwable) {
            return null;
        }
    }
}
