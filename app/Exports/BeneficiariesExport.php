<?php

namespace App\Exports;

use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BeneficiariesExport extends DefaultValueBinder implements FromQuery, ShouldAutoSize, WithCustomStartCell, WithCustomValueBinder, WithDrawings, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * Number of columns in the exported table (A–L).
     */
    private const TOTAL_COLUMNS = 12;

    /**
     * Height (in points) of Row 1, which is reserved for the centred logo.
     */
    private const LOGO_ROW_HEIGHT = 72;

    /**
     * Logo height in pixels (width is derived proportionally).
     */
    private const LOGO_HEIGHT = 55;

    /**
     * Pixels to shift the logo right of its anchor column so its midpoint lines
     * up with the centred banner text. Tune this if column content changes the
     * auto-sized layout noticeably.
     */
    private const LOGO_OFFSET_X = 90;

    protected int $rowNumber = 0;

    public function __construct(
        protected Builder $query
    ) {}

    public function query(): Builder
    {
        // Strictly sort alphabetically by Last Name, then First Name (Panelist: Glaiza Mae Libe)
        return $this->query
            ->with(['beneficiaryPrograms.program', 'duplicateFlags'])
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc');
    }

    /**
     * Start the data table at Row 7: Row 1 holds the centred logo, Rows 2–5 the
     * header banner text, Row 6 is a spacer.
     */
    public function startCell(): string
    {
        return 'A7';
    }

    /**
     * Embed the official DOLE seal, horizontally centred above the header banner.
     *
     * The table spans columns A–L (TOTAL_COLUMNS = 12), so the horizontal
     * midpoint of the sheet is the F|G column boundary. The drawing is anchored
     * at the first column of the right half (G for 12 columns) and nudged right
     * with a pixel offset so its midpoint aligns with the centred title text
     * underneath it. Column widths are auto-sized, so LOGO_OFFSET_X is a
     * deliberate approximation rather than an exact pixel calculation.
     */
    public function drawings(): BaseDrawing|array
    {
        $logoPath = public_path('logo dole.png');
        if (! file_exists($logoPath)) {
            $logoPath = public_path('dole-logo.png');
        }

        if (! file_exists($logoPath)) {
            return [];
        }

        // Dynamic anchor column: 12 columns -> index 7 -> "G".
        $anchorColumnIndex = intdiv(self::TOTAL_COLUMNS, 2) + 1;
        $anchorColumn = Coordinate::stringFromColumnIndex(max(1, $anchorColumnIndex));

        $drawing = new Drawing;
        $drawing->setName('DOLE Official Seal');
        $drawing->setDescription('Department of Labor and Employment Official Seal');
        $drawing->setPath($logoPath);
        $drawing->setResizeProportional(true);
        $drawing->setHeight(self::LOGO_HEIGHT);
        $drawing->setCoordinates($anchorColumn.'1');
        $drawing->setOffsetX(self::LOGO_OFFSET_X);
        $drawing->setOffsetY(6); // top padding inside the taller Row 1

        return $drawing;
    }

    /**
     * Bind string values to prevent Excel scientific notation for ID & phone numbers.
     */
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_numeric($value) && strlen((string) $value) > 10) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /**
     * Excel Column Headings matching PDF report exactly (Panelists: Glaiza Mae Libe & Mark Ian Mukara).
     */
    public function headings(): array
    {
        return [
            '#',
            'Full Name',
            'Date of Birth',
            'Age',
            'Sex',
            'Municipality',
            'Barangay',
            'House Number / Address',
            'Gov ID Number',
            'Contact Number',
            'Program(s)',
            'Duplication Risk / Status',
        ];
    }

    /**
     * Map each beneficiary record to row columns.
     */
    public function map($beneficiary): array
    {
        $this->rowNumber++;

        // Format Programs
        $programs = $beneficiary->beneficiaryPrograms->map(function ($bp) {
            return ($bp->program?->code ?? 'N/A').' ('.$bp->availment_year.')';
        })->implode(', ');

        if (empty($programs)) {
            $programs = 'None';
        }

        // Compute Duplication Risk Status
        $maxScore = $beneficiary->duplicateFlags->max('match_score') ?? 0;
        if ($maxScore >= 100) {
            $riskStatus = 'Exact Duplicate (100%)';
        } elseif ($maxScore >= 85) {
            $riskStatus = "High Risk ({$maxScore}%)";
        } elseif ($maxScore >= 70) {
            $riskStatus = "Moderate Risk ({$maxScore}%)";
        } elseif ($maxScore > 0) {
            $riskStatus = "Low Risk ({$maxScore}%)";
        } else {
            $riskStatus = 'Clean Record (0%)';
        }

        return [
            $this->rowNumber,
            $beneficiary->full_name,
            $beneficiary->date_of_birth ? $beneficiary->date_of_birth->format('M d, Y') : '—',
            $beneficiary->age ?? '—',
            $beneficiary->sex,
            $beneficiary->municipality ?? '—',
            $beneficiary->barangay ?? '—',
            $beneficiary->address ?: '—',
            $beneficiary->government_id_number ?: '—',
            $beneficiary->contact_number ?: '—',
            $programs,
            $riskStatus,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();
        $lastColumn = Coordinate::stringFromColumnIndex(self::TOTAL_COLUMNS); // "L"

        // Row 1: reserved for the centred logo — merged, empty, and tall enough
        // that the seal never overlaps the banner text below it.
        $sheet->mergeCells('A1:'.$lastColumn.'1');
        $sheet->getRowDimension(1)->setRowHeight(self::LOGO_ROW_HEIGHT);

        // 1. Official Header Banner text (Rows 2-5), merged & centred across A-L
        $sheet->mergeCells('A2:'.$lastColumn.'2');
        $sheet->mergeCells('A3:'.$lastColumn.'3');
        $sheet->mergeCells('A4:'.$lastColumn.'4');
        $sheet->mergeCells('A5:'.$lastColumn.'5');

        $sheet->setCellValue('A2', 'Republic of the Philippines');
        $sheet->setCellValue('A3', 'DEPARTMENT OF LABOR AND EMPLOYMENT');
        $sheet->setCellValue('A4', 'Regional Office No. X — Bukidnon Field Office');
        $sheet->setCellValue('A5', 'Beneficiary Masterlist & Duplication Detection Official Report (Alphabetical Order)');

        $sheet->getStyle('A2:'.$lastColumn.'5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:'.$lastColumn.'5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '475569']],
        ]);
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E3A8A']],
        ]);
        $sheet->getStyle('A4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10.5, 'color' => ['rgb' => '1E293B']],
        ]);
        $sheet->getStyle('A5')->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '64748B']],
        ]);

        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(24);
        $sheet->getRowDimension(4)->setRowHeight(18);
        $sheet->getRowDimension(5)->setRowHeight(18);
        $sheet->getRowDimension(6)->setRowHeight(10); // Spacing row

        // 2. Style Table Column Header (Row 7)
        $sheet->getStyle('A7:'.$highestCol.'7')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A8A'], // DOLE Navy Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(7)->setRowHeight(26);

        // 3. Center specific columns (#, DOB, Age, Sex, Risk) and add cell borders
        if ($highestRow >= 8) {
            $sheet->getStyle('A8:A'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C8:E'.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($lastColumn.'8:'.$lastColumn.$highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Add thin borders to all table cells
            $sheet->getStyle('A7:'.$highestCol.$highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ]);
        }

        return [];
    }

    public function title(): string
    {
        return 'DOLE Beneficiaries Masterlist';
    }
}
