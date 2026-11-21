<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\DilpGroup;
use App\Models\DilpProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DilpProjectController extends Controller
{
    public function index(Request $request): View
    {
        $activeType = $request->input('type', 'all');
        if (! in_array($activeType, ['all', 'group', 'individual'], true)) {
            $activeType = 'all';
        }

        $query = DilpProject::with(['group', 'beneficiary']);

        if ($activeType === 'group') {
            $query->where('project_type', DilpProject::TYPE_GROUP);
        } elseif ($activeType === 'individual') {
            $query->where('project_type', DilpProject::TYPE_INDIVIDUAL);
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('beneficiary_name', 'like', "%{$search}%")
                    ->orWhere('acp_name', 'like', "%{$search}%")
                    ->orWhereHas('group', fn ($gq) => $gq->where('group_name', 'like', "%{$search}%"))
                    ->orWhereHas('beneficiary', fn ($bq) => $bq->where('full_name', 'like', "%{$search}%"));
            });
        }

        if ($mode = $request->input('implementation_mode')) {
            $query->where('implementation_mode', $mode);
        }

        if ($status = $request->input('liquidation_status')) {
            $query->where('liquidation_status', $status);
        }

        $projects = $query->latest()->paginate(15)->withQueryString();

        $counts = [
            'all' => DilpProject::count(),
            'group' => DilpProject::where('project_type', DilpProject::TYPE_GROUP)->count(),
            'individual' => DilpProject::where('project_type', DilpProject::TYPE_INDIVIDUAL)->count(),
        ];

        $totalGrantAmount = (float) (clone $query)->sum('grant_amount');

        return view('dilp.projects.index', compact('projects', 'activeType', 'counts', 'totalGrantAmount'));
    }

    public function create(Request $request): View
    {
        $groups = DilpGroup::orderBy('group_name')->get();
        $beneficiaries = Beneficiary::select('id', 'full_name', 'municipality', 'barangay')
            ->orderBy('full_name')
            ->limit(500)
            ->get();
        $defaultType = in_array($request->input('type'), ['group', 'individual'], true)
            ? $request->input('type')
            : DilpProject::TYPE_GROUP;

        return view('dilp.projects.create', compact('groups', 'beneficiaries', 'defaultType'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_type' => ['nullable', 'in:group,individual'],
            'dilp_group_id' => ['nullable', 'exists:dilp_groups,id'],
            'beneficiary_id' => ['nullable', 'exists:beneficiaries,id'],
            'beneficiary_name' => ['nullable', 'string', 'max:200'],
            'project_name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'grant_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'implementation_mode' => ['nullable', 'in:DA,ACP'],
            'acp_name' => ['nullable', 'string', 'max:200'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'liquidation_status' => ['required', 'in:pending,partial,liquidated,overdue,unliquidated'],
        ]);

        // Infer project_type cleanly if omitted (preserves backwards compatibility)
        $projectType = $data['project_type']
            ?? (! empty($data['dilp_group_id']) ? DilpProject::TYPE_GROUP : (! empty($data['beneficiary_id']) || ! empty($data['beneficiary_name']) ? DilpProject::TYPE_INDIVIDUAL : DilpProject::TYPE_GROUP));
        $data['project_type'] = $projectType;

        if ($projectType === DilpProject::TYPE_INDIVIDUAL) {
            $data['dilp_group_id'] = null;
            if (! empty($data['beneficiary_id']) && empty($data['beneficiary_name'])) {
                $data['beneficiary_name'] = Beneficiary::find($data['beneficiary_id'])?->full_name;
            }
        } else {
            $data['beneficiary_id'] = null;
            $data['beneficiary_name'] = null;
        }

        if (($data['implementation_mode'] ?? null) !== DilpProject::MODE_ACP) {
            $data['acp_name'] = null;
        }

        // DILP Gatekeeper Rule: Associated Group must be Fully Liquidated before receiving a new grant.
        if (! empty($data['dilp_group_id'])) {
            $unliquidatedProject = DilpProject::where('dilp_group_id', $data['dilp_group_id'])
                ->where(function ($q) {
                    $q->whereIn('liquidation_status', ['pending', 'partial', 'unliquidated', 'overdue', 'Pending', 'Partial Liquidation', 'PENDING', 'PARTIAL'])
                        ->orWhere(function ($sub) {
                            $sub->whereNotIn('liquidation_status', ['liquidated', 'Liquidated', 'LIQUIDATED']);
                        });
                })
                ->exists();

            if ($unliquidatedProject) {
                throw ValidationException::withMessages([
                    'dilp_group_id' => 'Cannot create new DILP grant. The selected group has an unliquidated past project (Pending or Partial).',
                ]);
            }
        }

        $project = DilpProject::create($data);

        AuditLog::log([
            'action' => 'create',
            'model_type' => DilpProject::class,
            'model_id' => $project->id,
            'description' => "Created DILP {$project->project_type} project {$project->project_name}",
        ]);

        return redirect()->route('dilp.projects.index')->with('success', "DILP Project {$project->project_name} created.");
    }

    public function edit(DilpProject $project): View
    {
        $groups = DilpGroup::orderBy('group_name')->get();
        $beneficiaries = Beneficiary::select('id', 'full_name', 'municipality', 'barangay')
            ->orderBy('full_name')
            ->limit(500)
            ->get();

        return view('dilp.projects.edit', compact('project', 'groups', 'beneficiaries'));
    }

    public function update(Request $request, DilpProject $project): RedirectResponse
    {
        $data = $request->validate([
            'project_type' => ['nullable', 'in:group,individual'],
            'dilp_group_id' => ['nullable', 'exists:dilp_groups,id'],
            'beneficiary_id' => ['nullable', 'exists:beneficiaries,id'],
            'beneficiary_name' => ['nullable', 'string', 'max:200'],
            'project_name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'grant_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'implementation_mode' => ['nullable', 'in:DA,ACP'],
            'acp_name' => ['nullable', 'string', 'max:200'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'liquidation_status' => ['required', 'in:pending,partial,liquidated,overdue,unliquidated'],
        ]);

        $projectType = $data['project_type'] ?? $project->project_type ?? DilpProject::TYPE_GROUP;
        $data['project_type'] = $projectType;

        if ($projectType === DilpProject::TYPE_INDIVIDUAL) {
            $data['dilp_group_id'] = null;
            if (! empty($data['beneficiary_id']) && empty($data['beneficiary_name'])) {
                $data['beneficiary_name'] = Beneficiary::find($data['beneficiary_id'])?->full_name;
            }
        } else {
            $data['beneficiary_id'] = null;
            $data['beneficiary_name'] = null;
        }

        if (($data['implementation_mode'] ?? null) !== DilpProject::MODE_ACP) {
            $data['acp_name'] = null;
        }

        if (! empty($data['dilp_group_id']) && (int) $data['dilp_group_id'] !== (int) $project->dilp_group_id) {
            $unliquidatedProject = DilpProject::where('dilp_group_id', $data['dilp_group_id'])
                ->where('id', '!=', $project->id)
                ->whereNotIn('liquidation_status', ['liquidated', 'Liquidated', 'LIQUIDATED'])
                ->exists();

            if ($unliquidatedProject) {
                throw ValidationException::withMessages([
                    'dilp_group_id' => 'Cannot assign to DILP group. The selected group has an unliquidated past project (Pending or Partial).',
                ]);
            }
        }

        $project->update($data);

        AuditLog::log([
            'action' => 'update',
            'model_type' => DilpProject::class,
            'model_id' => $project->id,
            'description' => "Updated DILP project {$project->project_name} (Liquidation: {$project->liquidation_status})",
        ]);

        return redirect()->route('dilp.projects.index')->with('success', 'DILP Project updated.');
    }

    public function destroy(DilpProject $project): RedirectResponse
    {
        $id = $project->id;
        $name = $project->project_name;
        $project->delete();

        AuditLog::log([
            'action' => 'DILP_PROJECT_DELETED',
            'model_type' => DilpProject::class,
            'model_id' => $id,
            'description' => "Deleted DILP Project ID: {$id} ({$name})",
        ]);

        return redirect()->route('dilp.projects.index')->with('success', "DILP Project {$name} deleted.");
    }
}
