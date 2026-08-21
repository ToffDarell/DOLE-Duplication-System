<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DilpGroup;
use App\Models\DilpProject;
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

    public function show(DilpGroup $group): View
    {
        $group->loadCount(['projects', 'members']);
        $members = $group->members()->latest()->paginate(20);

        return view('dilp.groups.show', compact('group', 'members'));
    }

    public function importCoPartnerMembers(Request $request, DilpGroup $group): RedirectResponse
    {
        $request->validate([
            'file' => ['nullable', 'file', 'mimes:csv,txt', 'max:5120'],
            'csv_file' => ['nullable', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('file') ?? $request->file('csv_file');
        if (! $file) {
            return back()->withErrors(['file' => 'Please select a valid CSV file to upload.']);
        }

        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->withErrors(['file' => 'Failed to read uploaded CSV file.']);
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return back()->withErrors(['file' => 'Uploaded CSV file is empty.']);
        }

        // Clean BOM if present and normalize headers
        $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
        $headerMap = [];
        foreach ($header as $index => $col) {
            $cleanCol = strtolower(trim(str_replace([' ', '_', '-'], '', $col)));
            if (in_array($cleanCol, ['membername', 'name', 'fullname', 'member'])) {
                $headerMap['member_name'] = $index;
            } elseif (in_array($cleanCol, ['contactno', 'contactnumber', 'contact', 'phone', 'phonenumber', 'mobile'])) {
                $headerMap['contact_no'] = $index;
            } elseif (in_array($cleanCol, ['designation', 'position', 'role', 'title'])) {
                $headerMap['designation'] = $index;
            }
        }

        // Default indices if headers not recognized by name
        $nameIdx = $headerMap['member_name'] ?? 0;
        $contactIdx = $headerMap['contact_no'] ?? 1;
        $desigIdx = $headerMap['designation'] ?? 2;

        $importedCount = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) {
                continue;
            }

            $memberName = trim($row[$nameIdx] ?? '');
            $contactNo = isset($row[$contactIdx]) ? trim($row[$contactIdx]) : null;
            $designation = isset($row[$desigIdx]) ? trim($row[$desigIdx]) : null;

            if (! empty($memberName)) {
                $group->members()->create([
                    'member_name' => $memberName,
                    'contact_no' => $contactNo,
                    'designation' => $designation,
                ]);
                $importedCount++;
            }
        }

        fclose($handle);

        AuditLog::log([
            'action' => 'import',
            'model_type' => DilpGroup::class,
            'model_id' => $group->id,
            'description' => "Imported {$importedCount} member contact roster for DILP group {$group->group_name}",
        ]);

        return redirect()->route('dilp.groups.show', $group)
            ->with('success', "Successfully imported {$importedCount} member(s) to {$group->group_name}.");
    }

    public function destroy(DilpGroup $group): RedirectResponse
    {
        $id = $group->id;
        $name = $group->group_name;

        // Check if group currently has linked projects
        if ($group->projects()->exists() || DilpProject::where('dilp_group_id', $id)->exists()) {
            return redirect()->route('dilp.groups.index')
                ->withErrors(['error' => 'Cannot delete group with existing associated projects.']);
        }

        $group->delete();

        AuditLog::log([
            'action' => 'DILP_GROUP_DELETED',
            'model_type' => DilpGroup::class,
            'model_id' => $id,
            'description' => "Deleted DILP Group ID: {$id} ({$name})",
        ]);

        return redirect()->route('dilp.groups.index')->with('success', "DILP Group {$name} deleted.");
    }
}
