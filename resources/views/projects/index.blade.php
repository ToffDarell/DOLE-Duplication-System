@extends('layouts.app')
@section('title', 'Program Projects')
@section('page-title', 'Projects Directory')
@section('page-subtitle', 'Manage program projects, view enrolled beneficiaries, and track allocations across Bukidnon')

@section('content')
{{-- Official Header & Action Controls --}}
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <div class="flex items-center gap-2">
            <span class="rounded-full bg-blue-100 border border-blue-200 px-2.5 py-0.5 text-[10px] font-extrabold text-blue-900 uppercase tracking-wide">
                DOLE Bukidnon Field Office
            </span>
            <span class="text-xs text-slate-400 font-bold">&bull;</span>
            <span class="text-xs font-semibold text-slate-500">
                CY {{ date('Y') }} Implementation
            </span>
        </div>
        <h2 class="mt-1 text-xl font-black text-slate-900 tracking-tight">Program Projects Masterlist</h2>
    </div>

    {{-- Header Action Buttons --}}
    <div class="flex flex-wrap items-center gap-2.5">
        <a href="{{ route('beneficiaries.index', $selected ? ['program' => $selected->code] : []) }}"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50">
            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
            </svg>
            <span>All Beneficiaries</span>
        </a>

        @hasanyrole('Admin|Encoder')
        <a href="{{ route('import.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50">
            <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
            </svg>
            <span>Import Excel</span>
        </a>

        <a href="{{ route('projects.create', ['program' => $selected?->code]) }}"
           class="inline-flex items-center gap-2 rounded-xl bg-blue-800 px-4 py-2.5 text-xs font-extrabold text-white shadow-md transition hover:bg-blue-900 focus:ring-2 focus:ring-blue-600/30">
            <svg class="h-4 w-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span>New Project</span>
        </a>
        @endhasanyrole
    </div>
</div>

{{-- 4 DOLE Program Selection Cards --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach($programs as $prog)
        @php
            $isSelected = $selected?->id === $prog->id;
            $theme = match($prog->code) {
                'TUPAD' => [
                    'border' => 'border-l-blue-700',
                    'active_border' => 'border-blue-700 bg-blue-50/80 ring-2 ring-blue-500/20',
                    'icon_bg' => 'bg-blue-700 text-white',
                    'text_code' => 'text-blue-900',
                    'desc' => 'Emergency Employment for Displaced Workers',
                    'pill' => 'bg-blue-100 text-blue-900',
                    'badge' => 'border-blue-200 bg-blue-50 text-blue-900',
                ],
                'SPES' => [
                    'border' => 'border-l-emerald-600',
                    'active_border' => 'border-emerald-600 bg-emerald-50/80 ring-2 ring-emerald-500/20',
                    'icon_bg' => 'bg-emerald-600 text-white',
                    'text_code' => 'text-emerald-900',
                    'desc' => 'Special Program for Employment of Students',
                    'pill' => 'bg-emerald-100 text-emerald-900',
                    'badge' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
                ],
                'DILP' => [
                    'border' => 'border-l-amber-600',
                    'active_border' => 'border-amber-600 bg-amber-50/80 ring-2 ring-amber-500/20',
                    'icon_bg' => 'bg-amber-600 text-white',
                    'text_code' => 'text-amber-900',
                    'desc' => 'Integrated Livelihood & Emergency Grants',
                    'pill' => 'bg-amber-100 text-amber-900',
                    'badge' => 'border-amber-200 bg-amber-50 text-amber-900',
                ],
                'GIP' => [
                    'border' => 'border-l-purple-700',
                    'active_border' => 'border-purple-700 bg-purple-50/80 ring-2 ring-purple-500/20',
                    'icon_bg' => 'bg-purple-700 text-white',
                    'text_code' => 'text-purple-900',
                    'desc' => 'Government Internship Program for Youth',
                    'pill' => 'bg-purple-100 text-purple-900',
                    'badge' => 'border-purple-200 bg-purple-50 text-purple-900',
                ],
                default => [
                    'border' => 'border-l-slate-700',
                    'active_border' => 'border-slate-700 bg-slate-50',
                    'icon_bg' => 'bg-slate-700 text-white',
                    'text_code' => 'text-slate-900',
                    'desc' => $prog->name,
                    'pill' => 'bg-slate-100 text-slate-800',
                    'badge' => 'border-slate-200 bg-slate-50 text-slate-800',
                ]
            };
        @endphp

        <a href="{{ $isSelected ? route('projects.index') : route('projects.index', ['program' => $prog->code]) }}"
           class="gov-card p-5 transition-all duration-200 relative flex flex-col justify-between hover:shadow-md border-l-4 {{ $theme['border'] }} {{ $isSelected ? $theme['active_border'] . ' shadow-sm' : 'bg-white hover:border-slate-300' }}">

            {{-- Top Row: Code & Icon --}}
            <div>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xl font-black tracking-tight {{ $theme['text_code'] }}">{{ $prog->code }}</span>
                            @if($isSelected)
                                <span class="rounded-full bg-blue-700 text-white p-0.5" title="Filter active">
                                    <svg class="h-3 w-3 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                </span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-[11px] font-semibold text-slate-500 leading-snug line-clamp-1">{{ $theme['desc'] }}</p>
                    </div>

                    {{-- Icon Container --}}
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $theme['icon_bg'] }} shadow-xs">
                        @if($prog->code === 'TUPAD')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/>
                            </svg>
                        @elseif($prog->code === 'SPES')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
                            </svg>
                        @elseif($prog->code === 'DILP')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a5.97 5.97 0 00-.942 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                            </svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.364a4.125 4.125 0 00-6.338 0 .375.375 0 01-.58-.372 4.875 4.875 0 017.498 0 .375.375 0 01-.58.372z"/>
                            </svg>
                        @endif
                    </div>
                </div>

                {{-- Metrics Numbers --}}
                <div class="mt-4 flex items-baseline justify-between border-t border-slate-100 pt-3">
                    <div>
                        <span class="text-2xl font-black text-slate-900 tracking-tight">{{ $prog->projects_count }}</span>
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">Projects</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-extrabold text-slate-800">{{ number_format($prog->beneficiaries_count) }}</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Beneficiaries</span>
                    </div>
                </div>
            </div>

            {{-- Unassigned Alert pill if applicable --}}
            @if($prog->unassigned_count > 0)
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50/90 px-2.5 py-1 text-[11px] font-bold text-amber-800 flex items-center justify-between">
                    <span class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                        <span>{{ number_format($prog->unassigned_count) }} unassigned</span>
                    </span>
                    <span class="text-[10px] font-extrabold underline">Filter &rarr;</span>
                </div>
            @endif
        </a>
    @endforeach
</div>

{{-- Search & Program Filter Pill Bar --}}
<div class="gov-card p-4 mb-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        {{-- Quick Filter Pills --}}
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('projects.index', request('search') ? ['search' => request('search')] : []) }}"
               class="rounded-xl px-3 py-1.5 text-xs font-extrabold transition {{ empty($selected) ? 'bg-blue-800 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                All Programs ({{ $totalProjectsCount }})
            </a>
            @foreach($programs as $p)
                <a href="{{ route('projects.index', array_merge(request()->query(), ['program' => $p->code])) }}"
                   class="rounded-xl px-3 py-1.5 text-xs font-extrabold transition {{ $selected?->id === $p->id ? 'bg-blue-800 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    {{ $p->code }} ({{ $p->projects_count }})
                </a>
            @endforeach
        </div>

        {{-- Search Input Form --}}
        <form method="GET" action="{{ route('projects.index') }}" class="flex items-center gap-2">
            @if($selected)
                <input type="hidden" name="program" value="{{ $selected->code }}">
            @endif
            <div class="relative w-full sm:w-72">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search project name, town..."
                       class="w-full rounded-xl border border-slate-300 bg-slate-50/50 py-2 pl-9 pr-3 text-xs font-medium text-slate-900 placeholder-slate-400 focus:border-blue-700 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-700">
            </div>

            <button type="submit"
                    class="rounded-xl bg-slate-900 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-slate-800">
                Filter
            </button>

            @if(request('search') || request('program'))
                <a href="{{ route('projects.index') }}"
                   class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100"
                   title="Reset all filters">
                    Reset
                </a>
            @endif
        </form>
    </div>
</div>

{{-- Active Filter Context Notice --}}
@if($selected)
    <div class="mb-4 flex items-center justify-between rounded-xl border border-blue-200 bg-blue-50/70 px-4 py-2.5 text-xs text-blue-900">
        <div class="flex items-center gap-2">
            <span class="font-extrabold uppercase">Filtered Program:</span>
            <span class="font-bold underline">{{ $selected->code }} &mdash; {{ $selected->name }}</span>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('beneficiaries.index', ['program' => $selected->code]) }}" class="font-bold text-blue-800 hover:underline">
                View all {{ $selected->code }} beneficiaries &rarr;
            </a>
            <a href="{{ route('projects.index') }}" class="font-semibold text-slate-500 hover:text-slate-800">
                Clear filter &times;
            </a>
        </div>
    </div>
@endif

{{-- Projects Grid or Empty State --}}
<div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
    @forelse($projects as $project)
        @php
            $progBadge = match($project->program->code) {
                'TUPAD' => 'bg-blue-100 text-blue-900 border-blue-200',
                'SPES' => 'bg-emerald-100 text-emerald-900 border-emerald-200',
                'DILP' => 'bg-amber-100 text-amber-900 border-amber-200',
                'GIP' => 'bg-purple-100 text-purple-900 border-purple-200',
                default => 'bg-slate-100 text-slate-900 border-slate-200'
            };
        @endphp
        <div class="gov-card flex flex-col justify-between p-5 transition-all duration-200 hover:border-blue-400 hover:shadow-md group">
            <div>
                {{-- Card Header: Badges --}}
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="rounded-md border px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider {{ $progBadge }}">
                            {{ $project->program->code }}
                        </span>
                        @if($project->implementation_type)
                            <span class="rounded-md bg-slate-100 border border-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700">
                                {{ $project->implementation_type }}
                            </span>
                        @endif
                    </div>
                    @if($project->year)
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 tabular-nums">
                            CY {{ $project->year }}
                        </span>
                    @endif
                </div>

                {{-- Project Name & Details --}}
                <h3 class="mt-3 text-base font-extrabold text-slate-900 leading-snug group-hover:text-blue-800 transition">
                    <a href="{{ route('projects.show', $project) }}">
                        {{ $project->name }}
                    </a>
                </h3>

                @if($project->description)
                    <p class="mt-1 text-xs text-slate-500 line-clamp-2 leading-relaxed">
                        {{ $project->description }}
                    </p>
                @endif

                {{-- Location & Date of Implementation --}}
                <div class="mt-3 space-y-1.5 text-xs text-slate-600">
                    <div class="flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        <span class="font-semibold">{{ $project->municipality ?? 'Bukidnon Field Office' }}</span>
                    </div>
                    @if($project->start_date || $project->end_date)
                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500 tabular-nums">
                            <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                            <span>{{ $project->start_date ? $project->start_date->format('M d, Y') : 'Open' }}{{ $project->end_date ? ' – ' . $project->end_date->format('M d, Y') : '' }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Card Footer: Beneficiaries Count & Action Button --}}
            <div class="mt-5 border-t border-slate-100 pt-3.5 flex items-center justify-between">
                <div class="flex items-center gap-1.5 text-xs font-black text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 tabular-nums">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                    <span>
                        {{ number_format($project->beneficiary_programs_count) }}{{ $project->target_beneficiaries ? ' / ' . number_format($project->target_beneficiaries) : '' }} Beneficiaries
                    </span>
                </div>

                <div class="flex items-center gap-2.5">
                    @hasanyrole('Admin|Encoder')
                    <a href="{{ route('projects.edit', $project) }}"
                       class="text-xs font-bold text-slate-500 hover:text-slate-800 transition"
                       title="Edit project details or dates">
                        Edit
                    </a>
                    @endhasanyrole
                    <a href="{{ route('projects.show', $project) }}"
                       class="inline-flex items-center gap-1 text-xs font-extrabold text-blue-800 hover:text-blue-900 group-hover:translate-x-0.5 transition">
                        <span>Open</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    @empty
        {{-- Professional Government Empty State matching the UI system --}}
        <div class="col-span-full gov-card p-10 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-700 shadow-2xs border border-blue-100">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12.75M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                </svg>
            </div>

            <h3 class="mt-4 text-base font-black text-slate-900 tracking-tight">No Program Projects Recorded Yet</h3>
            <p class="mt-1 text-xs font-medium text-slate-500 max-w-md mx-auto leading-relaxed">
                Projects group beneficiaries under specific program implementations (such as TUPAD batches, SPES assignments, or DILP grants).
                You can create a project manually or automatically assign one during Excel masterlist imports.
            </p>

            @hasanyrole('Admin|Encoder')
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('projects.create', ['program' => $selected?->code]) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-blue-800 px-4 py-2.5 text-xs font-extrabold text-white shadow-md hover:bg-blue-900 transition">
                    <svg class="h-4 w-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span>Create First Project</span>
                </a>

                <a href="{{ route('import.index') }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 transition">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                    <span>Import Excel Masterlist</span>
                </a>
            </div>
            @endhasanyrole
        </div>
    @endforelse
</div>

{{-- Pagination Links --}}
<div class="mt-6">
    {{ $projects->links() }}
</div>
@endsection
