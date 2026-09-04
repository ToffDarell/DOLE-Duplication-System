@extends('layouts.app')
@section('title', 'DILP Groups')
@section('page-title', 'DILP Groups & Cooperatives')
@section('page-subtitle', 'Manage DILP group associations, accredited cooperatives, and co-partner details')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('dilp.groups.create') }}" 
           class="inline-flex items-center gap-2 rounded-xl bg-blue-800 hover:bg-blue-900 px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition cursor-pointer">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span>Create DILP Group</span>
        </a>
        <span class="rounded-full bg-slate-100 border border-slate-200 px-3 py-1 text-xs font-bold text-slate-700">
            Total Groups: <span class="font-extrabold text-slate-900">{{ $groups->total() }}</span>
        </span>
    </div>
</div>

<div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs min-w-[750px]">
            <thead class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3.5 min-w-[220px]">Group / Association Name</th>
                    <th class="px-5 py-3.5 whitespace-nowrap min-w-[180px]">Co-Partner Organization</th>
                    <th class="px-5 py-3.5 whitespace-nowrap min-w-[140px]">Co-Partner Contact</th>
                    <th class="px-5 py-3.5 whitespace-nowrap min-w-[120px]">Associated Projects</th>
                    <th class="px-5 py-3.5 text-right whitespace-nowrap min-w-[180px]">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium bg-white">
                @forelse($groups as $g)
                    @php
                        $groupInitials = strtoupper(substr($g->group_name, 0, 2));
                    @endphp
                    <tr class="transition hover:bg-slate-50/80">
                        {{-- Group Name with Icon/Initial --}}
                        <td class="px-5 py-3.5 min-w-[220px]">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-800 font-extrabold text-xs shadow-2xs border border-blue-200">
                                    {{ $groupInitials }}
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('dilp.groups.show', $g) }}" class="font-extrabold text-slate-900 text-xs sm:text-sm hover:text-blue-800 hover:underline block truncate" title="{{ $g->group_name }}">
                                        {{ $g->group_name }}
                                    </a>
                                    <span class="text-[11px] text-slate-400 font-normal">DILP Cooperative / Association</span>
                                </div>
                            </div>
                        </td>

                        {{-- Co-Partner Org --}}
                        <td class="px-5 py-3.5 whitespace-nowrap text-slate-700 font-semibold">
                            {{ $g->co_partner_name ?? '—' }}
                        </td>

                        {{-- Co-Partner Contact --}}
                        <td class="px-5 py-3.5 whitespace-nowrap text-slate-600 font-mono text-xs">
                            {{ $g->co_partner_contact ?? '—' }}
                        </td>

                        {{-- Projects Count Badge --}}
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-[10px] font-extrabold text-emerald-800 shadow-2xs">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                {{ $g->projects_count }} {{ Str::plural('Project', $g->projects_count) }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <a href="{{ route('dilp.groups.show', $g) }}"
                                   class="inline-flex items-center gap-1 rounded-xl border border-blue-300 bg-blue-50 px-2.5 py-1.5 text-xs font-bold text-blue-800 hover:bg-blue-100 transition shadow-2xs">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                    <span>Roster</span>
                                </a>
                                <a href="{{ route('dilp.groups.edit', $g) }}"
                                   class="inline-flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                                    <span>Edit</span>
                                </a>
                                <form action="{{ route('dilp.groups.destroy', $g) }}" method="POST"
                                      data-confirm="Are you sure you want to delete group {{ addslashes($g->group_name) }}?"
                                      data-confirm-title="Delete DILP Group"
                                      data-confirm-btn="Yes, Delete Group"
                                      data-confirm-variant="danger"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 rounded-xl border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100 transition shadow-2xs cursor-pointer">
                                        <span>Delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">
                            No DILP groups or cooperatives created yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">{{ $groups->links() }}</div>
</div>
@endsection
