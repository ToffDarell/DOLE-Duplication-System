<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BeneficiaryProgram;
use App\Models\DuplicateFlag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DuplicateController extends Controller
{
    public function index(Request $request): View
    {
        $query = DuplicateFlag::with(['beneficiary', 'matchedBeneficiary', 'reviewer']);

        if ($status = $request->input('status')) {
            if (in_array($status, ['overridden', 'resolved'])) {
                $query->whereIn('status', ['overridden', 'resolved_not_duplicate', 'resolved_duplicate']);
            } else {
                $query->where('status', $status);
            }
        } else {
            // Default to showing pending flags first
            $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END");
        }

        if ($request->boolean('household')) {
            $query->where('household_match_flag', true);
        }

        if ($matchType = $request->input('match_type')) {
            $query->where('match_type', $matchType);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('beneficiary', function ($bq) use ($search) {
                    $bq->where('full_name', 'like', "%{$search}%");
                })->orWhereHas('matchedBeneficiary', function ($mq) use ($search) {
                    $mq->where('full_name', 'like', "%{$search}%");
                });
            });
        }

        $flags = $query->latest()->paginate(15)->withQueryString();

        return view('duplicates.index', compact('flags'));
    }

    public function resolve(Request $request, DuplicateFlag $flag): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:resolved_duplicate,resolved_not_duplicate,overridden'],
            'remarks' => ['required', 'string', 'max:500'],
        ]);

        $decision = $request->input('status');
        $remarks = $request->input('remarks');

        $candidate = $flag->beneficiary;
        $matched = $flag->matchedBeneficiary;
        $candidateName = $candidate?->full_name ?? ($flag->matched_fields['rejected_applicant_name'] ?? 'Candidate');
        $matchedName = $matched?->full_name ?? 'Matched Beneficiary';

        if ($decision === 'resolved_duplicate') {
            // Store candidate snapshot in matched_fields so the flag retains full info in audit history
            $mf = $flag->matched_fields ?? [];
            $mf['rejected_applicant_name'] = $candidateName;
            $mf['rejected_applicant_dob'] = $candidate?->date_of_birth?->format('M d, Y');
            $mf['rejected_applicant_address'] = $candidate?->address;
            $mf['rejected_applicant_gov_id'] = $candidate?->government_id_number;
            $mf['rejection_reason'] = $remarks;

            $flag->update([
                'status' => 'resolved_duplicate',
                'remarks' => $remarks,
                'reviewed_by' => auth()->id(),
                'matched_fields' => $mf,
            ]);

            // Completely delete the duplicate candidate so they are NOT recorded in beneficiaries
            if ($candidate) {
                $candidate->delete();
            }

            AuditLog::log([
                'action' => 'reject_duplicate_beneficiary',
                'model_type' => DuplicateFlag::class,
                'model_id' => $flag->id,
                'description' => "Rejected duplicate registration for {$candidateName} (matched with existing beneficiary {$matchedName}). Duplicate applicant removed from database.",
            ]);

            return redirect()->route('duplicates.index')->with('success', "Duplicate applicant {$candidateName} rejected and removed from registered beneficiaries.");
        }

        // For approved decisions (resolved_not_duplicate or overridden)
        $flag->update([
            'status' => $decision,
            'remarks' => $remarks,
            'reviewed_by' => auth()->id(),
        ]);

        if ($candidate) {
            BeneficiaryProgram::where('beneficiary_id', $candidate->id)
                ->where('status', 'pending')
                ->update(['status' => 'approved']);
        }

        AuditLog::log([
            'action' => $flag->household_match_flag ? 'override_household_duplicate' : 'duplicate_override',
            'model_type' => DuplicateFlag::class,
            'model_id' => $flag->id,
            'description' => "Approved {$candidateName} despite duplicate flag (matched with {$matchedName}) as {$decision}. Remarks: {$remarks}",
        ]);

        return redirect()->route('duplicates.index')->with('success', "Beneficiary {$candidateName} verified and approved.");
    }
}
