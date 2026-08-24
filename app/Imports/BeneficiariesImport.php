<?php

namespace App\Imports;

use App\Models\ImportLog;
use App\Services\BeneficiaryImportService;
use Maatwebsite\Excel\Concerns\WithStartRow;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BeneficiariesImport implements WithStartRow
{
    protected ?int $customStartRow = null;

    protected ?int $customEndRow = null;

    protected string $programCode = 'TUPAD';

    protected ?BeneficiaryImportService $importService = null;

    public function __construct(string $programCode = 'TUPAD', ?int $startRow = null, ?int $endRow = null)
    {
        $this->programCode = strtoupper($programCode);
        $this->customStartRow = ($startRow !== null && $startRow > 0) ? $startRow : null;
        $this->customEndRow = ($endRow !== null && $endRow > 0) ? $endRow : null;
        $this->importService = app(BeneficiaryImportService::class);
    }

    /**
     * Start row for Maatwebsite / chunk processing (defaults to 1 if auto-detecting).
     */
    public function startRow(): int
    {
        return $this->customStartRow ?? 1;
    }

    /**
     * Execute the full import with auto-detected start/end rows and dynamic column mapping.
     */
    public function import(string $filePath): ImportLog
    {
        return $this->importService->import(
            $filePath,
            $this->programCode,
            $this->customStartRow,
            $this->customEndRow
        );
    }

    /**
     * Auto-detect header row and starting data row from worksheet.
     * Searches for: "Last Name", "First Name", "Name of Beneficiary", "Surname", "No."
     *
     * @return array{start_row: int, header_row: ?int, column_map: array}
     */
    public function detectStartRowAndHeaders(Worksheet $worksheet): array
    {
        return $this->importService->detectStartRowAndMapping($worksheet, $this->programCode);
    }

    /**
     * Check if row contains footer, total, or sign-off keywords.
     * Stops reading when encountering "TOTAL", "APPROVED BY", "PREPARED BY", "CERTIFIED CORRECT", etc.
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
            'certified correct', 'certified true', 'certified by', 'noted by', 'note:', 'legend:',
            'focal person', 'provincial director', 'regional director', 'field office',
            'signature', 'verified by', 'recommending approval', 'summary',
        ];

        foreach ($keywords as $kw) {
            if (str_contains($combined, $kw)) {
                return true;
            }
        }

        return false;
    }

    public function setProgramCode(string $code): self
    {
        $this->programCode = strtoupper($code);

        return $this;
    }

    public function setStartRow(?int $row): self
    {
        $this->customStartRow = $row;

        return $this;
    }

    public function setEndRow(?int $row): self
    {
        $this->customEndRow = $row;

        return $this;
    }
}
