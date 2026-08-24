<?php

namespace App\Http\Controllers;

use App\Imports\BeneficiariesImport;
use App\Models\ImportLog;
use App\Services\BeneficiaryImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function __construct(
        protected BeneficiaryImportService $importService
    ) {}

    public function index(): View
    {
        $logs = ImportLog::with('user')->latest()->paginate(10);

        return view('import.index', compact('logs'));
    }

    /**
     * Preview rows and auto-detect start/end bounds for uploaded masterlist.
     */
    public function previewRows(Request $request): JsonResponse
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'program_code' => ['nullable', 'string', 'in:TUPAD,SPES,DILP,GIP'],
        ]);

        $file = $request->file('file');
        $programCode = $request->input('program_code', 'TUPAD');
        $realPath = $file->getRealPath();
        $ext = strtolower($file->getClientOriginalExtension());

        try {
            $readerType = match ($ext) {
                'xlsx' => IOFactory::READER_XLSX,
                'xls' => IOFactory::READER_XLS,
                'csv' => IOFactory::READER_CSV,
                default => null,
            };

            if ($readerType) {
                $reader = IOFactory::createReader($readerType);
            } else {
                $reader = IOFactory::createReaderForFile($realPath);
            }

            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($realPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = (int) $worksheet->getHighestRow();

            $detection = $this->importService->detectStartRowAndMapping($worksheet, $programCode);
            $startRow = (int) $detection['start_row'];
            $headerRow = $detection['header_row'];
            $columnMap = $detection['column_map'];

            $lastNameCol = $columnMap['last_name'] ?? 'B';
            $firstNameCol = $columnMap['first_name'] ?? 'C';
            $noCol = $columnMap['no'] ?? 'A';

            $footerKeywords = [
                'total', 'total number', 'grand total', 'sub-total', 'sub total', 'total beneficiaries',
                'approved by', 'approved:', 'prepared by', 'prepared:', 'prepared and certified',
                'certified correct', 'certified true', 'certified by', 'noted by', 'note:', 'legend:',
                'focal person', 'provincial director', 'regional director', 'field office',
                'signature', 'verified by', 'recommending approval', 'summary', 'birthdate:', 'civil status:',
            ];

            // Scan rows starting from startRow
            $endRow = $startRow;
            $consecutiveEmpty = 0;
            $totalFound = 0;

            for ($r = $startRow; $r <= $highestRow; $r++) {
                $noVal = trim((string) $worksheet->getCell($noCol.$r)->getValue());
                $lastName = trim((string) $worksheet->getCell($lastNameCol.$r)->getValue());
                $firstName = trim((string) $worksheet->getCell($firstNameCol.$r)->getValue());
                $colAVal = trim((string) $worksheet->getCell('A'.$r)->getValue());

                $checkText = strtolower($noVal.' '.$lastName.' '.$firstName.' '.$colAVal);

                // Check footer keywords
                $isFooter = false;
                foreach ($footerKeywords as $kw) {
                    if (str_contains($checkText, $kw)) {
                        $isFooter = true;
                        break;
                    }
                }

                if ($isFooter) {
                    $endRow = max($startRow, $r - 1);
                    break;
                }

                if (empty($lastName) && empty($firstName)) {
                    $consecutiveEmpty++;
                    if ($consecutiveEmpty >= 4) {
                        $endRow = max($startRow, $r - $consecutiveEmpty);
                        break;
                    }
                } else {
                    $consecutiveEmpty = 0;
                    $totalFound++;
                    $endRow = $r;
                }
            }

            return response()->json([
                'success' => true,
                'detected_start_row' => $startRow,
                'detected_end_row' => max($startRow, $endRow),
                'total_rows_found' => max(0, $totalFound),
                'header_row' => $headerRow,
                'program_code' => $programCode,
                'filename' => $file->getClientOriginalName(),
            ]);
        } catch (\Throwable $e) {
            $fallbackStart = ($programCode === 'TUPAD') ? 16 : 2;

            return response()->json([
                'success' => false,
                'message' => 'Auto-detection note: '.$e->getMessage(),
                'detected_start_row' => $fallbackStart,
                'detected_end_row' => null,
                'total_rows_found' => 0,
                'filename' => $file->getClientOriginalName(),
            ]);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'program_code' => ['required', 'in:TUPAD,SPES,DILP,GIP'],
            'start_row' => ['nullable', 'integer', 'min:1'],
            'end_row' => ['nullable', 'integer', 'min:1'],
        ], [
            'file.uploaded' => 'The file failed to upload. Please ensure the file is saved and not locked by another application.',
        ]);

        $file = $request->file('file');
        $filePath = $file->storeAs('imports', time().'_'.$file->getClientOriginalName());
        $fullPath = storage_path('app/private/'.$filePath);

        // Fallback for default storage disk path if local/public
        if (! file_exists($fullPath)) {
            $fullPath = storage_path('app/'.$filePath);
        }

        try {
            $importer = new BeneficiariesImport(
                $request->input('program_code'),
                $request->filled('start_row') ? (int) $request->input('start_row') : null,
                $request->filled('end_row') ? (int) $request->input('end_row') : null
            );

            $importLog = $importer->import($fullPath);

            if ($importLog->failed_rows > 0 && $importLog->imported_rows === 0) {
                return redirect()->route('import.show', $importLog)
                    ->with('error', "Import failed. 0 beneficiaries imported, {$importLog->failed_rows} rows had validation errors.");
            }

            if ($importLog->failed_rows > 0) {
                return redirect()->route('import.show', $importLog)
                    ->with('warning', "Import completed with {$importLog->imported_rows} successful and {$importLog->failed_rows} failed rows.");
            }

            return redirect()->route('import.show', $importLog)
                ->with('success', "Successfully imported {$importLog->imported_rows} beneficiaries.");
        } catch (\Throwable $e) {
            return redirect()->route('import.index')
                ->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    public function show(ImportLog $importLog): View
    {
        $importLog->load('user');

        return view('import.show', compact('importLog'));
    }

    public function destroy(ImportLog $importLog): RedirectResponse
    {
        $importLog->delete();

        return redirect()->route('import.index')
            ->with('success', 'Import history entry deleted successfully.');
    }
}
