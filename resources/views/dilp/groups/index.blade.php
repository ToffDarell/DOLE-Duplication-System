@extends('layouts.app')
@section('title', 'DILP Groups')
@section('page-title', 'DILP Groups & Cooperatives')
@section('page-subtitle', 'Manage DILP group associations and co-partner details')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('dilp.groups.create') }}" class="rounded-xl bg-dole-blue px-4 py-2.5 text-xs font-extrabold text-white hover:bg-dole-blue-dark shadow-sm transition">
        + Create DILP Group
    </a>
</div>

<div class="rounded-2xl border border-gray-200/60 bg-white shadow-sm overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-100 bg-gray-50/70 text-xs uppercase text-gray-500 font-bold">
            <tr>
                <th class="px-6 py-3.5 font-bold">Group Name</th>
                <th class="px-6 py-3.5 font-bold">Co-Partner Organization</th>
                <th class="px-6 py-3.5 font-bold">Co-Partner Contact</th>
                <th class="px-6 py-3.5 font-bold">Total Projects</th>
                <th class="px-6 py-3.5 text-right font-bold">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-xs">
            @forelse($groups as $g)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4 font-bold text-gray-900">
                        <a href="{{ route('dilp.groups.show', $g) }}" class="text-blue-700 hover:underline">
                            {{ $g->group_name }}
                        </a>
                    </td>
                    <td class="px-6 py-4 text-gray-600 font-medium">{{ $g->co_partner_name ?? '—' }}</td>
                    <td class="px-6 py-4 text-gray-600 font-medium">{{ $g->co_partner_contact ?? '—' }}</td>
                    <td class="px-6 py-4">
                        <span class="rounded-full bg-blue-50 border border-blue-200 px-2.5 py-1 text-xs font-bold text-dole-blue">
                            {{ $g->projects_count }} {{ Str::plural('project', $g->projects_count) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('dilp.groups.show', $g) }}"
                               class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-800 hover:bg-blue-100 hover:text-blue-900 transition shadow-2xs">
                                View Roster
                            </a>
                            <a href="{{ route('dilp.groups.edit', $g) }}"
                               class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition shadow-2xs">
                                Edit
                            </a>
                            <form action="{{ route('dilp.groups.destroy', $g) }}" method="POST"
                                  data-confirm="Are you sure you want to delete group {{ addslashes($g->group_name) }}?"
                                  data-confirm-title="Delete DILP Group"
                                  data-confirm-btn="Yes, Delete Group"
                                  class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100 hover:text-rose-900 transition shadow-2xs cursor-pointer">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400 font-medium">No DILP groups created yet</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $groups->links() }}</div>
</div>
@endsection
