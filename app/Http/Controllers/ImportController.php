<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Services\BeneficiaryImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'program_code' => ['required', 'in:TUPAD,SPES,DILP,GIP'],
            'start_row' => ['required', 'integer', 'min:1'],
        ]);

        $file = $request->file('file');
        $filePath = $file->storeAs('imports', time().'_'.$file->getClientOriginalName());
        $fullPath = storage_path('app/private/'.$filePath);

        // Fallback for default storage disk path if local/public
        if (! file_exists($fullPath)) {
            $fullPath = storage_path('app/'.$filePath);
        }

        $importLog = $this->importService->import(
            $fullPath,
            $request->input('program_code'),
            (int) $request->input('start_row')
        );

        if ($importLog->failed_rows > 0) {
            return redirect()->route('import.show', $importLog)
                ->with('warning', "Import completed with {$importLog->imported_rows} successful and {$importLog->failed_rows} failed rows.");
        }

        return redirect()->route('import.show', $importLog)
            ->with('success', "Successfully imported {$importLog->imported_rows} beneficiaries.");
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
