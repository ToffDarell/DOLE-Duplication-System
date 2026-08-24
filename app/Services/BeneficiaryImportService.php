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
     * Import beneficiaries from Excel/CSV file using dynamic header resolution and auto-detected row ranges.
     */
    public function import(string $filePath, string $programCode = 'TUPAD', ?int $startRow = null, ?int $endRow = null): ImportLog
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

        // Auto-detect header row, start row, and column mapping dynamically
        $detection = $this->detectStartRowAndMapping($worksheet, $programCode);
        $columnMap = $detection['column_map'];
        $effectiveStartRow = ($startRow !== null && $startRow > 0) ? $startRow : $detection['start_row'];

        $imported = 0;
        $failed = 0;
        $skippedFooterRows = 0;
        $consecutiveEmptyRows = 0;
        $errors = [];

        // Data loop starting from auto-detected start row, stopping at end_row (if set) or footer/end of sheet
        $lastRow = ($endRow !== null && $endRow <= $highestRow) ? $endRow : $highestRow;
        for ($row = $effectiveStartRow; $row <= $lastRow; $row++) {
            $noVal = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'no', $row, 'A'));
            $lastName = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'last_name', $row, 'B'));
            $firstName = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'first_name', $row, 'C'));
            $middleName = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'middle_name', $row, 'D'));
            $suffix = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'suffix', $row, 'E'));

            // Check if this row is a legend / footer / totals / sign-off section at the end of the sheet
            if ($this->isFooterOrLegendRow($noVal, $lastName, $firstName, $worksheet, $row)) {
                $skippedFooterRows++;
                // Stop reading rows automatically when encountering footer/totals
                break;
            }

            // Skip table header text rows gracefully (e.g. repeated page headers)
            if ($this->isHeaderRow($lastName, $firstName)) {
                continue;
            }

            // Skip blank empty rows gracefully and stop after consecutive empty rows
            if (empty($lastName) && empty($firstName)) {
                $consecutiveEmptyRows++;
                if ($consecutiveEmptyRows >= 4) {
                    // Stop reading after 4 consecutive empty rows
                    break;
                }

                continue;
            }

            $consecutiveEmptyRows = 0;
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

                $rawCivilStatus = $this->getMappedValue($worksheet, $columnMap, 'civil_status', $row, 'R');
                $civilStatus = $this->normalizeCivilStatus($this->cleanValue($rawCivilStatus));

                $rawGovIdType = $this->getMappedValue($worksheet, $columnMap, 'government_id_type', $row, 'K');
                $govIdType = $this->normalizeGovIdType($this->cleanValue($rawGovIdType));

                $rawGovIdNumber = $this->getMappedValue($worksheet, $columnMap, 'government_id_number', $row, 'L');
                $govIdNumber = $this->cleanIdNumber($rawGovIdNumber);

                $rawContact = $this->getMappedValue($worksheet, $columnMap, 'contact_number', $row, 'M');
                $contact = $this->normalizeContactNumber($rawContact);

                $rawBarangay = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'barangay', $row, 'G'));
                $rawMunicipality = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'municipality', $row, 'H'));
                $rawProvince = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'province', $row, 'I'));
                $rawDistrict = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'district', $row, 'J'));
                $rawAddress = isset($columnMap['address']) ? $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'address', $row, '')) : null;

                $municipality = $rawMunicipality ? $this->normalizeMunicipality($rawMunicipality) : 'Pangantucan';
                $barangay = $rawBarangay ? ucwords(strtolower($rawBarangay)) : 'Central';
                $province = $rawProvince ? ucwords(strtolower($rawProvince)) : 'Bukidnon';
                $district = $rawDistrict ? trim($rawDistrict) : null;
                $address = $rawAddress;

                // Annex D TUPAD extra fields
                $epayment = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'epayment_account_no', $row, 'N'));
                $rawBenType = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'beneficiary_type', $row, 'O'));
                $benType = $this->normalizeBeneficiaryType($rawBenType);

                $occupation = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'occupation', $row, 'P'));
                $monthlyIncome = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'average_monthly_income', $row, 'T'));
                $dependentName = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'dependent_name', $row, 'U'));
                $rawDepRelationship = $this->cleanValue($this->getMappedValue($worksheet, $columnMap, 'dependent_relationship', $row, 'V'));
                $dependentRelationship = $this->normalizeRelationship($rawDepRelationship);

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
                    'province' => $province,
                    'district' => $district,
                    'program_code' => $programCode,
                    'availment_year' => (int) date('Y'),
                ];

                $age = $dob->age;
                $isSenior = $age >= 60;

                // Perform Duplicate Check before saving
                $dupCheck = $this->duplicateService->checkDuplicates($payload);
                $householdCheck = $this->duplicateService->checkHouseholdDuplicates($payload);

                DB::transaction(function () use ($payload, $firstName, $middleName, $lastName, $suffix, $dob, $isSenior, $program, $epayment, $benType, $occupation, $monthlyIncome, $dependentName, $dependentRelationship, $province, $district, $dupCheck, $householdCheck) {
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
                            'project_location_province' => $province,
                            'project_location_district' => $district,
                            'epayment_account_no' => $epayment,
                            'beneficiary_type' => $benType,
                            'occupation' => $occupation,
                            'average_monthly_income' => $monthlyIncome,
                            'dependent_name' => $dependentName,
                            'dependent_relationship' => $dependentRelationship,
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
     * Auto-detect start data row, header rows, and column mapping dynamically.
     * Works universally across all programs (TUPAD, SPES, DILP, GIP).
     */
    public function detectStartRowAndMapping($worksheet, string $programCode = 'TUPAD'): array
    {
        $highestRow = $worksheet->getHighestRow();
        $scanMaxRow = min(50, $highestRow);
        $headerRow = null;

        // Scan rows to find the primary table header
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

            // Check if this row contains core header keywords
            $hasNameKeyword = str_contains($rowText, 'last name')
                || str_contains($rowText, 'first name')
                || str_contains($rowText, 'surname')
                || str_contains($rowText, 'given name')
                || str_contains($rowText, 'name of beneficiary')
                || str_contains($rowText, 'beneficiary name')
                || str_contains($rowText, 'full name')
                || str_contains($rowText, 'apelyido')
                || str_contains($rowText, 'pangalan');

            $hasStructureKeyword = (str_contains($rowText, 'no.') || str_contains($rowText, 'no') || str_contains($rowText, 'seq'))
                && (str_contains($rowText, 'sex') || str_contains($rowText, 'birth') || str_contains($rowText, 'dob') || str_contains($rowText, 'address') || str_contains($rowText, 'barangay') || str_contains($rowText, 'municipality') || str_contains($rowText, 'status'));

            if ($hasNameKeyword || $hasStructureKeyword) {
                $headerRow = $r;

                // Check if the immediately following row is a sub-header (e.g. Annex D Rows 14 & 15)
                if ($r < $scanMaxRow) {
                    $nextRowVals = [];
                    for ($c = 1; $c <= 26; $c++) {
                        $cLetter = Coordinate::stringFromColumnIndex($c);
                        $v = trim((string) $worksheet->getCell($cLetter.($r + 1))->getValue());
                        if (! empty($v)) {
                            $nextRowVals[] = strtolower($v);
                        }
                    }
                    $nextRowText = implode(' ', $nextRowVals);
                    if (str_contains($nextRowText, 'last name') || str_contains($nextRowText, 'first name') || str_contains($nextRowText, 'middle name') || str_contains($nextRowText, 'ext')) {
                        $headerRow = $r + 1;
                    }
                }

                break;
            }
        }

        // Determine Start Row
        if ($headerRow !== null) {
            $startRow = $headerRow + 1;
            $mappingScanRow = $headerRow;
        } else {
            // Safe fallback when header row is not found
            $startRow = ($programCode === 'TUPAD' && $highestRow >= 20) ? 16 : 2;
            $mappingScanRow = min(20, $highestRow);
        }

        $columnMap = $this->resolveHeaderMapping($worksheet, $mappingScanRow);

        return [
            'start_row' => $startRow,
            'header_row' => $headerRow,
            'column_map' => $columnMap,
        ];
    }

    /**
     * Dynamically map column letters by scanning rows 1 through the header row.
     */
    protected function resolveHeaderMapping($worksheet, int $scanMaxRow = 20): array
    {
        $mapping = [];
        $maxCol = 30; // Columns A to AD

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
            } elseif (str_contains($combined, 'middle name') || str_contains($combined, 'middle initial') || str_contains($combined, 'm.i.')) {
                $mapping['middle_name'] = $colLetter;
            } elseif (str_contains($combined, 'extension') || str_contains($combined, 'suffix') || str_contains($combined, 'exten') || str_contains($combined, 'ext.')) {
                $mapping['suffix'] = $colLetter;
            } elseif (str_contains($combined, 'barangay') || str_contains($combined, 'brgy')) {
                $mapping['barangay'] = $colLetter;
            } elseif (str_contains($combined, 'city') || str_contains($combined, 'municipality') || str_contains($combined, 'mun.')) {
                $mapping['municipality'] = $colLetter;
            } elseif (str_contains($combined, 'province') || str_contains($combined, 'prov')) {
                $mapping['province'] = $colLetter;
            } elseif (str_contains($combined, 'district') || str_contains($combined, 'distric') || str_contains($combined, 'dist.')) {
                $mapping['district'] = $colLetter;
            } elseif (str_contains($combined, 'type of id') || str_contains($combined, 'id type') || str_contains($combined, 'kind of id') || str_contains($combined, 'gov id type') || str_contains($combined, 'government id type')) {
                $mapping['government_id_type'] = $colLetter;
            } elseif (str_contains($combined, 'id no') || str_contains($combined, 'id number') || str_contains($combined, 'id #') || str_contains($combined, 'government id number') || str_contains($combined, 'id_number') || str_contains($combined, 'id_no')) {
                $mapping['government_id_number'] = $colLetter;
            } elseif (str_contains($combined, 'contact') || str_contains($combined, 'mobile') || str_contains($combined, 'phone') || str_contains($combined, 'cellphone') || str_contains($combined, 'tel no')) {
                $mapping['contact_number'] = $colLetter;
            } elseif (str_contains($combined, 'e-payment') || str_contains($combined, 'epayment') || str_contains($combined, 'account no')) {
                $mapping['epayment_account_no'] = $colLetter;
            } elseif (str_contains($combined, 'type of beneficiary') || str_contains($combined, 'beneficiary type') || str_contains($combined, 'sector') || str_contains($combined, 'classification')) {
                $mapping['beneficiary_type'] = $colLetter;
            } elseif (str_contains($combined, 'occupation') || str_contains($combined, 'job') || str_contains($combined, 'work') || str_contains($combined, 'hanapbuhay')) {
                $mapping['occupation'] = $colLetter;
            } elseif (str_contains($combined, 'relationship to dependent') || str_contains($combined, 'relationship with dependent') || str_contains($combined, 'relationship') || str_contains($combined, 'rel to dep') || str_contains($combined, 'dependent relationship')) {
                $mapping['dependent_relationship'] = $colLetter;
            } elseif (str_contains($combined, 'dependent') || str_contains($combined, 'dependents') || str_contains($combined, 'name of dependent')) {
                $mapping['dependent_name'] = $colLetter;
            } elseif (str_contains($combined, 'sex') || str_contains($combined, 'gender') || str_contains($combined, 'kasarian')) {
                $mapping['sex'] = $colLetter;
            } elseif (str_contains($combined, 'civil status') || str_contains($combined, 'marital status')) {
                $mapping['civil_status'] = $colLetter;
            } elseif (str_contains($combined, 'average monthly income') || str_contains($combined, 'monthly income') || str_contains($combined, 'average income') || str_contains($combined, 'income') || str_contains($combined, 'salary')) {
                $mapping['average_monthly_income'] = $colLetter;
            } elseif (str_contains($combined, 'address') || str_contains($combined, 'purok') || str_contains($combined, 'sitio') || str_contains($combined, 'tirahan')) {
                $mapping['address'] = $colLetter;
            } elseif (str_contains($combined, 'no.') || str_contains($combined, 'no') || str_contains($combined, 'item')) {
                $mapping['no'] = $colLetter;
            }
        }

        return $mapping;
    }

    /**
     * Standardize municipality name against Bukidnon list (case-insensitive).
     */
    protected function normalizeMunicipality(string $muni): string
    {
        $muniTrim = trim($muni);
        $known = [
            'Baungon', 'Cabanglasan', 'Damulog', 'Dangcagan', 'Don Carlos',
            'Impasugong', 'Kadingilan', 'Kibawe', 'Kitaotao', 'Lantapan',
            'Libona', 'Malaybalay City', 'Maramag', 'Manolo Fortich',
            'Pangantucan', 'Quezon', 'San Fernando', 'Sumilao',
            'Talakag', 'Valencia City',
        ];

        foreach ($known as $k) {
            if (strcasecmp($k, $muniTrim) === 0 || strcasecmp(str_replace(' City', '', $k), str_replace(' City', '', $muniTrim)) === 0) {
                return $k;
            }
        }

        return ucwords(strtolower($muniTrim));
    }

    /**
     * Normalize civil status to standard casing.
     */
    protected function normalizeCivilStatus(?string $val): ?string
    {
        if (! $val) {
            return null;
        }
        $trimmed = trim($val);
        $upper = strtoupper($trimmed);

        return match ($upper) {
            'SINGLE', 'SOLO' => 'Single',
            'MARRIED' => 'Married',
            'WIDOW', 'WIDOWED' => 'Widowed',
            'SEPARATED', 'DIVORCED', 'ANNULLED' => 'Separated',
            default => ucwords(strtolower($trimmed)),
        };
    }

    /**
     * Normalize Government ID Type to standard options.
     */
    protected function normalizeGovIdType(?string $val): ?string
    {
        if (! $val) {
            return null;
        }
        $trimmed = trim($val);
        $upper = strtoupper($trimmed);

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
        if (str_contains($upper, 'TIN')) {
            return 'TIN Card';
        }
        if (str_contains($upper, 'PAG-IBIG') || str_contains($upper, 'PAGIBIG') || str_contains($upper, 'HDMF')) {
            return 'Pag-IBIG ID';
        }
        if (str_contains($upper, 'PHILHEALTH')) {
            return 'PhilHealth ID';
        }
        if (str_contains($upper, 'VOTER')) {
            return "Voter's ID";
        }
        if (str_contains($upper, 'DRIVER')) {
            return "Driver's License";
        }
        if (str_contains($upper, 'PASSPORT')) {
            return 'Passport';
        }
        if (str_contains($upper, 'SENIOR')) {
            return 'Senior Citizen ID';
        }
        if (str_contains($upper, 'PWD')) {
            return 'PWD ID';
        }
        if (str_contains($upper, 'POSTAL')) {
            return 'Postal ID';
        }
        if (str_contains($upper, 'BARANGAY') || str_contains($upper, 'BRGY')) {
            return 'Barangay ID';
        }
        if (str_contains($upper, 'PRC')) {
            return 'PRC ID';
        }
        if (str_contains($upper, 'OWWA')) {
            return 'OWWA ID';
        }
        if (str_contains($upper, 'SOLO PARENT')) {
            return 'Solo Parent ID';
        }
        if (str_contains($upper, 'NBI')) {
            return 'NBI Clearance';
        }
        if (str_contains($upper, 'POLICE')) {
            return 'Police Clearance';
        }
        if (str_contains($upper, 'STUDENT') || str_contains($upper, 'SCHOOL')) {
            return 'Student ID';
        }

        return ucwords(strtolower($trimmed));
    }

    /**
     * Clean and preserve exact ID Number string formatting.
     */
    protected function cleanIdNumber(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }
        $str = trim((string) $val);
        if ($str === '') {
            return null;
        }
        $upper = strtoupper($str);
        if (in_array($upper, ['N/A', 'N / A', 'NA', 'NONE', '-', 'NULL', 'N.A.', 'NOT APPLICABLE', '0', '0000', '00000'])) {
            return null;
        }

        return $str;
    }

    /**
     * Normalize Contact Number: format 10-digit 9xxxxxxxxx to 09xxxxxxxxx or preserve N/A.
     */
    protected function normalizeContactNumber(mixed $val): ?string
    {
        if ($val === null) {
            return 'N/A';
        }
        $str = trim((string) $val);
        if ($str === '') {
            return 'N/A';
        }
        $upper = strtoupper($str);
        if (in_array($upper, ['N/A', 'N / A', 'NA', 'NONE', '-', 'NULL', 'N.A.', 'NOT APPLICABLE'])) {
            return 'N/A';
        }

        $digits = preg_replace('/[^0-9]/', '', $str);
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            return '0'.$digits;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            return $digits;
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '639')) {
            return '0'.substr($digits, 2);
        }
        if (strlen($digits) === 13 && str_starts_with($digits, '0639')) {
            return '0'.substr($digits, 3);
        }

        return $str;
    }

    /**
     * Normalize Beneficiary Sector Type.
     */
    protected function normalizeBeneficiaryType(?string $val): ?string
    {
        if (! $val) {
            return null;
        }
        $trimmed = trim($val);
        $upper = strtoupper($trimmed);

        if (str_contains($upper, 'INDIGENOUS') || $upper === 'IP') {
            return 'Indigenous People';
        }
        if (str_contains($upper, 'SELF-EMPLOYED') || str_contains($upper, 'SELF EMPLOYED')) {
            return 'Self-Employed';
        }
        if (str_contains($upper, 'UNDEREMPLOYED')) {
            return 'Underemployed';
        }
        if (str_contains($upper, 'DISPLACED') || str_contains($upper, 'UNEMPLOYED')) {
            return 'Displaced Worker';
        }
        if (str_contains($upper, 'SEASONAL')) {
            return 'Seasonal Worker';
        }
        if (str_contains($upper, 'SOLO PARENT')) {
            return 'Solo Parent';
        }
        if (str_contains($upper, 'DISABILITY') || $upper === 'PWD') {
            return 'PWD';
        }
        if (str_contains($upper, 'SENIOR')) {
            return 'Senior Citizen';
        }
        if (str_contains($upper, 'OFW')) {
            return 'Returning OFW';
        }
        if (str_contains($upper, 'FARMER') || str_contains($upper, 'FISHER')) {
            return 'Farmer / Fisherfolk';
        }
        if (str_contains($upper, 'STUDENT') || str_contains($upper, 'YOUTH')) {
            return 'Youth / Student';
        }

        return ucwords(strtolower($trimmed));
    }

    /**
     * Normalize Relationship to Dependent.
     */
    protected function normalizeRelationship(?string $val): ?string
    {
        if (! $val) {
            return null;
        }
        $trimmed = trim($val);
        $upper = strtoupper($trimmed);

        return match ($upper) {
            'WIFE' => 'Wife',
            'HUSBAND' => 'Husband',
            'SPOUSE' => 'Spouse',
            'SON' => 'Son',
            'DAUGHTER' => 'Daughter',
            'CHILD', 'CHILDREN' => 'Child',
            'MOTHER', 'MOM', 'NANAY' => 'Mother',
            'FATHER', 'DAD', 'TATAY' => 'Father',
            'PARENT', 'PARENTS' => 'Parent',
            'BROTHER' => 'Brother',
            'SISTER' => 'Sister',
            'SIBLING', 'SIBLINGS' => 'Sibling',
            'LIVE-IN PARTNER', 'LIVE IN PARTNER', 'PARTNER' => 'Live-in Partner',
            'GRANDCHILD', 'GRANDSON', 'GRANDDAUGHTER' => 'Grandchild',
            'OTHER' => 'Other',
            default => ucwords(strtolower($trimmed)),
        };
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
     * Check if row is a footer, legend, totals, or sign-off section at end of table.
     */
    protected function isFooterOrLegendRow(?string $no, ?string $lastName, ?string $firstName, $worksheet = null, ?int $row = null): bool
    {
        $texts = array_filter([$no, $lastName, $firstName]);

        // If worksheet and row are provided, scan columns A to Z for footer keywords
        if ($worksheet !== null && $row !== null) {
            for ($col = 1; $col <= 26; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $cellVal = trim((string) $worksheet->getCell($colLetter.$row)->getValue());
                if (! empty($cellVal)) {
                    $texts[] = $cellVal;
                }
            }
        }

        $combined = strtolower(implode(' ', $texts));

        if (empty($combined)) {
            return false;
        }

        $keywords = [
            'total', 'total number', 'grand total', 'sub-total', 'sub total', 'total beneficiaries',
            'approved by', 'approved:', 'prepared by', 'prepared:', 'prepared and certified',
            'certified correct', 'certified true', 'certified by', 'noted by', 'note:', 'legend:',
            'focal person', 'provincial director', 'regional director', 'field office',
            'signature', 'verified by', 'recommending approval', 'summary', 'birthdate:', 'civil status:', 'yyyy/mm/dd',
        ];

        foreach ($keywords as $kw) {
            if (str_contains($combined, $kw)) {
                return true;
            }
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
