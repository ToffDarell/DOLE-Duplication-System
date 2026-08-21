<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
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
        $query = DilpProject::with('group');

        if ($search = $request->input('search')) {
            $query->where('project_name', 'like', "%{$search}%");
        }

        if ($status = $request->input('liquidation_status')) {
            $query->where('liquidation_status', $status);
        }

        $projects = $query->latest()->paginate(15)->withQueryString();

        return view('dilp.projects.index', compact('projects'));
    }

    public function create(): View
    {
        $groups = DilpGroup::all();

        return view('dilp.projects.create', compact('groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dilp_group_id' => ['nullable', 'exists:dilp_groups,id'],
            'project_name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'liquidation_status' => ['required', 'in:pending,partial,liquidated,overdue,unliquidated'],
        ]);

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
            'description' => "Created DILP project {$project->project_name}",
        ]);

        return redirect()->route('dilp.projects.index')->with('success', "DILP Project {$project->project_name} created.");
    }

    public function edit(DilpProject $project): View
    {
        $groups = DilpGroup::all();

        return view('dilp.projects.edit', compact('project', 'groups'));
    }

    public function update(Request $request, DilpProject $project): RedirectResponse
    {
        $data = $request->validate([
            'dilp_group_id' => ['nullable', 'exists:dilp_groups,id'],
            'project_name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'liquidation_status' => ['required', 'in:pending,partial,liquidated,overdue,unliquidated'],
        ]);

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
