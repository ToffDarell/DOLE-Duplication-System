<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
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

        $flag->update([
            'status' => $request->input('status'),
            'remarks' => $request->input('remarks'),
            'reviewed_by' => auth()->id(),
        ]);

        $auditAction = $flag->household_match_flag ? 'override_household_duplicate' : 'duplicate_override';

        AuditLog::log([
            'action' => $auditAction,
            'model_type' => DuplicateFlag::class,
            'model_id' => $flag->id,
            'description' => "Resolved {$auditAction} flag #{$flag->id} as {$request->input('status')}. Remarks: {$request->input('remarks')}",
        ]);

        return redirect()->route('duplicates.index')->with('success', "Duplicate flag #{$flag->id} updated successfully.");
    }
}
