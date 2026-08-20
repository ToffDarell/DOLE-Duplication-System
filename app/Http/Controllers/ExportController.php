<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export filtered beneficiaries to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $beneficiaries = $this->getFilteredQuery($request)->get();

        AuditLog::log([
            'action' => 'export',
            'description' => 'Exported '.$beneficiaries->count().' beneficiaries to CSV',
        ]);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="beneficiaries_export_'.date('Y-m-d_His').'.csv"',
        ];

        $callback = function () use ($beneficiaries) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID', 'Full Name', 'First Name', 'Middle Name', 'Last Name', 'Suffix',
                'Date of Birth', 'Age', 'Sex', 'Civil Status', 'Gov ID Type', 'Gov ID Number',
                'Contact Number', 'Barangay', 'Municipality', 'Address', 'Senior Citizen', 'PWD', 'Student', 'Gov Employee',
            ]);

            foreach ($beneficiaries as $b) {
                fputcsv($file, [
                    $b->id,
                    $b->full_name,
                    $b->first_name,
                    $b->middle_name,
                    $b->last_name,
                    $b->suffix,
                    $b->date_of_birth ? $b->date_of_birth->format('Y-m-d') : '',
                    $b->age,
                    $b->sex,
                    $b->civil_status,
                    $b->government_id_type,
                    $b->government_id_number,
                    $b->contact_number,
                    $b->barangay,
                    $b->municipality,
                    $b->address,
                    $b->is_senior_citizen ? 'Yes' : 'No',
                    $b->is_pwd ? 'Yes' : 'No',
                    $b->is_student ? 'Yes' : 'No',
                    $b->is_government_employee ? 'Yes' : 'No',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export filtered beneficiaries to PDF.
     */
    public function exportPdf(Request $request)
    {
        $beneficiaries = $this->getFilteredQuery($request)->take(200)->get(); // PDF limit for performance

        AuditLog::log([
            'action' => 'export',
            'description' => 'Exported '.$beneficiaries->count().' beneficiaries to PDF',
        ]);

        $pdf = Pdf::loadView('exports.beneficiaries_pdf', compact('beneficiaries'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('beneficiaries_report_'.date('Y-m-d').'.pdf');
    }

    protected function getFilteredQuery(Request $request)
    {
        $query = Beneficiary::with(['beneficiaryPrograms.program']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('government_id_number', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        if ($programCode = $request->input('program')) {
            $query->whereHas('beneficiaryPrograms.program', function ($q) use ($programCode) {
                $q->where('code', $programCode);
            });
        }

        if ($municipality = $request->input('municipality')) {
            $query->where('municipality', $municipality);
        }

        return $query;
    }
}
