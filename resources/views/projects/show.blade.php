@extends('layouts.app')
@section('title', $project->name)
@section('page-title', $project->name)
@section('page-subtitle', $project->program->code . ' project implementation and enrolled beneficiary roster')

@section('content')
{{-- Navigation Breadcrumb --}}
<div class="mb-5 flex items-center justify-between">
    <a href="{{ route('projects.index', ['program' => $project->program->code]) }}"
       class="inline-flex items-center gap-2 text-xs font-bold text-blue-800 hover:text-blue-900 transition">
        &larr; Back to {{ $project->program->code }} Projects
    </a>

    <div class="flex items-center gap-2 text-xs text-slate-500">
        <span class="font-bold">{{ $project->program->code }}</span>
        <span>&bull;</span>
        <span>CY {{ $project->year ?? date('Y') }}</span>
    </div>
</div>

@if(session('success'))
    <div class="mb-5 flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs font-semibold text-emerald-800 shadow-2xs">
        <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

{{-- Project Overview Banner --}}
@php
    $progBadge = match($project->program->code) {
        'TUPAD' => 'bg-blue-100 text-blue-900 border-blue-200',
        'SPES' => 'bg-emerald-100 text-emerald-900 border-emerald-200',
        'DILP' => 'bg-amber-100 text-amber-900 border-amber-200',
        'GIP' => 'bg-purple-100 text-purple-900 border-purple-200',
        default => 'bg-slate-100 text-slate-900 border-slate-200'
    };
@endphp
<div class="gov-card p-6 mb-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-md border px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider {{ $progBadge }}">
                    {{ $project->program->code }} &mdash; {{ $project->program->name }}
                </span>
                @if($project->implementation_type)
                    <span class="rounded-full bg-blue-50 border border-blue-200 px-2.5 py-0.5 text-[10px] font-bold text-blue-900">
                        {{ $project->implementation_type }}
                    </span>
                @endif
                @if($project->year)
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-700 tabular-nums">
                        CY {{ $project->year }}
                    </span>
                @endif
                @if($project->municipality)
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-semibold text-slate-700 flex items-center gap-1">
                        <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        <span>{{ $project->municipality }}</span>
                    </span>
                @endif
            </div>

            <h2 class="text-xl font-black text-slate-900 tracking-tight leading-snug">
                {{ $project->name }}
            </h2>

            @if($project->description)
                <p class="text-xs text-slate-600 leading-relaxed max-w-2xl">
                    {{ $project->description }}
                </p>
            @endif

            @if($project->start_date || $project->end_date)
                <p class="text-[11px] font-semibold text-slate-500 flex items-center gap-1.5 pt-1">
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                    <span>Period: {{ $project->start_date ? $project->start_date->format('M d, Y') : 'Open' }} &mdash; {{ $project->end_date ? $project->end_date->format('M d, Y') : 'Ongoing' }}</span>
                </p>
            @endif
        </div>

        {{-- Top Right Metrics & Action Buttons --}}
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-black text-emerald-800 flex items-center gap-2 tabular-nums">
                <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                <span>{{ number_format($statusCounts['all']) }}{{ $project->target_beneficiaries ? ' / ' . number_format($project->target_beneficiaries) : '' }} Enrolled</span>
            </div>

            @hasanyrole('Admin|Encoder')
            <a href="{{ route('projects.edit', $project) }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 transition"
               title="Edit project details and dates">
                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                <span>Edit Project</span>
            </a>

            <a href="{{ route('import.index', ['project' => $project->id]) }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-blue-800 px-3.5 py-2 text-xs font-extrabold text-white shadow-sm hover:bg-blue-900 transition">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                <span>Import to Project</span>
            </a>

            <form method="POST" action="{{ route('projects.destroy', $project) }}"
                  data-confirm="Delete this project? Enrolled beneficiaries will remain in the database as unassigned."
                  data-confirm-title="Delete Project"
                  data-confirm-btn="Yes, Delete Project">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50 transition"
                        title="Delete project">
                    Delete
                </button>
            </form>
            @endhasanyrole
        </div>
    </div>
</div>

{{-- Beneficiary Roster Header & Filters Bar --}}
<div class="gov-card p-4 mb-4">
    <form method="GET" action="{{ route('projects.show', $project) }}" class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        {{-- Left: Barangay and Status Dropdown Filters --}}
        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Barangay Filter Dropdown --}}
            <div class="flex items-center gap-1.5">
                <label for="filter-barangay" class="text-xs font-extrabold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                    Barangay:
                </label>
                <select name="barangay" id="filter-barangay" onchange="this.form.submit()"
                        class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-800 focus:border-blue-800 focus:outline-none focus:ring-1 focus:ring-blue-800">
                    <option value="">All Barangays ({{ $statusCounts['all'] }})</option>
                    @foreach($availableBarangays as $b)
                        <option value="{{ $b->barangay }}" @selected($selectedBarangay === $b->barangay)>
                            {{ $b->barangay }} ({{ $b->count }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status Filter Dropdown --}}
            <div class="flex items-center gap-1.5">
                <label for="filter-status" class="text-xs font-extrabold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                    Status:
                </label>
                <select name="status" id="filter-status" onchange="this.form.submit()"
                        class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-800 focus:border-blue-800 focus:outline-none focus:ring-1 focus:ring-blue-800">
                    <option value="">All Statuses</option>
                    <option value="approved" @selected($selectedStatus === 'approved')>Approved ({{ $statusCounts['approved'] }})</option>
                    <option value="pending" @selected($selectedStatus === 'pending')>Pending ({{ $statusCounts['pending'] }})</option>
                </select>
            </div>
        </div>

        {{-- Right: Search Input & Action Buttons --}}
        <div class="flex items-center gap-2">
            <div class="relative w-full sm:w-64">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search name, Gov ID..."
                       class="w-full rounded-xl border border-slate-300 bg-white py-2 pl-9 pr-3 text-xs font-medium text-slate-900 placeholder-slate-400 focus:border-blue-800 focus:outline-none focus:ring-1 focus:ring-blue-800">
            </div>

            <button type="submit" class="rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-bold text-white hover:bg-slate-800 transition">
                Filter
            </button>

            @if($selectedBarangay || $selectedStatus || request('search'))
                <a href="{{ route('projects.show', $project) }}"
                   class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition whitespace-nowrap"
                   title="Clear all filters">
                    Reset
                </a>
            @endif
        </div>
    </form>

    {{-- Active Filter Pills Notice --}}
    @if($selectedBarangay || $selectedStatus)
        <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3 text-xs">
            <span class="font-extrabold text-slate-500 text-[11px] uppercase tracking-wider">Active Filters:</span>
            @if($selectedBarangay)
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-900">
                    <span>Barangay: <strong>{{ $selectedBarangay }}</strong></span>
                    <a href="{{ route('projects.show', array_merge(request()->except('barangay'), ['project' => $project->id])) }}" class="hover:text-blue-700 text-sm font-bold">&times;</a>
                </span>
            @endif
            @if($selectedStatus)
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-900">
                    <span>Status: <strong>{{ ucfirst($selectedStatus) }}</strong></span>
                    <a href="{{ route('projects.show', array_merge(request()->except('status'), ['project' => $project->id])) }}" class="hover:text-emerald-700 text-sm font-bold">&times;</a>
                </span>
            @endif
            <a href="{{ route('projects.show', $project) }}" class="text-[11px] font-bold text-slate-500 hover:text-slate-800 underline ml-2">
                Clear all filters
            </a>
        </div>
    @endif
</div>

{{-- Roster Table --}}
<div class="gov-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-100/80 text-[10px] font-black uppercase tracking-wider text-slate-600 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3.5">Beneficiary Full Name</th>
                    <th class="px-4 py-3.5">DOB / Age / Sex</th>
                    <th class="px-4 py-3.5">Address / Location</th>
                    <th class="px-4 py-3.5">Government ID</th>
                    <th class="px-4 py-3.5 text-center">Program Status</th>
                    <th class="px-4 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($members as $bp)
                    @php $ben = $bp->beneficiary; @endphp
                    <tr class="hover:bg-slate-50/70 transition">
                        {{-- Name --}}
                        <td class="px-4 py-3.5">
                            <a href="{{ route('beneficiaries.show', $ben) }}"
                               class="font-black text-slate-900 hover:text-blue-800 transition block">
                                {{ $ben->full_name }}
                            </a>
                            <span class="text-[11px] text-slate-400 font-mono">
                                {{ $ben->contact_number ?? 'No contact' }}
                            </span>
                        </td>

                        {{-- DOB / Age / Sex --}}
                        <td class="px-4 py-3.5 text-slate-600">
                            <span class="font-bold text-slate-800 block">
                                {{ $ben->date_of_birth ? $ben->date_of_birth->format('M d, Y') : '—' }}
                            </span>
                            <span class="text-[11px] text-slate-500">
                                {{ $ben->age ? $ben->age . ' yrs' : '' }} &bull; {{ $ben->sex ?? '—' }}
                            </span>
                        </td>

                        {{-- Location --}}
                        <td class="px-4 py-3.5 text-slate-700">
                            <span class="font-bold block">{{ $ben->barangay ?? 'Central' }}</span>
                            <span class="text-[11px] text-slate-500">{{ $ben->municipality ?? 'Bukidnon' }}</span>
                        </td>

                        {{-- Gov ID --}}
                        <td class="px-4 py-3.5">
                            @if($ben->government_id_number)
                                <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-bold text-slate-800 block w-fit">
                                    {{ $ben->government_id_number }}
                                </span>
                                <span class="text-[10px] text-slate-400 font-medium">{{ $ben->government_id_type ?? 'ID' }}</span>
                            @else
                                <span class="text-slate-400 italic">None</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3.5 text-center">
                            @if($bp->status === 'approved')
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-900 border border-emerald-200">
                                    Approved
                                </span>
                            @elseif($bp->status === 'pending')
                                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-amber-900 border border-amber-200">
                                    Pending
                                </span>
                            @else
                                <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-rose-900 border border-rose-200">
                                    {{ $bp->status }}
                                </span>
                            @endif
                        </td>

                        {{-- Action --}}
                        <td class="px-4 py-3.5 text-right">
                            <a href="{{ route('beneficiaries.show', $ben) }}"
                               class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-blue-800 hover:bg-slate-50 transition">
                                <span>Profile</span>
                                <span>&rarr;</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <p class="text-sm font-bold text-slate-700">No beneficiaries found matching your filter criteria.</p>
                            @if($selectedBarangay || $selectedStatus || request('search'))
                                <div class="mt-2">
                                    <a href="{{ route('projects.show', $project) }}" class="text-xs font-bold text-blue-800 underline">
                                        Reset filters
                                    </a>
                                </div>
                            @else
                                <p class="text-xs text-slate-400 mt-1">Import an Excel file with this project selected to enroll beneficiaries.</p>
                                @hasanyrole('Admin|Encoder')
                                <div class="mt-4">
                                    <a href="{{ route('import.index', ['project' => $project->id]) }}"
                                       class="inline-flex items-center gap-1.5 rounded-xl bg-blue-800 px-4 py-2 text-xs font-extrabold text-white shadow-sm hover:bg-blue-900 transition">
                                        Import Excel Masterlist Now
                                    </a>
                                </div>
                                @endhasanyrole
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($members->hasPages())
        <div class="border-t border-slate-200 p-4">
            {{ $members->links() }}
        </div>
    @endif
</div>
@endsection
