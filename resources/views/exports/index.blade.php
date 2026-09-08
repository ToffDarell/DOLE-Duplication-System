@extends('layouts.app')
@section('title', 'Report Generator & Exports')
@section('page-title', 'Report Generation & Multi-Category Export Center')
@section('page-subtitle', 'Configure cross-category filters, preview matched masterlist records, and export official PDF, Excel, and CSV reports')

@section('content')
<div x-data="{ showLegend: false }">
    {{-- DOLE Header Banner with Integrated Export Toolbar --}}
    <div class="mb-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 rounded-2xl border border-blue-700/50 bg-blue-800 p-5 sm:p-6 text-white shadow-lg relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 h-44 w-44 rounded-full bg-white/5 blur-3xl pointer-events-none"></div>
        <div class="flex items-center gap-3.5 relative z-10">
            <div class="rounded-2xl bg-white p-2 shadow-md border border-white/20 shrink-0">
                <img src="{{ asset('logo dole.png') }}" alt="DOLE Official Logo" class="h-10 w-10 sm:h-12 sm:w-12 object-contain bg-white">
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white/15 border border-white/20 px-2.5 py-0.5 text-[10px] font-extrabold text-white uppercase tracking-wider">DOLE Bukidnon Field Office</span>
                    <span class="rounded-full bg-emerald-400/20 border border-emerald-300/30 px-2.5 py-0.5 text-[10px] font-extrabold text-emerald-200 uppercase tracking-wider">Official Report Engine</span>
                </div>
                <h2 class="mt-1 text-lg sm:text-xl font-black tracking-tight text-white">Custom Beneficiary Report &amp; Export Console</h2>
            </div>
        </div>

        {{-- Direct Export Buttons in Top Header --}}
        <div class="flex flex-wrap items-center gap-2 relative z-10">
            {{-- Excel Export --}}
            <a href="{{ route('export.excel', request()->query()) }}" 
               class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-3.5 py-2 text-xs font-extrabold text-white shadow-sm transition cursor-pointer">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                <span>Export Excel (.xlsx)</span>
            </a>

            {{-- PDF Export --}}
            <a href="{{ route('export.pdf', request()->query()) }}" 
               class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 hover:bg-red-500 px-3.5 py-2 text-xs font-extrabold text-white shadow-sm transition cursor-pointer">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                <span>Export PDF</span>
            </a>

            {{-- CSV Export --}}
            <a href="{{ route('export.csv', request()->query()) }}" 
               class="inline-flex items-center gap-1.5 rounded-xl bg-white/20 hover:bg-white/30 border border-white/30 px-3 py-2 text-xs font-bold text-white transition shadow-2xs">
                <span>CSV</span>
            </a>
        </div>
    </div>

    {{-- Collapsible Indicator Key / Legend Card --}}
    <div class="mb-5 rounded-2xl border border-slate-200/80 bg-white shadow-2xs overflow-hidden transition-all">
        <div class="flex items-center justify-between p-3.5 sm:px-5 cursor-pointer hover:bg-slate-50/70 select-none" @click="showLegend = !showLegend">
            <div class="flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-800 shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Duplication Risk Level Definitions</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Click to <span x-text="showLegend ? 'collapse' : 'view'"></span> risk thresholds used for report filtering</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold text-slate-400 hidden sm:inline">Classification Scale</span>
                <div class="rounded-lg p-1 text-slate-400 hover:text-slate-600 transition-transform duration-200" :class="showLegend ? 'rotate-180' : ''">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </div>
            </div>
        </div>
        
        <div x-show="showLegend" x-transition.duration.200ms class="border-t border-slate-100 p-4 pt-3 bg-slate-50/40" style="display: none;">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 text-xs font-medium">
                <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50/60 p-3">
                    <span class="inline-flex h-7 w-12 shrink-0 items-center justify-center rounded-lg bg-red-600 text-xs font-black text-white shadow-2xs">100%</span>
                    <div>
                        <p class="font-extrabold text-red-950">Exact Duplicate</p>
                        <p class="text-[11px] text-red-800 mt-0.5">Identical Name, Birthdate, and Government ID match.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 rounded-xl border border-orange-200 bg-orange-50/60 p-3">
                    <span class="inline-flex h-7 w-16 shrink-0 items-center justify-center rounded-lg bg-orange-500 text-xs font-black text-white shadow-2xs">85%–99%</span>
                    <div>
                        <p class="font-extrabold text-orange-950">High Similarity Risk</p>
                        <p class="text-[11px] text-orange-800 mt-0.5">Near-identical name spellings or birthdates.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50/60 p-3">
                    <span class="inline-flex h-7 w-16 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-xs font-black text-white shadow-2xs">70%–84%</span>
                    <div>
                        <p class="font-extrabold text-amber-950">Moderate Risk</p>
                        <p class="text-[11px] text-amber-800 mt-0.5">Partial field match or identical house number.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 p-3">
                    <span class="inline-flex h-7 w-12 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-xs font-black text-white shadow-2xs">&lt;70%</span>
                    <div>
                        <p class="font-extrabold text-emerald-950">Low Risk / Distinct</p>
                        <p class="text-[11px] text-emerald-800 mt-0.5">Distinct records with minimal collision overlap.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Controls Form Card --}}
    <div class="mb-5 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-2xs">
        <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Multi-Category Report Parameters</h3>
                <p class="text-xs text-slate-500 mt-0.5">Customize target filters before exporting official masterlists</p>
            </div>
            <span class="rounded-lg bg-blue-50 border border-blue-200 px-2.5 py-0.5 text-xs font-extrabold text-blue-800">Live Dynamic Cascade</span>
        </div>

        <form method="GET" action="{{ route('reports.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                {{-- 1. Sex Filter --}}
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Sex / Gender</label>
                    <select name="sex" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                        <option value="">All (Male &amp; Female)</option>
                        <option value="Male" {{ request('sex') === 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ request('sex') === 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>

                {{-- 2. Municipality Filter --}}
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Municipality (Bukidnon)</label>
                    <select name="municipality" id="export_municipality_select" onchange="fetchExportBarangays(this.value)"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                        <option value="">All Municipalities</option>
                        @foreach($municipalities as $muni)
                            <option value="{{ $muni }}" {{ strcasecmp(request('municipality'), $muni) === 0 ? 'selected' : '' }}>
                                {{ $muni }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 3. Dependent Barangay Filter --}}
                <div>
                    <label class="mb-1.5 flex items-center justify-between text-xs font-extrabold text-slate-800">
                        <span>Barangay</span>
                        <span id="export_loading_indicator" class="text-[10px] text-blue-700 animate-pulse font-normal hidden">Loading...</span>
                    </label>
                    <select name="barangay" id="export_barangay_select"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $b)
                            <option value="{{ $b }}" {{ strcasecmp(request('barangay'), $b) === 0 ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 4. Age Range Filter --}}
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Age Range</label>
                    <select name="age_range" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                        <option value="">All Ages (18+)</option>
                        <option value="18-30" {{ request('age_range') === '18-30' ? 'selected' : '' }}>Youth / Young Adult (18–30)</option>
                        <option value="31-50" {{ request('age_range') === '31-50' ? 'selected' : '' }}>Adult Workforce (31–50)</option>
                        <option value="51-59" {{ request('age_range') === '51-59' ? 'selected' : '' }}>Mature Age (51–59)</option>
                        <option value="60+" {{ request('age_range') === '60+' ? 'selected' : '' }}>Senior Citizens (60+)</option>
                    </select>
                </div>

                {{-- 5. Duplication Risk Level --}}
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Duplication Risk Level</label>
                    <select name="risk_level" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                        <option value="">All Risk Levels</option>
                        <option value="exact" {{ request('risk_level') === 'exact' ? 'selected' : '' }}>Exact Duplicate (100% Match)</option>
                        <option value="high" {{ request('risk_level') === 'high' ? 'selected' : '' }}>High Risk (85%–99% Match)</option>
                        <option value="moderate" {{ request('risk_level') === 'moderate' ? 'selected' : '' }}>Moderate Risk (70%–84% Match)</option>
                        <option value="low" {{ request('risk_level') === 'low' ? 'selected' : '' }}>Low Risk / Distinct (&lt; 70%)</option>
                        <option value="clean" {{ request('risk_level') === 'clean' ? 'selected' : '' }}>Clean Records (No Duplicate Flags)</option>
                    </select>
                </div>

                {{-- 6. Program Filter --}}
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Program</label>
                    <select name="program" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                        <option value="">All Programs</option>
                        @foreach($programs as $prog)
                            <option value="{{ $prog->code }}" {{ request('program') === $prog->code ? 'selected' : '' }}>
                                {{ $prog->name }} ({{ $prog->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 7. Search Keyword --}}
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Keyword Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, ID, or Contact..."
                           class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                </div>

                {{-- 8. Date Range --}}
                <div class="flex items-center gap-2">
                    <div class="w-1/2">
                        <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-2 py-1.5 text-xs font-semibold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                    </div>
                    <div class="w-1/2">
                        <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-2 py-1.5 text-xs font-semibold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                <div class="flex items-center gap-2">
                    <button type="submit" class="rounded-xl bg-blue-800 hover:bg-blue-900 px-5 py-2 text-xs font-extrabold text-white shadow-sm transition cursor-pointer flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/>
                        </svg>
                        <span>Apply Filters &amp; Preview</span>
                    </button>
                    @if(request()->hasAny(['sex', 'municipality', 'barangay', 'age_range', 'risk_level', 'program', 'search', 'date_from', 'date_to']))
                        <a href="{{ route('reports.index') }}" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                            Reset All Filters
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    @php
        $activeReportQuery = array_filter(request()->query(), fn($v) => filled($v) && $v !== '');
        $currentRisk = request('risk_level', '');
    @endphp

    {{-- Filter Summary KPI Cards (Clickable Quick Filters preserving Municipality & active filters) --}}
    <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('reports.index', \Illuminate\Support\Arr::except($activeReportQuery, ['risk_level', 'page'])) }}"
           class="block rounded-2xl border transition p-4 {{ empty($currentRisk) ? 'border-blue-700 bg-blue-100/70 ring-2 ring-blue-600/30' : 'border-blue-200 bg-blue-50/60 hover:bg-blue-100/50' }}">
            <p class="text-xs font-bold text-blue-900 uppercase tracking-wider">Filtered Beneficiaries</p>
            <p class="text-2xl font-black text-blue-950 mt-1">{{ number_format($totalCount) }}</p>
            <p class="text-[11px] font-semibold text-blue-700 mt-0.5">Matching active parameters</p>
        </a>

        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">Sex Breakdown</p>
            <p class="text-xl font-extrabold text-slate-900 mt-1">{{ number_format($maleCount) }} Male / {{ number_format($femaleCount) }} Female</p>
            <p class="text-[11px] font-semibold text-slate-500 mt-0.5">In current filtered dataset</p>
        </div>

        <a href="{{ route('reports.index', array_merge(\Illuminate\Support\Arr::except($activeReportQuery, ['page']), ['risk_level' => 'exact'])) }}"
           class="block rounded-2xl border transition p-4 {{ $currentRisk === 'exact' ? 'border-red-700 bg-red-100/70 ring-2 ring-red-600/30' : 'border-red-200 bg-red-50/60 hover:bg-red-100/50' }}">
            <p class="text-xs font-bold text-red-900 uppercase tracking-wider">Exact Duplicates (100%)</p>
            <p class="text-2xl font-black text-red-950 mt-1">{{ number_format($exactDuplicateCount) }}</p>
            <p class="text-[11px] font-semibold text-red-800 mt-0.5">High-priority collisions</p>
        </a>

        <a href="{{ route('reports.index', array_merge(\Illuminate\Support\Arr::except($activeReportQuery, ['page']), ['risk_level' => 'high'])) }}"
           class="block rounded-2xl border transition p-4 {{ $currentRisk === 'high' ? 'border-orange-700 bg-orange-100/70 ring-2 ring-orange-600/30' : 'border-orange-200 bg-orange-50/60 hover:bg-orange-100/50' }}">
            <p class="text-xs font-bold text-orange-900 uppercase tracking-wider">High Risk Similarity (85–99%)</p>
            <p class="text-2xl font-black text-orange-950 mt-1">{{ number_format($highRiskCount) }}</p>
            <p class="text-[11px] font-semibold text-orange-800 mt-0.5">Phonetic &amp; demographic warnings</p>
        </a>
    </div>

    {{-- Quick Risk Filter Pills that preserve municipality, barangay, program, sex, etc. --}}
    <div class="mb-5 flex flex-wrap items-center gap-2">
        <span class="text-xs font-extrabold text-slate-500 mr-1">Quick View:</span>
        <a href="{{ route('reports.index', \Illuminate\Support\Arr::except($activeReportQuery, ['risk_level', 'page'])) }}"
           class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ empty($currentRisk) ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
            <span>All Records</span>
        </a>
        <a href="{{ route('reports.index', array_merge(\Illuminate\Support\Arr::except($activeReportQuery, ['page']), ['risk_level' => 'exact'])) }}"
           class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $currentRisk === 'exact' ? 'bg-red-600 text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
            <span>Exact (100%)</span>
        </a>
        <a href="{{ route('reports.index', array_merge(\Illuminate\Support\Arr::except($activeReportQuery, ['page']), ['risk_level' => 'high'])) }}"
           class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $currentRisk === 'high' ? 'bg-orange-500 text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
            <span>High Risk (85%–99%)</span>
        </a>
        <a href="{{ route('reports.index', array_merge(\Illuminate\Support\Arr::except($activeReportQuery, ['page']), ['risk_level' => 'moderate'])) }}"
           class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $currentRisk === 'moderate' ? 'bg-amber-500 text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
            <span>Moderate (70%–84%)</span>
        </a>
        <a href="{{ route('reports.index', array_merge(\Illuminate\Support\Arr::except($activeReportQuery, ['page']), ['risk_level' => 'clean'])) }}"
           class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $currentRisk === 'clean' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
            <span>Clean Records</span>
        </a>
    </div>

    {{-- Live Matched Records Preview Table (Alphabetical Order - 7 Clean Columns) --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50/80 px-6 py-3.5">
            <div>
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Filtered Masterlist Records Preview</h3>
                <p class="text-[11px] font-semibold text-slate-500 mt-0.5">Sorted alphabetically by Last Name, First Name (Showing up to 25 preview records)</p>
            </div>
            <span class="rounded-full bg-slate-200/80 px-3 py-1 text-xs font-black text-slate-800">
                Total Matches: {{ number_format($totalCount) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[950px]">
                <thead class="border-b border-slate-200 bg-slate-100/80 text-[11px] font-extrabold uppercase tracking-wider text-slate-600">
                    <tr>
                        <th class="px-4 py-3.5 w-12 text-center shrink-0">#</th>
                        <th class="px-5 py-3.5 min-w-[220px]">Full Name</th>
                        <th class="px-5 py-3.5 whitespace-nowrap min-w-[140px]">Date of Birth &amp; Demographics</th>
                        <th class="px-5 py-3.5 whitespace-nowrap min-w-[170px]">Location &amp; Address</th>
                        <th class="px-5 py-3.5 whitespace-nowrap min-w-[160px]">Gov ID &amp; Contact</th>
                        <th class="px-5 py-3.5 whitespace-nowrap min-w-[130px]">Program(s)</th>
                        <th class="px-5 py-3.5 text-center whitespace-nowrap min-w-[110px]">Risk Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs font-medium bg-white">
                    @forelse($previewBeneficiaries as $index => $b)
                        @php
                            $maxScore = $b->duplicateFlags->max('match_score') ?? 0;
                            if ($maxScore >= 100) {
                                $rBadge = 'bg-red-600 text-white';
                                $rText = 'Exact (100%)';
                            } elseif ($maxScore >= 85) {
                                $rBadge = 'bg-orange-500 text-white';
                                $rText = "High ({$maxScore}%)";
                            } elseif ($maxScore >= 70) {
                                $rBadge = 'bg-amber-500 text-white';
                                $rText = "Moderate ({$maxScore}%)";
                            } elseif ($maxScore > 0) {
                                $rBadge = 'bg-emerald-600 text-white';
                                $rText = "Low ({$maxScore}%)";
                            } else {
                                $rBadge = 'bg-slate-100 text-slate-700 border border-slate-200';
                                $rText = 'Clean (0%)';
                            }
                        @endphp
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="px-4 py-3.5 text-center text-slate-500 font-bold shrink-0">{{ $index + 1 }}</td>
                            
                            {{-- Beneficiary Full Name --}}
                            <td class="px-5 py-3.5 min-w-[220px]">
                                <a href="{{ route('beneficiaries.show', $b) }}" class="font-extrabold text-slate-900 hover:text-blue-800 hover:underline block">
                                    {{ rtrim($b->full_name, " \\\t\n\r\0\x0B.") }}
                                </a>
                            </td>

                            {{-- Combined Date of Birth, Age & Sex (No wrapping) --}}
                            <td class="px-5 py-3.5 text-slate-700 whitespace-nowrap">
                                <span class="font-semibold text-slate-800 block">{{ $b->date_of_birth ? $b->date_of_birth->format('M d, Y') : '—' }}</span>
                                <span class="block text-[11px] text-slate-400 font-normal mt-0.5">
                                    {{ $b->age ? $b->age . ' yrs old' : '' }} &middot; {{ $b->sex }}
                                </span>
                            </td>

                            {{-- Combined Municipality, Barangay & Address (No empty dashes) --}}
                            <td class="px-5 py-3.5 text-slate-700 whitespace-nowrap">
                                <span class="font-bold text-slate-900 block">{{ $b->municipality }}</span>
                                <span class="block text-[11px] text-slate-400 font-normal mt-0.5 truncate max-w-[200px]" title="{{ $b->address ? $b->address . ', ' : '' }}Brgy. {{ $b->barangay }}">
                                    {{ $b->address ? $b->address . ', ' : '' }}Brgy. {{ $b->barangay }}
                                </span>
                            </td>

                            {{-- Gov ID & Contact --}}
                            <td class="px-5 py-3.5 text-slate-700 whitespace-nowrap">
                                <span class="font-mono text-xs font-bold text-slate-800 block">{{ $b->government_id_number ?: '—' }}</span>
                                <span class="block text-[11px] text-slate-400 font-normal mt-0.5">{{ $b->contact_number ?: 'No contact' }}</span>
                            </td>

                            {{-- Program(s) Badge --}}
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="flex flex-wrap items-center gap-1">
                                    @forelse($b->beneficiaryPrograms as $bp)
                                        @php
                                            $progCode = $bp->program?->code;
                                            if ($progCode === 'TUPAD') {
                                                $pColor = 'bg-blue-50 text-blue-900 border-blue-200';
                                            } elseif ($progCode === 'SPES') {
                                                $pColor = 'bg-purple-50 text-purple-900 border-purple-200';
                                            } elseif ($progCode === 'DILP') {
                                                $pColor = 'bg-emerald-50 text-emerald-900 border-emerald-200';
                                            } else {
                                                $pColor = 'bg-amber-50 text-amber-900 border-amber-200';
                                            }
                                        @endphp
                                        <span class="inline-flex rounded-md border {{ $pColor }} px-2 py-0.5 text-[10px] font-bold shadow-2xs">
                                            {{ $progCode }} ({{ $bp->availment_year }})
                                        </span>
                                    @empty
                                        <span class="text-slate-400 text-xs italic">None</span>
                                    @endforelse
                                </div>
                            </td>

                            {{-- Risk Status --}}
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-flex rounded-md {{ $rBadge }} px-2 py-0.5 text-[10px] font-black shadow-2xs">
                                    {{ $rText }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500 font-bold">
                                No beneficiary records match the specified parameter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function fetchExportBarangays(municipality) {
    const selectEl = document.getElementById('export_barangay_select');
    const loadingEl = document.getElementById('export_loading_indicator');
    if (!selectEl) return;
    
    if (!municipality) {
        selectEl.innerHTML = '<option value="">All Barangays</option>';
        return;
    }
    
    if (loadingEl) loadingEl.classList.remove('hidden');
    
    fetch(`{{ route('api.barangays') }}?municipality=${encodeURIComponent(municipality)}`)
        .then(res => res.json())
        .then(data => {
            let html = '<option value="">All Barangays</option>';
            data.forEach(b => {
                html += `<option value="${b}">${b}</option>`;
            });
            selectEl.innerHTML = html;
        })
        .catch(err => {
            console.error('Failed to load barangays:', err);
        })
        .finally(() => {
            if (loadingEl) loadingEl.classList.add('hidden');
        });
}
</script>
@endsection
