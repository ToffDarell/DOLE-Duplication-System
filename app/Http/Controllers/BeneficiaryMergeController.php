<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\BeneficiaryProgram;
use App\Models\DuplicateFlag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BeneficiaryMergeController extends Controller
{
    /**
     * Merge secondary beneficiary into primary beneficiary.
     */
    public function merge(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'primary_id' => ['required', 'integer', 'exists:beneficiaries,id'],
            'secondary_id' => ['required', 'integer', 'exists:beneficiaries,id', 'different:primary_id'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $primaryId = (int) $request->input('primary_id');
        $secondaryId = (int) $request->input('secondary_id');
        $remarks = $request->input('remarks', 'Manual Administrator Beneficiary Merge');

        $primary = Beneficiary::findOrFail($primaryId);
        $secondary = Beneficiary::findOrFail($secondaryId);

        $primaryName = $primary->full_name;
        $secondaryName = $secondary->full_name;

        DB::beginTransaction();
        try {
            // 1. Transfer linked beneficiary program availments from secondary to primary
            $secondaryPrograms = BeneficiaryProgram::where('beneficiary_id', $secondaryId)->get();
            $transferredProgramsCount = 0;

            foreach ($secondaryPrograms as $sp) {
                // Check if primary already has this exact program & availment year
                $existingProgram = BeneficiaryProgram::where('beneficiary_id', $primaryId)
                    ->where('program_id', $sp->program_id)
                    ->where('availment_year', $sp->availment_year)
                    ->first();

                if ($existingProgram) {
                    if ($sp->calamity_remarks && ! $existingProgram->calamity_remarks) {
                        $existingProgram->update(['calamity_remarks' => $sp->calamity_remarks]);
                    }
                    $sp->delete();
                } else {
                    $sp->update(['beneficiary_id' => $primaryId]);
                    $transferredProgramsCount++;
                }
            }

            // 2. Re-assign DuplicateFlag records
            DuplicateFlag::where('beneficiary_id', $secondaryId)
                ->where('matched_beneficiary_id', '!=', $primaryId)
                ->update(['beneficiary_id' => $primaryId]);

            DuplicateFlag::where('matched_beneficiary_id', $secondaryId)
                ->where('beneficiary_id', '!=', $primaryId)
                ->update(['matched_beneficiary_id' => $primaryId]);

            // Delete self-referencing duplicate flags between primary and secondary
            DuplicateFlag::where(function ($q) use ($primaryId, $secondaryId) {
                $q->where('beneficiary_id', $secondaryId)->where('matched_beneficiary_id', $primaryId);
            })->orWhere(function ($q) use ($primaryId, $secondaryId) {
                $q->where('beneficiary_id', $primaryId)->where('matched_beneficiary_id', $secondaryId);
            })->delete();

            // 3. Fill any missing/null attributes on primary using secondary record
            $fillableAttributes = [
                'middle_name', 'suffix', 'civil_status', 'government_id_type',
                'government_id_number', 'contact_number', 'address',
            ];
            $updates = [];
            foreach ($fillableAttributes as $attr) {
                if (empty($primary->{$attr}) && ! empty($secondary->{$attr})) {
                    $updates[$attr] = $secondary->{$attr};
                }
            }
            if (! empty($updates)) {
                $primary->update($updates);
            }

            // 4. Delete the secondary record
            $secondary->delete();

            // 5. Audit Log
            AuditLog::log([
                'action' => 'BENEFICIARY_MERGED',
                'model_type' => Beneficiary::class,
                'model_id' => $primaryId,
                'description' => "Merged beneficiary ID {$secondaryId} ({$secondaryName}) into ID {$primaryId} ({$primaryName}). Transferred {$transferredProgramsCount} program availment(s). Remarks: {$remarks}",
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully merged {$secondaryName} into {$primaryName}.",
                    'primary_beneficiary' => $primary->fresh(['beneficiaryPrograms.program']),
                ]);
            }

            return redirect()->route('beneficiaries.index')
                ->with('success', "Successfully merged record for {$secondaryName} into Master Profile {$primaryName}.");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to merge beneficiaries: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to merge beneficiaries: '.$e->getMessage());
        }
    }

    /**
     * Search potential candidate beneficiaries to merge.
     */
    public function searchCandidates(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));
        $excludeId = $request->input('exclude_id');

        if (empty($q)) {
            return response()->json([]);
        }

        $query = Beneficiary::with('beneficiaryPrograms.program')
            ->where(function ($query) use ($q) {
                $query->where('full_name', 'LIKE', "%{$q}%")
                    ->orWhere('first_name', 'LIKE', "%{$q}%")
                    ->orWhere('last_name', 'LIKE', "%{$q}%")
                    ->orWhere('government_id_number', 'LIKE', "%{$q}%");
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $candidates = $query->limit(10)->get();

        return response()->json($candidates);
    }
}
