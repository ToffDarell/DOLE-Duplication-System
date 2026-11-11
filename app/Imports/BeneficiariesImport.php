<?php

namespace App\Imports;

use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\ImportLog;
use App\Models\Program;
use App\Models\SystemSetting;
use App\Models\TupadProfile;
use App\Services\BeneficiaryImportService;
use App\Services\DuplicateDetectionService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    protected ?int $projectId = null;

    protected ?BeneficiaryImportService $importService = null;

    protected ?DuplicateDetectionService $duplicateService = null;

    public function __construct(string $programCode = 'TUPAD', ?int $startRow = null, ?int $endRow = null, ?int $projectId = null)
    {
        $this->projectId = $projectId;
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

        // 2. Choose a processing strategy. Large masterlists use a batched path
        //    that inserts rows first and runs the duplicate engine once at the
        //    end — instead of scanning the whole table on every single row.
        $estimatedRows = max(0, $effectiveEndRow - $effectiveStartRow + 1);
        $bulkThreshold = (int) SystemSetting::get('bulk_import_threshold', 600);
        $useBulkMode = $bulkThreshold > 0 && $estimatedRows >= $bulkThreshold;

        $extraLog = [];
        if ($useBulkMode) {
            [$imported, $failed, $errors, $note] = $this->processRowsBulk($importLog, $worksheet, $program, $columnMap, $effectiveStartRow, $effectiveEndRow);
            $extraLog[] = ['info' => $note];
        } else {
            [$imported, $failed, $errors] = $this->processRowsStandard($importLog, $worksheet, $program, $columnMap, $effectiveStartRow, $effectiveEndRow);
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $status = $failed === 0 ? 'completed' : ($imported > 0 ? 'completed' : 'failed');
        $importLog->update([
            'status' => $status,
            'imported_rows' => $imported,
            'failed_rows' => $failed,
            'error_log' => array_merge($extraLog, $errors),
        ]);

        return $importLog;
    }

    /**
     * Original one-row-at-a-time path: duplicate scan + insert per row.
     * Used for normal-sized masterlists (below the bulk threshold).
     *
     * @return array{0: int, 1: int, 2: array<int, array<string, mixed>>}
     */
    private function processRowsStandard(ImportLog $importLog, Worksheet $worksheet, Program $program, array $columnMap, int $startRow, int $endRow): array
    {
        $imported = 0;
        $failed = 0;
        $consecutiveEmpty = 0;
        $errors = [];

        for ($row = $startRow; $row <= $endRow; $row++) {
            $parsed = $this->parseRow($worksheet, $columnMap, $row);

            if ($parsed['status'] === 'footer') {
                break;
            }

            if ($parsed['status'] === 'header') {
                continue;
            }

            if ($parsed['status'] === 'empty') {
                if (++$consecutiveEmpty >= 3) {
                    break;
                }

                continue;
            }

            $consecutiveEmpty = 0;
            $importLog->increment('total_rows');

            if ($parsed['status'] === 'error') {
                $failed++;
                $errors[] = ['row' => $row, 'name' => "{$parsed['last_name']}, {$parsed['first_name']}", 'error' => $parsed['error']];

                continue;
            }

            try {
                $payload = $parsed['payload'];
                $dupCheck = $this->duplicateService->checkDuplicates($payload);
                $householdCheck = $this->duplicateService->checkHouseholdDuplicates($payload);

                DB::transaction(function () use ($parsed, $program, $dupCheck, $householdCheck) {
                    $this->persistRow($parsed, $program, $dupCheck, $householdCheck);
                });

                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = ['row' => $row, 'name' => "{$parsed['payload']['last_name']}, {$parsed['payload']['first_name']}", 'error' => $e->getMessage()];
            }
        }

        return [$imported, $failed, $errors];
    }

    /**
     * Fast path for large masterlists: parse everything, insert row by row
     * without touching the duplicate engine, then run the engine once over the
     * whole table and flag/downgrade the affected rows.
     *
     * @return array{0: int, 1: int, 2: array<int, array<string, mixed>>, 3: string}
     */
    private function processRowsBulk(ImportLog $importLog, Worksheet $worksheet, Program $program, array $columnMap, int $startRow, int $endRow): array
    {
        // Phase 1 — parse every row (pure PHP, no DB).
        $parsedRows = [];
        $failed = 0;
        $errors = [];
        $consecutiveEmpty = 0;
        $seen = 0;

        for ($row = $startRow; $row <= $endRow; $row++) {
            $parsed = $this->parseRow($worksheet, $columnMap, $row);

            if ($parsed['status'] === 'footer') {
                break;
            }

            if ($parsed['status'] === 'header') {
                continue;
            }

            if ($parsed['status'] === 'empty') {
                if (++$consecutiveEmpty >= 3) {
                    break;
                }

                continue;
            }

            $consecutiveEmpty = 0;
            $seen++;

            if ($parsed['status'] === 'error') {
                $failed++;
                $errors[] = ['row' => $row, 'name' => "{$parsed['last_name']}, {$parsed['first_name']}", 'error' => $parsed['error']];

                continue;
            }

            $parsed['_row'] = $row;
            $parsedRows[] = $parsed;
        }

        $importLog->update(['total_rows' => $seen]);

        if (empty($parsedRows)) {
            return [0, $failed, $errors, 'Fast bulk import: no valid rows to insert.'];
        }

        // Phase 2 — bulk insert beneficiaries, then their program (+ TUPAD) rows.
        // No Eloquent events / mutators are involved on these models, so raw
        // multi-row inserts are safe as long as timestamps + full_name are set.
        $timestamp = now()->toDateTimeString();
        $userId = auth()->id();
        $currentYear = (int) date('Y');
        $isTupad = $program->code === 'TUPAD';

        $beneficiaryRows = array_map(function (array $parsed) use ($timestamp, $userId) {
            $p = $parsed['payload'];

            return [
                'full_name' => $parsed['full_name'],
                'first_name' => $p['first_name'],
                'middle_name' => $p['middle_name'],
                'last_name' => $p['last_name'],
                'suffix' => $p['suffix'],
                'date_of_birth' => $p['date_of_birth'],
                'sex' => $p['sex'],
                'civil_status' => $p['civil_status'],
                'government_id_type' => $p['government_id_type'],
                'government_id_number' => $p['government_id_number'],
                'contact_number' => $p['contact_number'],
                'address' => $p['address'],
                'barangay' => $p['barangay'],
                'municipality' => $p['municipality'],
                'province' => $p['province'],
                'district' => $p['district'],
                'is_senior_citizen' => $parsed['is_senior'] ? 1 : 0,
                'created_by' => $userId,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }, $parsedRows);

        $maxBeneficiaryId = (int) Beneficiary::max('id');

        $projectId = $this->projectId;

        DB::transaction(function () use ($beneficiaryRows, $parsedRows, $program, $currentYear, $isTupad, $timestamp, $maxBeneficiaryId, $projectId) {
            foreach (array_chunk($beneficiaryRows, 500) as $chunk) {
                Beneficiary::insert($chunk);
            }

            $newBeneficiaryIds = Beneficiary::where('id', '>', $maxBeneficiaryId)->orderBy('id')->pluck('id')->all();

            $programRows = array_map(fn ($beneficiaryId) => [
                'beneficiary_id' => $beneficiaryId,
                'program_id' => $program->id,
                'project_id' => $projectId,
                'availment_year' => $currentYear,
                'status' => 'approved',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], $newBeneficiaryIds);

            $maxProgramId = (int) BeneficiaryProgram::max('id');
            foreach (array_chunk($programRows, 500) as $chunk) {
                BeneficiaryProgram::insert($chunk);
            }

            if ($isTupad) {
                $newProgramIds = BeneficiaryProgram::where('id', '>', $maxProgramId)->orderBy('id')->pluck('id')->all();

                $tupadRows = [];
                foreach ($newProgramIds as $i => $programRowId) {
                    $tupadRows[] = array_merge(
                        ['beneficiary_program_id' => $programRowId, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                        $parsedRows[$i]['tupad'],
                    );
                }

                foreach (array_chunk($tupadRows, 500) as $chunk) {
                    TupadProfile::insert($chunk);
                }
            }
        });

        $newBeneficiaryIds = Beneficiary::where('id', '>', $maxBeneficiaryId)->orderBy('id')->pluck('id')->all();
        $imported = count($newBeneficiaryIds);

        $importedPayloads = [];
        foreach ($newBeneficiaryIds as $i => $beneficiaryId) {
            $importedPayloads[$beneficiaryId] = $parsedRows[$i]['payload'];
        }

        // Phase 3 — run the duplicate engine once for the whole imported set.
        $note = $this->runDeferredDuplicateScan($importedPayloads);

        return [$imported, $failed, $errors, $note];
    }

    /**
     * Read and normalise a single spreadsheet row into a structured result.
     *
     * @return array{status: string, last_name?: string, first_name?: string, error?: string,
     *               payload?: array<string, mixed>, tupad?: array<string, mixed>,
     *               is_senior?: bool, full_name?: string}
     */
    private function parseRow(Worksheet $worksheet, array $columnMap, int $row): array
    {
        if ($this->isFooterRow($worksheet, $row)) {
            return ['status' => 'footer'];
        }

        $lastName = $this->cleanString($this->getCellValue($worksheet, $columnMap, 'last_name', $row, 'B'));
        $firstName = $this->cleanString($this->getCellValue($worksheet, $columnMap, 'first_name', $row, 'C'));

        if (empty($lastName) && empty($firstName)) {
            return ['status' => 'empty'];
        }

        if ($this->isHeaderRow($lastName, $firstName)) {
            return ['status' => 'header'];
        }

        if (empty($lastName) || empty($firstName)) {
            return [
                'status' => 'error',
                'last_name' => $lastName ?? '',
                'first_name' => $firstName ?? '',
                'error' => 'Beneficiary Last Name and First Name are required',
            ];
        }

        $middleName = $this->cleanString($this->getCellValue($worksheet, $columnMap, 'middle_name', $row, 'D'));
        $suffix = $this->cleanString($this->getCellValue($worksheet, $columnMap, 'suffix', $row, 'E'));

        $dobRaw = $this->getCellValue($worksheet, $columnMap, 'date_of_birth', $row, 'F');
        $dob = $this->parseDate($dobRaw);
        if (! $dob) {
            return [
                'status' => 'error',
                'last_name' => $lastName,
                'first_name' => $firstName,
                'error' => "Invalid or missing Date of Birth: '".($dobRaw ?? 'blank')."'",
            ];
        }

        $sexRaw = strtolower((string) $this->getCellValue($worksheet, $columnMap, 'sex', $row, 'Q'));
        $sex = str_starts_with(trim($sexRaw), 'f') ? 'Female' : 'Male';

        $civilStatus = $this->fallbackNA($this->normalizeCivilStatus($this->cleanString($this->getCellValue($worksheet, $columnMap, 'civil_status', $row, 'R'))));
        $govIdType = $this->fallbackNA($this->normalizeGovIdType($this->cleanString($this->getCellValue($worksheet, $columnMap, 'government_id_type', $row, 'K'))));
        $govIdNumber = $this->fallbackNA($this->cleanString($this->getCellValue($worksheet, $columnMap, 'government_id_number', $row, 'L')));
        $contactNumber = $this->fallbackNA($this->normalizeContact($this->getCellValue($worksheet, $columnMap, 'contact_number', $row, 'M')));

        $barangay = $this->fallbackNA($this->cleanString($this->getCellValue($worksheet, $columnMap, 'barangay', $row, 'G')), 'Central');
        $municipality = Beneficiary::canonicalMunicipality(
            $this->cleanString($this->getCellValue($worksheet, $columnMap, 'municipality', $row, 'H'))
        ) ?? 'Pangantucan';
        $province = $this->fallbackNA($this->cleanString($this->getCellValue($worksheet, $columnMap, 'province', $row, 'I')), 'Bukidnon');
        $district = $this->cleanString($this->getCellValue($worksheet, $columnMap, 'district', $row, 'J'));
        $address = $this->cleanString($this->getCellValue($worksheet, $columnMap, 'address', $row, ''));

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

        return [
            'status' => 'ok',
            'payload' => $payload,
            'full_name' => Beneficiary::buildFullName($firstName, $middleName, $lastName, $suffix),
            'is_senior' => $dob->age >= 60,
            'tupad' => [
                'project_location_barangay' => $barangay,
                'project_location_municipality' => $municipality,
                'project_location_province' => $province,
                'project_location_district' => $district,
                'epayment_account_no' => $epayment,
                'beneficiary_type' => $benType,
                'occupation' => $occupation,
                'average_monthly_income' => $monthlyIncome,
                'dependent_name' => $dependentName,
                'dependent_relationship' => $dependentRel,
            ],
        ];
    }

    /**
     * Persist one parsed row: beneficiary + program (+ TUPAD profile + flags).
     *
     * @param  array<string, mixed>  $parsed
     * @param  array{has_duplicates: bool, flags: array}  $dupCheck
     * @param  array{has_household_flags: bool, flags: array}  $householdCheck
     */
    private function persistRow(array $parsed, Program $program, array $dupCheck, array $householdCheck): Beneficiary
    {
        $payload = $parsed['payload'];

        $beneficiary = Beneficiary::create([
            'full_name' => $parsed['full_name'],
            'first_name' => $payload['first_name'],
            'middle_name' => $payload['middle_name'],
            'last_name' => $payload['last_name'],
            'suffix' => $payload['suffix'],
            'date_of_birth' => $payload['date_of_birth'],
            'sex' => $payload['sex'],
            'civil_status' => $payload['civil_status'],
            'government_id_type' => $payload['government_id_type'],
            'government_id_number' => $payload['government_id_number'],
            'contact_number' => $payload['contact_number'],
            'address' => $payload['address'],
            'barangay' => $payload['barangay'],
            'municipality' => $payload['municipality'],
            'province' => $payload['province'],
            'district' => $payload['district'],
            'is_senior_citizen' => $parsed['is_senior'],
            'created_by' => auth()->id(),
        ]);

        $hasFlags = $dupCheck['has_duplicates'] || $householdCheck['has_household_flags'];

        $beneficiaryProgram = BeneficiaryProgram::create([
            'beneficiary_id' => $beneficiary->id,
            'program_id' => $program->id,
            'project_id' => $this->projectId,
            'availment_year' => (int) date('Y'),
            'status' => $hasFlags ? 'pending' : 'approved',
        ]);

        if ($program->code === 'TUPAD') {
            TupadProfile::create(array_merge(
                ['beneficiary_program_id' => $beneficiaryProgram->id],
                $parsed['tupad'],
            ));
        }

        if ($dupCheck['has_duplicates']) {
            $this->duplicateService->recordDuplicateFlags($beneficiary, $dupCheck['flags'], 'pending');
        }

        if ($householdCheck['has_household_flags']) {
            $this->duplicateService->recordDuplicateFlags($beneficiary, $householdCheck['flags'], 'pending');
        }

        return $beneficiary;
    }

    /**
     * Run the duplicate engine once over the beneficiary table for a set of
     * freshly imported rows: record flags and downgrade flagged rows to
     * "pending" — the same end state the per-row path produces.
     *
     * To stay fast on large files each imported row is only scored against
     * candidates that share a name sound (SOUNDEX of the surname / first name /
     * surname tokens) rather than against every beneficiary.
     *
     * @param  array<int, array<string, mixed>>  $importedPayloads  [beneficiaryId => payload]
     */
    private function runDeferredDuplicateScan(array $importedPayloads): string
    {
        $count = count($importedPayloads);
        if ($count === 0) {
            return 'Fast bulk import: no rows available to scan for duplicates.';
        }

        $candidates = Beneficiary::with(['beneficiaryPrograms.program'])->get();
        $index = $this->indexCandidatesByNameSound($candidates);
        $flaggedIds = [];

        foreach ($importedPayloads as $id => $payload) {
            $beneficiary = $candidates->firstWhere('id', $id);
            if (! $beneficiary) {
                continue;
            }

            $pool = $this->candidatesForPayload($payload, $index);

            $dupCheck = $this->duplicateService->checkDuplicates($payload, (int) $id, $pool);
            $householdCheck = $this->duplicateService->checkHouseholdDuplicates($payload, (int) $id, $pool);

            if ($dupCheck['has_duplicates']) {
                $this->duplicateService->recordDuplicateFlags($beneficiary, $dupCheck['flags'], 'pending');
                $flaggedIds[$id] = true;
            }

            if ($householdCheck['has_household_flags']) {
                $this->duplicateService->recordDuplicateFlags($beneficiary, $householdCheck['flags'], 'pending');
                $flaggedIds[$id] = true;
            }
        }

        if (! empty($flaggedIds)) {
            BeneficiaryProgram::whereIn('beneficiary_id', array_keys($flaggedIds))
                ->where('status', 'approved')
                ->update(['status' => 'pending']);
        }

        return sprintf(
            'Fast bulk import: %d row(s) inserted in batch, duplicate engine run once afterwards — %d flagged for review.',
            $count,
            count($flaggedIds),
        );
    }

    /**
     * Bucket every candidate by exact name keys and SOUNDEX of its surname,
     * first name, and tokens, so a payload can pull all plausibly-similar records
     * without excluding exact surname + first_name matches.
     *
     * @param  Collection<int, Beneficiary>  $candidates
     * @return array{
     *     exact_full: array<string, list<Beneficiary>>,
     *     exact_last: array<string, list<Beneficiary>>,
     *     exact_first: array<string, list<Beneficiary>>,
     *     last: array<string, list<Beneficiary>>,
     *     first: array<string, list<Beneficiary>>
     * }
     */
    private function indexCandidatesByNameSound(Collection $candidates): array
    {
        $byExactFull = [];
        $byExactLast = [];
        $byExactFirst = [];
        $byLast = [];
        $byFirst = [];

        foreach ($candidates as $candidate) {
            $rawLast = mb_strtolower(trim((string) $candidate->last_name));
            $rawFirst = mb_strtolower(trim((string) $candidate->first_name));
            $normLast = DuplicateDetectionService::normalizeNameString($candidate->last_name);
            $normFirst = DuplicateDetectionService::normalizeNameString($candidate->first_name);
            $cleanLast = preg_replace('/[^a-z0-9]/', '', mb_strtolower(Str::ascii($normLast)));
            $cleanFirst = preg_replace('/[^a-z0-9]/', '', mb_strtolower(Str::ascii($normFirst)));

            // 1. Exact surname + first_name compound bucket (both raw and normalized)
            if ($rawLast !== '' && $rawFirst !== '') {
                $byExactFull[$rawLast.'|'.$rawFirst][] = $candidate;
            }
            if ($normLast !== '' && $normFirst !== '') {
                $byExactFull[$normLast.'|'.$normFirst][] = $candidate;
            }
            if ($cleanLast !== '' && $cleanFirst !== '') {
                $byExactFull[$cleanLast.'|'.$cleanFirst][] = $candidate;
            }

            // 2. Exact surname bucket
            if ($rawLast !== '') {
                $byExactLast[$rawLast][] = $candidate;
            }
            if ($normLast !== '') {
                $byExactLast[$normLast][] = $candidate;
            }
            if ($cleanLast !== '') {
                $byExactLast[$cleanLast][] = $candidate;
            }

            // 3. Exact first name bucket
            if ($rawFirst !== '') {
                $byExactFirst[$rawFirst][] = $candidate;
            }
            if ($normFirst !== '') {
                $byExactFirst[$normFirst][] = $candidate;
            }
            if ($cleanFirst !== '') {
                $byExactFirst[$cleanFirst][] = $candidate;
            }

            // 4. Soundex buckets
            foreach ($this->nameSoundKeys($normLast !== '' ? $normLast : $candidate->last_name) as $key) {
                $byLast[$key][] = $candidate;
            }
            foreach ($this->nameSoundKeys($normFirst !== '' ? $normFirst : $candidate->first_name) as $key) {
                $byFirst[$key][] = $candidate;
            }
        }

        return [
            'exact_full' => $byExactFull,
            'exact_last' => $byExactLast,
            'exact_first' => $byExactFirst,
            'last' => $byLast,
            'first' => $byFirst,
        ];
    }

    /**
     * SOUNDEX keys for a name: the whole cleaned string plus each token.
     *
     * @return list<string>
     */
    private function nameSoundKeys(?string $name): array
    {
        $normalized = DuplicateDetectionService::normalizeNameString($name);
        if ($normalized === '') {
            return [];
        }

        $lowerAscii = mb_strtolower(Str::ascii($normalized));
        $keys = [];

        $whole = preg_replace('/[^a-z]/', '', $lowerAscii);
        if ($whole !== '') {
            $keys[soundex($whole)] = true;
        }

        foreach (preg_split('/[\s\-]+/', $lowerAscii, -1, PREG_SPLIT_NO_EMPTY) as $token) {
            $token = preg_replace('/[^a-z]/', '', $token);
            if ($token !== '') {
                $keys[soundex($token)] = true;
            }
        }

        return array_keys($keys);
    }

    /**
     * Candidates that could plausibly match this payload by exact name or sound.
     *
     * @param  array<string, mixed>  $payload
     * @param  array{
     *     exact_full: array<string, list<Beneficiary>>,
     *     exact_last: array<string, list<Beneficiary>>,
     *     exact_first: array<string, list<Beneficiary>>,
     *     last: array<string, list<Beneficiary>>,
     *     first: array<string, list<Beneficiary>>
     * }  $index
     * @return Collection<int, Beneficiary>
     */
    private function candidatesForPayload(array $payload, array $index): Collection
    {
        $pool = [];

        $payloadLast = (string) ($payload['last_name'] ?? '');
        $payloadFirst = (string) ($payload['first_name'] ?? '');

        $rawLast = mb_strtolower(trim($payloadLast));
        $rawFirst = mb_strtolower(trim($payloadFirst));
        $normLast = DuplicateDetectionService::normalizeNameString($payloadLast);
        $normFirst = DuplicateDetectionService::normalizeNameString($payloadFirst);
        $cleanLast = preg_replace('/[^a-z0-9]/', '', mb_strtolower(Str::ascii($normLast)));
        $cleanFirst = preg_replace('/[^a-z0-9]/', '', mb_strtolower(Str::ascii($normFirst)));

        // 1. Exact surname + first_name matches (guarantees exact name matches are NEVER excluded)
        if ($rawLast !== '' && $rawFirst !== '') {
            foreach ($index['exact_full'][$rawLast.'|'.$rawFirst] ?? [] as $candidate) {
                $pool[$candidate->id] = $candidate;
            }
        }
        if ($normLast !== '' && $normFirst !== '') {
            foreach ($index['exact_full'][$normLast.'|'.$normFirst] ?? [] as $candidate) {
                $pool[$candidate->id] = $candidate;
            }
        }
        if ($cleanLast !== '' && $cleanFirst !== '') {
            foreach ($index['exact_full'][$cleanLast.'|'.$cleanFirst] ?? [] as $candidate) {
                $pool[$candidate->id] = $candidate;
            }
            // Transposed names (swapped first and last)
            foreach ($index['exact_full'][$cleanFirst.'|'.$cleanLast] ?? [] as $candidate) {
                $pool[$candidate->id] = $candidate;
            }
        }

        // 2. Exact surname matches
        if ($rawLast !== '') {
            foreach ($index['exact_last'][$rawLast] ?? [] as $candidate) {
                $pool[$candidate->id] = $candidate;
            }
        }
        if ($normLast !== '') {
            foreach ($index['exact_last'][$normLast] ?? [] as $candidate) {
                $pool[$candidate->id] = $candidate;
            }
        }
        if ($cleanLast !== '') {
            foreach ($index['exact_last'][$cleanLast] ?? [] as $candidate) {
                $pool[$candidate->id] = $candidate;
            }
        }

        // 3. Soundex keys for surname and first name
        foreach ($this->nameSoundKeys($normLast !== '' ? $normLast : $payloadLast) as $key) {
            foreach ($index['last'][$key] ?? [] as $candidate) {
                $pool[$candidate->id] = $candidate;
            }
        }

        foreach ($this->nameSoundKeys($normFirst !== '' ? $normFirst : $payloadFirst) as $key) {
            foreach ($index['first'][$key] ?? [] as $candidate) {
                $pool[$candidate->id] = $candidate;
            }
        }

        return new Collection(array_values($pool));
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

        // Strip non-breaking spaces and normalize
        $str = str_replace(["\xc2\xa0", "\u{00a0}"], ' ', $str);
        $str = preg_replace('/\s+/', ' ', $str);

        // Strip trailing and leading punctuation (e.g. '.', ',', ';', '-', etc.)
        $cleaned = preg_replace('/[^\p{L}\p{N}]+$/u', '', $str);
        $cleaned = preg_replace('/^[^\p{L}\p{N}]+/u', '', $cleaned);
        $cleaned = trim($cleaned);

        return $cleaned !== '' ? $cleaned : $str;
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
