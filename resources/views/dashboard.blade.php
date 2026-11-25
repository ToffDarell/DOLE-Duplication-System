@extends('layouts.app')
@section('title', 'Analytics & Reporting Dashboard')
@section('page-title', 'System Analytics & Intelligence Dashboard')
@section('page-subtitle', 'Real-time performance metrics, duplicate detection analytics, program allocations, and geographic insights')

@section('content')
{{-- DOLE Official Header Banner --}}
<div class="mb-6 flex flex-col lg:flex-row items-start lg:items-center justify-between rounded-2xl border border-blue-700/50 bg-blue-800 p-4 sm:p-6 text-white shadow-sm relative overflow-hidden gap-4">
    <div class="flex items-center gap-3 sm:gap-4 relative z-10 min-w-0">
        <div class="rounded-2xl bg-white p-2 shadow-xs border border-white/20 shrink-0">
            <img src="{{ asset('logo dole.png') }}" alt="DOLE Official Logo" class="h-10 w-10 sm:h-12 sm:w-12 object-contain bg-white">
        </div>
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                <span class="rounded-full bg-white/15 border border-white/20 px-2.5 py-0.5 text-[10px] font-bold text-white">DOLE Bukidnon Field Office</span>
                <span class="rounded-full bg-amber-400/20 border border-amber-300/30 px-2.5 py-0.5 text-[10px] font-bold text-amber-200 tabular-nums">CY {{ date('Y') }} Intelligence</span>
                <span class="text-[11px] font-medium text-blue-200/90 tabular-nums">&middot; Synced {{ now()->format('M d, h:i A') }}</span>
            </div>
            <h2 class="mt-1 text-base sm:text-xl font-black tracking-tight text-white leading-tight">Centralized Analytics &amp; Duplicate Engine Report</h2>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2 relative z-10 shrink-0 w-full lg:w-auto">
        @hasanyrole('Admin|Encoder')
        <a href="{{ route('import.index') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-xs font-bold text-blue-800 shadow-xs hover:bg-blue-50 active:scale-[0.98] transition-all">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
            {{ \App\Models\Program::isTupadOnly() ? 'Import TUPAD Excel' : 'Import Excel Roster' }}
        </a>
        @endhasanyrole
        @hasanyrole('Admin|Validator')
        <a href="{{ route('duplicates.index', ['status' => 'pending']) }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-white/25 bg-white/10 px-3.5 py-2 text-xs font-bold text-white hover:bg-white/20 active:scale-[0.98] transition-all">
            <svg class="h-4 w-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            Review Pending
            <span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-black text-white tabular-nums">{{ number_format($pendingDuplicates) }}</span>
        </a>
        @endhasanyrole
    </div>
</div>

{{-- Top Summary Cards (KPI Metrics) --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    {{-- Card 1: Total Beneficiaries Registered --}}
    <a href="{{ route('beneficiaries.index') }}" class="group gov-card gov-card-hover p-5 border-l-4 border-l-blue-700 flex flex-col justify-between h-full transition-all active:scale-[0.98]">
        <div>
            <div class="flex items-start justify-between gap-3 mb-2">
                <p class="text-sm font-bold text-slate-700 group-hover:text-blue-700 transition-colors leading-snug">Total Beneficiaries Registered</p>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-700 text-white shadow-xs">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v-.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900 tracking-tight tabular-nums">{{ number_format($totalBeneficiaries) }}</p>
        </div>
        <div class="mt-3 flex items-center justify-between gap-2 text-xs font-medium text-slate-400">
            <span>Master profiles across Bukidnon LGUs</span>
            <span class="font-bold text-blue-700 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">&rarr;</span>
        </div>
    </a>

    {{-- Card 2: Total Duplicates Flagged --}}
    <a href="{{ route('duplicates.index') }}" class="group gov-card gov-card-hover p-5 border-l-4 border-l-amber-500 flex flex-col justify-between h-full transition-[transform,border-color,box-shadow] active:scale-[0.98]">
        <div>
            <div class="flex items-start justify-between gap-3 mb-2">
                <p class="text-sm font-bold text-slate-700 group-hover:text-amber-700 transition-colors leading-snug">Total Duplicates Flagged</p>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-xs">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900 tracking-tight tabular-nums">{{ number_format($totalDuplicates) }}</p>
        </div>
        <div class="mt-3 flex items-center justify-between gap-2 text-xs font-medium text-slate-400 tabular-nums">
            <span>{{ number_format($pendingDuplicates) }} pending &middot; {{ number_format($overriddenCount) }} cleared / resolved</span>
            <span class="font-bold text-amber-600 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">&rarr;</span>
        </div>
    </a>

    {{-- Card 3: Pending Validation Queue Count --}}
    <a href="{{ route('duplicates.index', ['status' => 'pending']) }}" class="group gov-card gov-card-hover p-5 border-l-4 border-l-rose-600 flex flex-col justify-between h-full transition-all active:scale-[0.98]">
        <div>
            <div class="flex items-start justify-between gap-3 mb-2">
                <p class="text-sm font-bold text-slate-700 group-hover:text-rose-700 transition-colors leading-snug">Pending Validation Queue</p>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white shadow-xs">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900 tracking-tight tabular-nums">{{ number_format($pendingDuplicates) }}</p>
        </div>
        <div class="mt-3 flex items-center justify-between gap-2 text-xs font-medium text-slate-400">
            <span>Requires validator review &amp; decision</span>
            <span class="font-bold text-rose-600 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">&rarr;</span>
        </div>
    </a>

    {{-- Card 4: Total Program Availments --}}
    <a href="{{ route('projects.index') }}" class="group gov-card gov-card-hover p-5 border-l-4 border-l-emerald-600 flex flex-col justify-between h-full transition-all active:scale-[0.98]">
        <div>
            <div class="flex items-start justify-between gap-3 mb-2">
                <p class="text-sm font-bold text-slate-700 group-hover:text-emerald-700 transition-colors leading-snug">Total Program Availments</p>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-xs">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-black text-slate-900 tracking-tight tabular-nums">{{ number_format(array_sum($programDistribution)) }}</p>
        </div>
        <div class="mt-3 flex items-center justify-between gap-2 text-xs font-medium text-slate-400">
            <span>{{ \App\Models\Program::isTupadOnly() ? 'Total TUPAD enrollments (incl. repeat batches)' : 'Total enrollments across TUPAD, SPES, DILP & GIP' }}</span>
            <span class="font-bold text-emerald-700 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">&rarr;</span>
        </div>
    </a>
</div>

{{-- Collapsible Duplication Risk Level Reference --}}
<div x-data="{ showLegend: false }" class="mb-6 rounded-2xl border border-slate-200/80 bg-white shadow-2xs overflow-hidden transition-all">
    <div class="flex items-center justify-between p-3.5 sm:px-5 cursor-pointer hover:bg-slate-50/70 select-none" @click="showLegend = !showLegend">
        <div class="flex items-center gap-2.5">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-800 shrink-0">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xs font-black text-slate-900">Duplication Risk Level &amp; Indicator Legend</h3>
                <p class="text-[11px] text-slate-500 font-medium">Click to <span x-text="showLegend ? 'collapse' : 'view'"></span> official DOLE scoring thresholds</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold text-slate-400 hidden sm:inline">Official Scoring Metric</span>
            <div class="rounded-lg p-1 text-slate-400 hover:text-slate-600 transition-transform duration-200" :class="showLegend ? 'rotate-180' : ''">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
            </div>
        </div>
    </div>
    
    <div x-show="showLegend" x-transition.duration.200ms class="border-t border-slate-100 p-4 pt-3 bg-slate-50/40" style="display: none;">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 text-xs font-medium">
            {{-- Exact Duplicate (100%) --}}
            <div class="rounded-xl border border-red-200 bg-red-50/60 p-3.5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center justify-center min-w-16 px-2 py-0.5 rounded-lg bg-red-600 text-[11px] font-black text-white shadow-2xs shrink-0 tabular-nums">100%</span>
                        <h4 class="font-extrabold text-red-950 text-xs leading-snug">Exact Match</h4>
                    </div>
                    <p class="text-[11px] text-red-800 leading-relaxed">All core fields match identically (Full Name, Birthdate, and Address / Gov ID).</p>
                </div>
            </div>

            {{-- High Similarity Risk (85% - 99%) --}}
            <div class="rounded-xl border border-orange-200 bg-orange-50/60 p-3.5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center justify-center min-w-16 px-2 py-0.5 rounded-lg bg-orange-500 text-[11px] font-black text-white shadow-2xs shrink-0 tabular-nums">85%–99%</span>
                        <h4 class="font-extrabold text-orange-950 text-xs leading-snug">Wrong Spelling / Human Error</h4>
                    </div>
                    <p class="text-[11px] text-orange-800 leading-relaxed">Misspelled name, phonetic similarity, or single-digit birthdate encoding typo.</p>
                </div>
            </div>

            {{-- Moderate Similarity Risk (70% - 84%) --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3.5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center justify-center min-w-16 px-2 py-0.5 rounded-lg bg-amber-500 text-[11px] font-black text-white shadow-2xs shrink-0 tabular-nums">70%–84%</span>
                        <h4 class="font-extrabold text-amber-950 text-xs leading-snug">Missing Suffix / Middle Name</h4>
                    </div>
                    <p class="text-[11px] text-amber-800 leading-relaxed">Missing Jr./Sr. suffix, same name with different middle name, or namesake.</p>
                </div>
            </div>

            {{-- Low Similarity / Distinct (< 70%) --}}
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3.5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center justify-center min-w-16 px-2 py-0.5 rounded-lg bg-emerald-600 text-[11px] font-black text-white shadow-2xs shrink-0 tabular-nums">&lt;70%</span>
                        <h4 class="font-extrabold text-emerald-950 text-xs leading-snug">Below Threshold / Distinct</h4>
                    </div>
                    <p class="text-[11px] text-emerald-800 leading-relaxed">Divergent record details below the 70% DOLE duplicate flagging cutoff.</p>
                </div>
            </div>
        </div>
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

{{-- Row 1: Charts -- Duplicate Status Breakdown + Pending Risk Bands (TUPAD-Only) / Program Distribution --}}
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

    @if(\App\Models\Program::isTupadOnly())
    {{-- TUPAD Pending Queue Breakdown by DOLE Score Band (Clickable Shortcuts) --}}
    <div class="gov-card p-6 flex flex-col justify-between">
        <div>
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Pending Flags by DOLE Score Band</h3>
                    <p class="text-[11px] font-medium text-slate-500">Click any band to jump straight into its filtered review queue</p>
                </div>
                <span class="rounded-md bg-blue-50 px-2.5 py-1 text-[10px] font-extrabold text-blue-800 uppercase tabular-nums">{{ number_format($pendingDuplicates) }} Pending</span>
            </div>
            <div class="space-y-2.5">
                <a href="{{ route('duplicates.index', ['risk_level' => 'exact']) }}"
                   class="flex items-center justify-between rounded-xl border border-rose-200 bg-rose-50/60 px-4 py-3 transition hover:bg-rose-50">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex w-16 justify-center rounded-lg bg-rose-600 px-2 py-1 text-[11px] font-black text-white tabular-nums">100%</span>
                        <div>
                            <p class="text-xs font-extrabold text-rose-950">Exact Match</p>
                            <p class="text-[11px] text-rose-800">All core identity fields match identically</p>
                        </div>
                    </div>
                    <span class="text-lg font-black text-rose-700 tabular-nums">{{ number_format($pendingRiskBands['exact'] ?? 0) }} &rarr;</span>
                </a>

                <a href="{{ route('duplicates.index', ['risk_level' => 'high']) }}"
                   class="flex items-center justify-between rounded-xl border border-orange-200 bg-orange-50/60 px-4 py-3 transition hover:bg-orange-50">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex w-16 justify-center rounded-lg bg-orange-600 px-2 py-1 text-[11px] font-black text-white tabular-nums">85%–99%</span>
                        <div>
                            <p class="text-xs font-extrabold text-orange-950">Wrong Spelling / Human Error</p>
                            <p class="text-[11px] text-orange-800">Misspelled names or 1-digit birthdate typos</p>
                        </div>
                    </div>
                    <span class="text-lg font-black text-orange-700 tabular-nums">{{ number_format($pendingRiskBands['high'] ?? 0) }} &rarr;</span>
                </a>

                <a href="{{ route('duplicates.index', ['risk_level' => 'moderate']) }}"
                   class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50/60 px-4 py-3 transition hover:bg-amber-50">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex w-16 justify-center rounded-lg bg-amber-500 px-2 py-1 text-[11px] font-black text-white tabular-nums">70%–84%</span>
                        <div>
                            <p class="text-xs font-extrabold text-amber-950">Missing Suffix / Middle Name</p>
                            <p class="text-[11px] text-amber-800">Missing Jr./Sr., different middle name, or namesake</p>
                        </div>
                    </div>
                    <span class="text-lg font-black text-amber-700 tabular-nums">{{ number_format($pendingRiskBands['moderate'] ?? 0) }} &rarr;</span>
                </a>

                <a href="{{ route('duplicates.index', ['risk_level' => 'low']) }}"
                   class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50/60 px-4 py-3 transition hover:bg-emerald-50">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex w-16 justify-center rounded-lg bg-emerald-600 px-2 py-1 text-[11px] font-black text-white tabular-nums">&lt; 70%</span>
                        <div>
                            <p class="text-xs font-extrabold text-emerald-950">Below Threshold / Household Review</p>
                            <p class="text-[11px] text-emerald-800">Distinct individuals flagged for household check</p>
                        </div>
                    </div>
                    <span class="text-lg font-black text-emerald-700 tabular-nums">{{ number_format($pendingRiskBands['low'] ?? 0) }} &rarr;</span>
                </a>
            </div>
        </div>
        <div class="mt-4 border-t border-slate-100 pt-3 text-center">
            <p class="text-[11px] font-semibold text-slate-500">
                Prioritize 100% Exact and 85%–99% flags first; bulk-clear &lt; 70% flags after barangay verification.
            </p>
        </div>
    </div>
    @else
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
    @endif
</div>

{{-- Row 2: Registration & Duplicate Trends (Full-Width Primary Trend Chart) --}}
<div class="mb-8">
    {{-- Chart 3: Registration & Duplicate Trends --}}
    <div class="gov-card p-6 flex flex-col justify-between">
        <div>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3.5">
                <div>
                    <h3 class="text-xs font-extrabold text-slate-900">Registration &amp; Duplicate Trends</h3>
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
    <div class="gov-card overflow-hidden lg:col-span-2 min-w-0">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50/80 px-5 py-4">
            <div>
                <h3 class="text-xs font-extrabold text-slate-900">Recently Registered Beneficiaries</h3>
                <p class="text-[11px] font-medium text-slate-500">Latest additions to the Bukidnon regional beneficiary database</p>
            </div>
            <a href="{{ route('beneficiaries.index') }}" class="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-extrabold text-slate-800 shadow-2xs transition-colors hover:bg-slate-100 hover:text-blue-800">
                View Full Registry &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-slate-200 bg-slate-100/70 text-[11px] font-bold text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Beneficiary Name</th>
                        <th class="whitespace-nowrap px-4 py-3">Municipality</th>
                        <th class="whitespace-nowrap px-4 py-3">Date of Birth</th>
                        <th class="hidden xl:table-cell whitespace-nowrap px-4 py-3">Encoded By</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs font-medium bg-white">
                    @forelse($recentBeneficiaries as $b)
                        <tr class="transition-colors hover:bg-slate-50/80">
                            <td class="px-4 py-3 font-extrabold text-slate-900 max-w-[220px]">
                                <a href="{{ route('beneficiaries.show', $b) }}" class="block truncate hover:text-blue-800 hover:underline" title="{{ $b->full_name }}">{{ $b->full_name }}</a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-700 font-semibold">{{ $b->municipality }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-700 font-semibold tabular-nums">{{ $b->date_of_birth?->format('M d, Y') }}</td>
                            <td class="hidden xl:table-cell whitespace-nowrap px-4 py-3 text-slate-600 font-medium max-w-[140px] truncate">{{ $b->creator?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('beneficiaries.show', $b) }}" class="group inline-flex items-center gap-1 whitespace-nowrap rounded-lg bg-slate-100 border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-800 hover:bg-blue-800 hover:text-white hover:border-blue-800 active:scale-[0.97] transition-[background-color,color,border-color,transform] shadow-2xs">
                                    <span>View</span>
                                    <span aria-hidden="true">&rarr;</span>
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
