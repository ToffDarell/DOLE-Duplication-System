<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DilpGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DilpGroupController extends Controller
{
    public function index(Request $request): View
    {
        $query = DilpGroup::withCount('projects');

        if ($search = $request->input('search')) {
            $query->where('group_name', 'like', "%{$search}%")
                ->orWhere('co_partner_name', 'like', "%{$search}%");
        }

        $groups = $query->latest()->paginate(15)->withQueryString();

        return view('dilp.groups.index', compact('groups'));
    }

    public function create(): View
    {
        return view('dilp.groups.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'group_name' => ['required', 'string', 'max:150'],
            'co_partner_name' => ['nullable', 'string', 'max:150'],
            'co_partner_contact' => ['nullable', 'string', 'max:100'],
        ]);

        $group = DilpGroup::create($data);

        AuditLog::log([
            'action' => 'create',
            'model_type' => DilpGroup::class,
            'model_id' => $group->id,
            'description' => "Created DILP group {$group->group_name}",
        ]);

        return redirect()->route('dilp.groups.index')->with('success', "DILP Group {$group->group_name} created.");
    }

    public function edit(DilpGroup $group): View
    {
        return view('dilp.groups.edit', compact('group'));
    }

    public function update(Request $request, DilpGroup $group): RedirectResponse
    {
        $data = $request->validate([
            'group_name' => ['required', 'string', 'max:150'],
            'co_partner_name' => ['nullable', 'string', 'max:150'],
            'co_partner_contact' => ['nullable', 'string', 'max:100'],
        ]);

        $group->update($data);

        AuditLog::log([
            'action' => 'update',
            'model_type' => DilpGroup::class,
            'model_id' => $group->id,
            'description' => "Updated DILP group {$group->group_name}",
        ]);

        return redirect()->route('dilp.groups.index')->with('success', 'DILP Group updated.');
    }

    public function destroy(DilpGroup $group): RedirectResponse
    {
        $name = $group->group_name;
        $group->delete();

        AuditLog::log([
            'action' => 'delete',
            'model_type' => DilpGroup::class,
            'model_id' => $group->id,
            'description' => "Deleted DILP group {$name}",
        ]);

        return redirect()->route('dilp.groups.index')->with('success', "DILP Group {$name} deleted.");
    }
}
