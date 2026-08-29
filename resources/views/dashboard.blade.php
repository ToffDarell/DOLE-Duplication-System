@extends('layouts.app')
@section('title', 'Analytics & Reporting Dashboard')
@section('page-title', 'System Analytics & Intelligence Dashboard')
@section('page-subtitle', 'Real-time performance metrics, duplicate detection analytics, program allocations, and geographic insights')

@section('content')
{{-- DOLE Official Header Banner --}}
<div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between rounded-2xl border border-blue-700/50 bg-blue-800 p-6 text-white shadow-lg relative overflow-hidden">
    <div class="absolute -right-10 -bottom-10 h-44 w-44 rounded-full bg-white/5 blur-3xl pointer-events-none"></div>
    <div class="flex items-center gap-4 relative z-10">
        <div class="rounded-2xl bg-white p-2 shadow-md border border-white/20 shrink-0">
            <img src="{{ asset('logo dole.png') }}" alt="DOLE Official Logo" class="h-12 w-12 object-contain bg-white">
        </div>
        <div>
            <div class="flex items-center gap-2">
                <span class="rounded-full bg-white/15 border border-white/20 px-2.5 py-0.5 text-[10px] font-extrabold text-white uppercase tracking-wider">DOLE Bukidnon Field Office</span>
                <span class="rounded-full bg-amber-400/20 border border-amber-300/30 px-2.5 py-0.5 text-[10px] font-extrabold text-amber-200 uppercase tracking-wider">CY {{ date('Y') }} Intelligence</span>
            </div>
            <h2 class="mt-1 text-xl font-black tracking-tight text-white">Centralized Analytics & Duplicate Engine Report</h2>
        </div>
    </div>
    <div class="mt-4 sm:mt-0 text-left sm:text-right relative z-10">
        <p class="text-[11px] font-extrabold text-blue-200 uppercase tracking-wider">Last Sync Time</p>
        <p class="text-sm font-bold text-white tracking-tight">{{ now()->format('F d, Y - h:i A') }}</p>
    </div>
</div>

{{-- Duplication risk level reference --}}
<div class="mb-8 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
    <div class="mb-3 flex items-center justify-between border-b border-slate-100 pb-2.5">
        <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                </svg>
            </div>
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Duplication Risk Level & Indicator Legend</h3>
        </div>
        <span class="text-[11px] font-bold text-slate-500">Official Scoring Metric</span>
    </div>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 text-xs font-medium">
        {{-- Exact Duplicate (100%) --}}
        <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50/60 p-3">
            <span class="inline-flex h-7 w-12 shrink-0 items-center justify-center rounded-lg bg-red-600 text-xs font-black text-white shadow-2xs">100%</span>
            <div>
                <p class="font-extrabold text-red-950">Exact Duplicate</p>
                <p class="text-[11px] text-red-800 mt-0.5">Identical Full Name, Birthdate, and Government ID match.</p>
            </div>
        </div>

        {{-- High Similarity Risk (85% - 99%) --}}
        <div class="flex items-start gap-3 rounded-xl border border-orange-200 bg-orange-50/60 p-3">
            <span class="inline-flex h-7 w-16 shrink-0 items-center justify-center rounded-lg bg-orange-500 text-xs font-black text-white shadow-2xs">85%–99%</span>
            <div>
                <p class="font-extrabold text-orange-950">High Similarity Risk</p>
                <p class="text-[11px] text-orange-800 mt-0.5">Near-identical name spellings, phonetic match, or birthdates.</p>
            </div>
        </div>

        {{-- Moderate Similarity Risk (70% - 84%) --}}
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50/60 p-3">
            <span class="inline-flex h-7 w-16 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-xs font-black text-white shadow-2xs">70%–84%</span>
            <div>
                <p class="font-extrabold text-amber-950">Moderate Risk</p>
                <p class="text-[11px] text-amber-800 mt-0.5">Partial field match (e.g. same house number / address or sibling name).</p>
            </div>
        </div>

        {{-- Low Similarity / Distinct (< 70%) --}}
        <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 p-3">
            <span class="inline-flex h-7 w-12 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-xs font-black text-white shadow-2xs">&lt;70%</span>
            <div>
                <p class="font-extrabold text-emerald-950">Low Similarity / Distinct</p>
                <p class="text-[11px] text-emerald-800 mt-0.5">Divergent record details with minor overlap; low collision risk.</p>
            </div>
        </div>
    </div>
</div>

{{-- Top Summary Cards (KPI Metrics) --}}
<div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    {{-- Card 1: Total Beneficiaries Registered --}}
    <div class="gov-card gov-card-hover p-5 border-l-4 border-l-blue-700">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-700 text-white shadow-xs">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </div>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-[11px] font-extrabold text-blue-900 border border-blue-200">System Total</span>
        </div>
        <p class="text-3xl font-black text-slate-900 tracking-tight">{{ number_format($totalBeneficiaries) }}</p>
        <p class="mt-1 text-xs font-bold text-slate-600">Total Beneficiaries Registered</p>
    </div>

    {{-- Card 2: Total Duplicates Flagged --}}
    <div class="gov-card gov-card-hover p-5 border-l-4 border-l-amber-500">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-xs">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </div>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-extrabold text-amber-900 border border-amber-200">Engine Flagged</span>
        </div>
        <p class="text-3xl font-black text-slate-900 tracking-tight">{{ number_format($totalDuplicates) }}</p>
        <p class="mt-1 text-xs font-bold text-slate-600">Total Duplicate Records Detected</p>
        <p class="mt-0.5 text-[11px] font-medium text-slate-400">{{ number_format($pendingDuplicates) }} pending &middot; {{ number_format($overriddenCount) }} resolved / overridden</p>
    </div>

    {{-- Card 3: Pending Validation Queue Count --}}
    <div class="gov-card gov-card-hover p-5 border-l-4 border-l-rose-600">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-600 text-white shadow-xs">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="rounded-full bg-rose-100 px-3 py-1 text-[11px] font-extrabold text-rose-900 border border-rose-200">Action Required</span>
        </div>
        <p class="text-3xl font-black text-slate-900 tracking-tight">{{ number_format($pendingDuplicates) }}</p>
        <p class="mt-1 text-xs font-bold text-slate-600">Pending Validation Queue</p>
        <p class="mt-0.5 text-[11px] font-medium text-slate-400">Subset of {{ number_format($totalDuplicates) }} detected &middot; matches the header &amp; sidebar badge</p>
    </div>

    {{-- Card 4: Total Overridden / Approved Cases --}}
    <div class="gov-card gov-card-hover p-5 border-l-4 border-l-emerald-600">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-xs">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-extrabold text-emerald-900 border border-emerald-200">Audit Verified</span>
        </div>
        <p class="text-3xl font-black text-slate-900 tracking-tight">{{ number_format($overriddenCount) }}</p>
        <p class="mt-1 text-xs font-bold text-slate-600">Total Overridden / Approved Cases</p>
    </div>
</div>

{{-- Municipal breakdown & duplicate distribution --}}
<div class="mb-8 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-4 gap-2">
        <div>
            <h3 class="text-base font-black text-slate-900">Municipal Breakdown & Duplicate Distribution</h3>
            <p class="text-xs font-medium text-slate-500">Cross-sectional analysis of registered beneficiaries and duplicate incidences across Bukidnon Municipalities</p>
        </div>
        <a href="{{ route('reports.index') }}" class="rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition shadow-2xs self-start sm:self-auto">
            Generate Municipal Reports →
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Bar Chart: Total Beneficiaries per Municipality (2 Cols) --}}
        <div class="lg:col-span-2 flex flex-col justify-between rounded-xl border border-slate-200 bg-slate-50/50 p-5">
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">Total Beneficiaries per Municipality</h4>
                        <p class="text-[11px] font-medium text-slate-500">Beneficiary volume distribution across Bukidnon</p>
                    </div>
                    <span class="rounded-lg bg-blue-700 px-2.5 py-1 text-[10px] font-black text-white">Horizontal Bar</span>
                </div>
                <div class="relative h-136 w-full">
                    <canvas id="chartMunicipalBeneficiaries"></canvas>
                </div>
            </div>
            <div class="mt-3 border-t border-slate-200/80 pt-2.5 text-center">
                <p class="text-[11px] font-semibold text-slate-500">
                    Aggregated count of active beneficiaries grouped by local government unit.
                </p>
            </div>
        </div>

        {{-- Pie / Donut Chart: Duplicate Distribution across Municipalities (1 Col) --}}
        <div class="lg:col-span-1 flex flex-col justify-between rounded-xl border border-slate-200 bg-slate-50/50 p-5">
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">Duplicates by Municipality</h4>
                        <p class="text-[11px] font-medium text-slate-500">Proportion of flagged duplicate records</p>
                    </div>
                    <span class="rounded-lg bg-amber-500 px-2.5 py-1 text-[10px] font-black text-white">Pie Distribution</span>
                </div>
                <div class="relative h-80 w-full flex items-center justify-center">
                    <canvas id="chartMunicipalDuplicates"></canvas>
                </div>
            </div>
            <div class="mt-3 border-t border-slate-200/80 pt-2.5 text-center">
                <p class="text-[11px] font-semibold text-slate-500">
                    Pinpoints municipalities with higher rates of duplicate submission warnings.
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Row 1: Charts -- Duplicate Status Breakdown & Program Distribution --}}
<div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
    {{-- Chart 1: Duplicate Status Breakdown (Pie / Donut Chart) --}}
    <div class="gov-card p-6 flex flex-col justify-between">
        <div>
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Duplicate Status Breakdown</h3>
                    <p class="text-[11px] font-medium text-slate-500">Real-time status of clean vs flagged vs validated records</p>
                </div>
                <span class="rounded-md bg-slate-100 px-2.5 py-1 text-[10px] font-extrabold text-slate-700 uppercase">Donut Analysis</span>
            </div>
            <div class="relative flex items-center justify-center py-2 h-64">
                <canvas id="chartDuplicateStatus"></canvas>
            </div>
        </div>
        <div class="mt-4 border-t border-slate-100 pt-3 text-center">
            <p class="text-[11px] font-semibold text-slate-500">
                Gives DOLE Validators an immediate view of pending duplicate flags requiring action.
            </p>
        </div>
    </div>

    {{-- Chart 2: Program Distribution (Pie / Donut Chart) --}}
    <div class="gov-card p-6 flex flex-col justify-between">
        <div>
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Program Distribution</h3>
                    <p class="text-[11px] font-medium text-slate-500">Proportion of beneficiaries across TUPAD, SPES, DILP, & GIP</p>
                </div>
                <span class="rounded-md bg-blue-50 px-2.5 py-1 text-[10px] font-extrabold text-blue-800 uppercase">4 Core Programs</span>
            </div>
            <div class="relative flex items-center justify-center py-2 h-64">
                <canvas id="chartProgramDistribution"></canvas>
            </div>
        </div>
        <div class="mt-4 border-t border-slate-100 pt-3 text-center">
            <p class="text-[11px] font-semibold text-slate-500">
                Displays the overall allocation of registered beneficiaries across DOLE programs for CY {{ date('Y') }}.
            </p>
        </div>
    </div>
</div>

{{-- Row 2: Registration & Duplicate Trends + Geographic Distribution --}}
<div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
    {{-- Chart 3: Registration & Duplicate Trends --}}
    <div class="gov-card p-6 flex flex-col justify-between">
        <div>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3.5">
                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Registration & Duplicate Trends</h3>
                    <p class="text-[11px] font-medium text-slate-500">Monthly new registrations vs policy violations flagged (CY {{ date('Y') }})</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-semibold">
                    <span class="inline-flex items-center gap-1.5 text-indigo-600">
                        <span class="h-2.5 w-2.5 rounded-xs bg-indigo-600 shadow-2xs"></span> Registrations
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-rose-500">
                        <span class="h-2.5 w-2.5 rounded-full bg-rose-500 shadow-2xs"></span> Duplicates
                    </span>
                </div>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="chartRegistrationTrends"></canvas>
            </div>
        </div>
        <div class="mt-4 border-t border-slate-100 pt-3 text-center">
            <p class="text-[11px] font-semibold text-slate-500">
                Identifies seasonal registration surges and spike patterns in potential duplicate entries.
            </p>
        </div>
    </div>

    {{-- Chart 4: Top Municipalities Concentration --}}
    <div class="gov-card p-6 flex flex-col justify-between">
        <div>
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3.5">
                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Top Density Areas</h3>
                    <p class="text-[11px] font-medium text-slate-500">High concentration municipalities in Bukidnon</p>
                </div>
                <span class="bg-emerald-50 text-emerald-700 text-xs px-2.5 py-1 rounded-full font-medium border border-emerald-200/60 flex items-center gap-1 shrink-0">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Density Hubs
                </span>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="chartGeographicDistribution"></canvas>
            </div>
        </div>
        <div class="mt-4 border-t border-slate-100 pt-3 text-center">
            <p class="text-[11px] font-semibold text-slate-500">
                Visualizes high-density areas requiring closer monitoring for duplicate submissions.
            </p>
        </div>
    </div>
</div>

{{-- Row 3: Pending Action Console & Recent Registry --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Quick Action Duplicate Queue (1 Col) --}}
    <div class="gov-card p-6 flex flex-col justify-between lg:col-span-1">
        <div>
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Pending Flags Queue</h3>
                @hasanyrole('Admin|Validator')
                <a href="{{ route('duplicates.index') }}" class="text-xs font-extrabold text-blue-700 hover:underline">View Console →</a>
                @endhasanyrole
            </div>

            @if($recentDuplicates->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 mb-3 shadow-xs">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-sm font-black text-slate-900">No Pending Duplicates</p>
                    <p class="text-xs text-slate-500 font-medium">All flagged beneficiary records are currently resolved.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($recentDuplicates as $flag)
                        @php
                            $fScore = (int) $flag->match_score;
                            $fBg = $fScore >= 100 ? 'bg-red-600' : ($fScore >= 85 ? 'bg-orange-500' : ($fScore >= 70 ? 'bg-amber-500' : 'bg-emerald-600'));
                        @endphp
                        <div class="flex items-center gap-3.5 rounded-xl border {{ $flag->household_match_flag ? 'border-blue-200 bg-blue-50/50' : 'border-amber-200 bg-amber-50/60' }} p-3 transition hover:shadow-2xs">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $fBg }} font-black text-white text-xs shadow-2xs">
                                {{ $flag->match_score }}%
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-extrabold text-slate-900">{{ $flag->beneficiary?->full_name }}</p>
                                <p class="truncate text-[11px] font-semibold text-slate-600">
                                    {{ $flag->household_match_flag ? 'house number with' : 'matches' }} {{ $flag->matchedBeneficiary?->full_name }}
                                </p>
                            </div>
                            @hasanyrole('Admin|Validator')
                            <a href="{{ route('duplicates.index') }}" class="rounded-lg bg-white border border-slate-300 px-2.5 py-1 text-[11px] font-extrabold text-slate-800 shadow-2xs hover:bg-slate-50 transition shrink-0">
                                Review
                            </a>
                            @endhasanyrole
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Recent Beneficiaries Master Table (2 Cols) --}}
    <div class="gov-card overflow-hidden lg:col-span-2">
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50/80 px-6 py-4">
            <div>
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Recently Registered Beneficiaries</h3>
                <p class="text-[11px] font-medium text-slate-500">Latest additions to the Bukidnon regional beneficiary database</p>
            </div>
            <a href="{{ route('beneficiaries.index') }}" class="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-extrabold text-slate-800 shadow-2xs transition hover:bg-slate-100 hover:text-blue-800">
                View Full Registry →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-100/70 text-[11px] font-extrabold uppercase tracking-wider text-slate-700">
                    <tr>
                        <th class="px-6 py-3.5">Beneficiary Name</th>
                        <th class="px-6 py-3.5">Municipality</th>
                        <th class="px-6 py-3.5">Date of Birth</th>
                        <th class="px-6 py-3.5">Encoded By</th>
                        <th class="px-6 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs font-medium bg-white">
                    @forelse($recentBeneficiaries as $b)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-6 py-3.5 font-extrabold text-slate-900">
                                <a href="{{ route('beneficiaries.show', $b) }}" class="hover:text-blue-800 hover:underline">{{ $b->full_name }}</a>
                            </td>
                            <td class="px-6 py-3.5 text-slate-700 font-semibold">{{ $b->municipality }}</td>
                            <td class="px-6 py-3.5 text-slate-700 font-semibold">{{ $b->date_of_birth?->format('M d, Y') }}</td>
                            <td class="px-6 py-3.5 text-slate-700 font-semibold">{{ $b->creator?->name ?? '—' }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <a href="{{ route('beneficiaries.show', $b) }}" class="rounded-lg bg-slate-100 border border-slate-200 px-2.5 py-1 text-[11px] font-bold text-slate-800 hover:bg-blue-700 hover:text-white transition">
                                    View Profile
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500 font-semibold">No beneficiaries registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Global Chart.js Defaults
    Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#64748B';

    // Floating Tooltip Configuration
    const modernTooltipConfig = {
        enabled: true,
        backgroundColor: '#0F172A',
        titleColor: '#FFFFFF',
        bodyColor: '#F8FAFC',
        titleFont: { size: 12, weight: 'bold' },
        bodyFont: { size: 11, weight: '500' },
        padding: 10,
        cornerRadius: 8,
        displayColors: true
    };

    // 1. Municipal Breakdown: Total Beneficiaries — horizontal bar so every
    //    municipality name reads left-to-right without rotated/colliding labels.
    const municipalLabels = @json($municipalLabels);
    const municipalBeneficiaryCounts = @json($municipalBeneficiaryCounts);
    const ctxMuniBar = document.getElementById('chartMunicipalBeneficiaries');
    if (ctxMuniBar) {
        new Chart(ctxMuniBar.getContext('2d'), {
            type: 'bar',
            data: {
                labels: municipalLabels,
                datasets: [{
                    label: 'Registered Beneficiaries',
                    data: municipalBeneficiaryCounts,
                    backgroundColor: '#1E40AF',
                    hoverBackgroundColor: '#1D4ED8',
                    borderRadius: 5,
                    borderSkipped: false,
                    barThickness: 14,
                    maxBarThickness: 18,
                    categoryPercentage: 0.85
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 1000, easing: 'easeInOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: modernTooltipConfig
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: '#64748B' },
                        grid: { color: '#F1F5F9', drawBorder: false }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            color: '#475569',
                            font: { size: 10, weight: '600' },
                            autoSkip: false
                        }
                    }
                }
            }
        });
    }

    // 2. Municipal Breakdown: Duplicates by Municipality — the backend already
    //    ranks LGUs and folds the long tail into an "Others" slice, so the
    //    legend only ever shows a handful of entries.
    const municipalDuplicateDistribution = @json($municipalDuplicateDistribution);
    const ctxMuniPie = document.getElementById('chartMunicipalDuplicates');
    if (ctxMuniPie) {
        // Palette of distinct accessible colors for municipalities
        const municipalColors = [
            '#EF4444', '#F97316', '#F59E0B', '#EAB308', '#84CC16',
            '#10B981', '#06B6D4', '#0EA5E9', '#3B82F6', '#6366F1',
            '#8B5CF6', '#A855F7', '#D946EF', '#EC4899', '#F43F5E'
        ];

        let muniPieLabels = Object.keys(municipalDuplicateDistribution);
        let muniPieCounts = Object.values(municipalDuplicateDistribution);

        if (muniPieLabels.length === 0) {
            muniPieLabels = ['No duplicates flagged'];
            muniPieCounts = [1];
        }

        const muniPieColors = muniPieLabels.map((label, i) => {
            if (label === 'Others') return '#94A3B8';
            if (label === 'No duplicates flagged') return '#E2E8F0';
            return municipalColors[i % municipalColors.length];
        });

        new Chart(ctxMuniPie.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: muniPieLabels,
                datasets: [{
                    data: muniPieCounts,
                    backgroundColor: muniPieColors,
                    borderWidth: 2,
                    borderColor: '#FFFFFF',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 1000, easing: 'easeInOutQuart' },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 8,
                            padding: 8,
                            font: { size: 10, weight: '600' }
                        }
                    },
                    tooltip: modernTooltipConfig
                },
                cutout: '55%'
            }
        });
    }

    // 3. Duplicate Status Breakdown (Donut Chart)
    const duplicateStatusData = @json($duplicateStatusBreakdown);
    const ctxStatus = document.getElementById('chartDuplicateStatus');
    if (ctxStatus) {
        new Chart(ctxStatus.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(duplicateStatusData),
                datasets: [{
                    data: Object.values(duplicateStatusData),
                    backgroundColor: ['#10B981', '#E11D48', '#F59E0B', '#64748B'],
                    borderWidth: 2,
                    borderColor: '#FFFFFF',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 1000, easing: 'easeInOutQuart' },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, padding: 12, font: { weight: '600', size: 10 } }
                    },
                    tooltip: modernTooltipConfig
                },
                cutout: '68%'
            }
        });
    }

    // 4. Program Distribution (Donut Chart) — colour each slice by program code
    //    so TUPAD / SPES / DILP / GIP always render as distinct segments
    //    regardless of key order or how many programs currently have records.
    const programData = @json($programDistribution);
    const ctxProg = document.getElementById('chartProgramDistribution');
    if (ctxProg) {
        const programLabels = Object.keys(programData);
        const programPalette = { 'TUPAD': '#4F46E5', 'SPES': '#10B981', 'DILP': '#F59E0B', 'GIP': '#8B5CF6' };
        const programFallback = ['#4F46E5', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#0EA5E9'];
        const programColors = programLabels.map((label, i) => programPalette[label] ?? programFallback[i % programFallback.length]);

        new Chart(ctxProg.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: programLabels,
                datasets: [{
                    data: Object.values(programData),
                    backgroundColor: programColors,
                    borderWidth: 2,
                    borderColor: '#FFFFFF',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 1000, easing: 'easeInOutQuart' },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, padding: 12, font: { weight: '600', size: 11 } }
                    },
                    tooltip: modernTooltipConfig
                },
                cutout: '68%'
            }
        });
    }

    // 5. Registration & Duplicate Trends (Spline / Bar Combination Chart)
    const trendMonths = @json($trendMonths);
    const trendRegistrations = @json($trendRegistrations);
    const trendDuplicates = @json($trendDuplicates);
    const ctxTrends = document.getElementById('chartRegistrationTrends');
    if (ctxTrends) {
        const ctxT = ctxTrends.getContext('2d');
        const barGradient = ctxT.createLinearGradient(0, 0, 0, 300);
        barGradient.addColorStop(0, '#4F46E5');
        barGradient.addColorStop(1, '#818CF8');

        const lineAreaGradient = ctxT.createLinearGradient(0, 0, 0, 300);
        lineAreaGradient.addColorStop(0, 'rgba(244, 63, 94, 0.25)');
        lineAreaGradient.addColorStop(1, 'rgba(244, 63, 94, 0.00)');

        new Chart(ctxT, {
            type: 'bar',
            data: {
                labels: trendMonths,
                datasets: [
                    {
                        type: 'bar',
                        label: 'New Registrations',
                        data: trendRegistrations,
                        backgroundColor: barGradient,
                        hoverBackgroundColor: '#4338CA',
                        borderRadius: { topLeft: 6, topRight: 6 },
                        borderSkipped: 'bottom',
                        barPercentage: 0.55
                    },
                    {
                        type: 'line',
                        label: 'Duplicates Flagged',
                        data: trendDuplicates,
                        borderColor: '#F43F5E',
                        backgroundColor: lineAreaGradient,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#F43F5E'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 1000, easing: 'easeInOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: modernTooltipConfig
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748B', font: { size: 11, weight: '600' } } },
                    y: { beginAtZero: true, ticks: { precision: 0, color: '#64748B' }, grid: { color: '#E2E8F0', borderDash: [5, 5], drawBorder: false } }
                }
            }
        });
    }

    // 6. Geographic Distribution: Top Density Areas
    const geoStats = @json($geographicStats);
    const ctxGeo = document.getElementById('chartGeographicDistribution');
    if (ctxGeo) {
        const ctxG = ctxGeo.getContext('2d');
        const geoGradient = ctxG.createLinearGradient(0, 0, 400, 0);
        geoGradient.addColorStop(0, '#10B981');
        geoGradient.addColorStop(1, '#14B8A6');

        new Chart(ctxG, {
            type: 'bar',
            data: {
                labels: Object.keys(geoStats),
                datasets: [{
                    label: 'Beneficiaries',
                    data: Object.values(geoStats),
                    backgroundColor: geoGradient,
                    hoverBackgroundColor: '#059669',
                    borderRadius: 20,
                    barThickness: 16
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 1000, easing: 'easeInOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: modernTooltipConfig
                },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0, color: '#64748B' }, grid: { color: '#E2E8F0', borderDash: [5, 5], drawBorder: false } },
                    y: { grid: { display: false }, ticks: { color: '#64748B', font: { size: 11, weight: '600' } } }
                }
            }
        });
    }
});
</script>
@endpush
