@extends('layouts.app')
@section('title', 'Duplicate Flags')
@section('page-title', 'Duplicate Resolution Console')
@section('page-subtitle', 'Review, compare side-by-side, and resolve flagged duplicate beneficiary records')

@section('content')
{{-- Indicator Key / Legend Card (Panelists: Mark Ian Mukara & Glaiza Mae Libe) --}}
<div x-data="{ showLegend: false }" class="mb-5 rounded-2xl border border-slate-200/80 bg-white shadow-2xs overflow-hidden">
    <div class="flex items-center justify-between p-3.5 sm:px-5 cursor-pointer hover:bg-slate-50/70 select-none" @click="showLegend = !showLegend">
        <div class="flex items-center gap-2.5">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-800 shrink-0">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xs font-extrabold text-slate-900">Indicator Key &amp; Percentage Threshold Definitions</h3>
                <p class="text-[11px] text-slate-500 font-medium">Click to <span x-text="showLegend ? 'collapse' : 'view'"></span> DOLE similarity thresholds and audit criteria</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold text-slate-400 hidden sm:inline">DOLE Standardization Metric</span>
            <div class="rounded-lg p-1 text-slate-400 hover:text-slate-600 transition-transform duration-200" :class="showLegend ? 'rotate-180' : ''">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
            </div>
        </div>
    </div>
    
    <div x-show="showLegend" x-transition.duration.200ms class="border-t border-slate-100 p-4 pt-3 bg-slate-50/40" style="display: none;">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 text-xs font-medium">
            {{-- Exact Duplicate (100%) --}}
            <div class="rounded-xl border border-red-200 bg-red-50/60 p-3 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center justify-center min-w-14 px-2 py-0.5 rounded-lg bg-red-600 text-[10px] font-black text-white shadow-2xs shrink-0 tabular-nums">100%</span>
                        <h4 class="font-extrabold text-red-950 text-xs">Exact Match</h4>
                    </div>
                    <p class="text-[11px] text-red-800 leading-relaxed">All records match identically (Full Name, Birthdate, and Address / Gov ID).</p>
                </div>
            </div>

            {{-- High Similarity Risk (85% - 99%) --}}
            <div class="rounded-xl border border-orange-200 bg-orange-50/60 p-3 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center justify-center min-w-14 px-2 py-0.5 rounded-lg bg-orange-500 text-[10px] font-black text-white shadow-2xs shrink-0 tabular-nums">85%–99%</span>
                        <h4 class="font-extrabold text-orange-950 text-xs">Wrong Spelling / Human Error</h4>
                    </div>
                    <p class="text-[11px] text-orange-800 leading-relaxed">Misspelled name, phonetic similarity, or single-digit birthdate encoding typo.</p>
                </div>
            </div>

            {{-- Moderate Similarity Risk (70% - 84%) --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center justify-center min-w-14 px-2 py-0.5 rounded-lg bg-amber-500 text-[10px] font-black text-white shadow-2xs shrink-0 tabular-nums">70%–84%</span>
                        <h4 class="font-extrabold text-amber-950 text-xs">Missing Suffix / Middle Name</h4>
                    </div>
                    <p class="text-[11px] text-amber-800 leading-relaxed">Missing Jr./Sr. suffix, same name with different middle name, or namesake.</p>
                </div>
            </div>

            {{-- Low Similarity / Distinct (< 70%) --}}
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center justify-center min-w-14 px-2 py-0.5 rounded-lg bg-emerald-600 text-[10px] font-black text-white shadow-2xs shrink-0 tabular-nums">&lt; 70%</span>
                        <h4 class="font-extrabold text-emerald-950 text-xs">Below Threshold</h4>
                    </div>
                    <p class="text-[11px] text-emerald-800 leading-relaxed">Divergent record details below the 70% DOLE duplicate flagging cutoff.</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Tabs & Search Toolbar --}}
@php
    $activeDropdownFilterCount = collect(['program', 'municipality', 'barangay', 'risk_level', 'sex'])
        ->filter(fn ($k) => request()->filled($k))
        ->count();
@endphp
<div x-data="{ showMobileFilters: {{ $activeDropdownFilterCount > 0 ? 'true' : 'false' }} }" class="mb-6 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
    <form method="GET" action="{{ route('duplicates.index') }}" class="flex flex-col gap-3.5">
        @if(request('filter'))
            <input type="hidden" name="filter" value="{{ request('filter') }}">
        @endif

        {{-- Tier 1: Search Bar & Actions --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 sm:gap-3">
            {{-- Live Search Input --}}
            <div class="flex items-center rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2 shadow-2xs transition-colors focus-within:bg-white focus-within:border-blue-700 focus-within:ring-2 focus-within:ring-blue-100 flex-1 max-w-lg">
                <svg class="h-4 w-4 text-slate-400 shrink-0 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input type="text" name="search" aria-label="Search duplicate flags" value="{{ request('search') }}" placeholder="Live search by beneficiary name, ID, or address..."
                       class="w-full border-0 bg-transparent p-0 text-xs font-medium text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0">
                @if(request('search'))
                    <a href="{{ route('duplicates.index', request()->except(['search', 'page'])) }}" 
                       class="ml-2 text-slate-400 hover:text-slate-600 shrink-0" title="Clear search" aria-label="Clear search">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" @click="showMobileFilters = !showMobileFilters"
                        class="sm:hidden inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer">
                    <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
                    </svg>
                    <span>Filters{{ $activeDropdownFilterCount > 0 ? " ({$activeDropdownFilterCount})" : '' }}</span>
                </button>
                <button type="submit" class="flex-1 sm:flex-none justify-center rounded-xl bg-blue-800 hover:bg-blue-900 active:scale-[0.98] px-4 py-2 text-xs font-extrabold text-white shadow-2xs transition-[background-color,transform] flex items-center gap-1.5 cursor-pointer">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/>
                    </svg>
                    <span>Filter</span>
                </button>
                @if(request()->hasAny(['program', 'municipality', 'barangay', 'risk_level', 'sex', 'search', 'filter']))
                    <a href="{{ route('duplicates.index') }}" 
                       class="rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition-colors"
                       title="Reset all filters">
                        Reset
                    </a>
                @endif
            </div>
        </div>

        {{-- Tier 2: Category Selectors (Collapsible on Mobile, Always Visible on sm+) --}}
        <div :class="showMobileFilters ? 'grid' : 'hidden sm:grid'" class="grid-cols-1 gap-2.5 sm:grid-cols-2 {{ \App\Models\Program::isTupadOnly() ? 'lg:grid-cols-4' : 'lg:grid-cols-5' }}">
            @if(!\App\Models\Program::isTupadOnly())
                {{-- Program --}}
                <div>
                    <label for="duplicate_program_select" class="mb-1 block text-xs font-bold text-slate-600">Program</label>
                    <select id="duplicate_program_select" name="program" onchange="this.form.submit()"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs {{ request('program') ? 'border-blue-700 bg-blue-50/50 text-blue-900 ring-1 ring-blue-700' : '' }}">
                        <option value="">All Programs</option>
                        @foreach(\App\Models\Program::enabledCodes() as $prog)
                            <option value="{{ $prog }}" {{ strcasecmp(request('program'), $prog) === 0 ? 'selected' : '' }}>{{ $prog }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Municipality --}}
            <div>
                <label for="duplicate_municipality_select" class="mb-1 block text-xs font-bold text-slate-600">Municipality</label>
                <select name="municipality" id="duplicate_municipality_select" onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs {{ request('municipality') ? 'border-blue-700 bg-blue-50/50 text-blue-900 ring-1 ring-blue-700' : '' }}">
                    <option value="">All Municipalities</option>
                    @foreach($municipalities as $muni)
                        <option value="{{ $muni }}" {{ strcasecmp(request('municipality'), $muni) === 0 ? 'selected' : '' }}>{{ $muni }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Dependent Barangay --}}
            <div>
                <label for="duplicate_barangay_select" class="mb-1 flex items-center justify-between text-xs font-bold text-slate-600">
                    <span>Barangay</span>
                </label>
                <select name="barangay" id="duplicate_barangay_select" onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs {{ request('barangay') ? 'border-blue-700 bg-blue-50/50 text-blue-900 ring-1 ring-blue-700' : '' }}">
                    <option value="">All Barangays</option>
                    @foreach($barangays as $b)
                        <option value="{{ $b }}" {{ strcasecmp(request('barangay'), $b) === 0 ? 'selected' : '' }}>{{ $b }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Duplication Risk Level --}}
            <div>
                <label for="duplicate_risk_select" class="mb-1 block text-xs font-bold text-slate-600">Risk Level</label>
                <select id="duplicate_risk_select" name="risk_level" onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs {{ request('risk_level') ? 'border-blue-700 bg-blue-50/50 text-blue-900 ring-1 ring-blue-700' : '' }}">
                    <option value="">All Risk Levels</option>
                    <option value="exact" {{ request('risk_level') === 'exact' ? 'selected' : '' }}>Exact (100% Match)</option>
                    <option value="high" {{ request('risk_level') === 'high' ? 'selected' : '' }}>High Risk (85%–99%)</option>
                    <option value="moderate" {{ request('risk_level') === 'moderate' ? 'selected' : '' }}>Moderate (70%–84%)</option>
                    <option value="low" {{ request('risk_level') === 'low' ? 'selected' : '' }}>Low Risk (&lt; 70%)</option>
                </select>
            </div>

            {{-- Sex --}}
            <div>
                <label for="duplicate_sex_select" class="mb-1 block text-xs font-bold text-slate-600">Sex</label>
                <select id="duplicate_sex_select" name="sex" onchange="this.form.submit()"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs {{ request('sex') ? 'border-blue-700 bg-blue-50/50 text-blue-900 ring-1 ring-blue-700' : '' }}">
                    <option value="">All Sexes</option>
                    <option value="Male" {{ strcasecmp(request('sex'), 'Male') === 0 ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ strcasecmp(request('sex'), 'Female') === 0 ? 'selected' : '' }}>Female</option>
                </select>
            </div>
        </div>

        <div class="h-px bg-slate-100 w-full"></div>

        {{-- Tier 3: Category & Status Filter Pills --}}
        <div class="flex flex-wrap items-center gap-2">
            @php
                $activeQuery = array_filter(request()->query(), fn($v) => filled($v) && $v !== '');
            @endphp

            {{-- 1. All Flags --}}
            <a href="{{ route('duplicates.index', array_filter(Arr::except($activeQuery, ['filter', 'page']))) }}" 
               class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $filter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
                <span>All Flags</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'all' ? 'bg-slate-700 text-white' : 'bg-white text-slate-700 border border-slate-200' }}">
                    {{ number_format($counts['all'] ?? 0) }}
                </span>
            </a>

            {{-- 2. First Name Match --}}
            <a href="{{ route('duplicates.index', array_merge(Arr::except($activeQuery, ['page']), ['filter' => 'first_name'])) }}" 
               class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $filter === 'first_name' ? 'bg-amber-600 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
                <span>First Name</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'first_name' ? 'bg-amber-800 text-amber-100' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                    {{ number_format($counts['first_name'] ?? 0) }}
                </span>
            </a>

            {{-- 3. Surname Match --}}
            <a href="{{ route('duplicates.index', array_merge(Arr::except($activeQuery, ['page']), ['filter' => 'surname'])) }}" 
               class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $filter === 'surname' ? 'bg-indigo-600 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
                <span>Surname</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'surname' ? 'bg-indigo-800 text-indigo-100' : 'bg-indigo-50 text-indigo-800 border border-indigo-200' }}">
                    {{ number_format($counts['surname'] ?? 0) }}
                </span>
            </a>

            {{-- 4. Address Match --}}
            <a href="{{ route('duplicates.index', array_merge(Arr::except($activeQuery, ['page']), ['filter' => 'address'])) }}" 
               class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $filter === 'address' ? 'bg-blue-700 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
                <span>Address Match</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'address' ? 'bg-blue-900 text-blue-100' : 'bg-blue-50 text-blue-800 border border-blue-200' }}">
                    {{ number_format($counts['address'] ?? 0) }}
                </span>
            </a>

            {{-- 5. Resolved & Approved --}}
            <a href="{{ route('duplicates.index', array_merge(Arr::except($activeQuery, ['page']), ['filter' => 'resolved'])) }}" 
               class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $filter === 'resolved' ? 'bg-emerald-600 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
                <span>Resolved</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'resolved' ? 'bg-emerald-800 text-emerald-100' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">
                    {{ number_format($counts['resolved'] ?? 0) }}
                </span>
            </a>

            {{-- 6. Duplicate & Removed --}}
            <a href="{{ route('duplicates.index', array_merge(Arr::except($activeQuery, ['page']), ['filter' => 'removed'])) }}" 
               class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $filter === 'removed' ? 'bg-rose-600 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
                <span>Removed</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'removed' ? 'bg-rose-800 text-rose-100' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                    {{ number_format($counts['removed'] ?? 0) }}
                </span>
            </a>
        </div>
    </form>
</div>

{{-- Duplicate Flags List with Alpine State + Bulk Resolve --}}
<div class="space-y-4" x-data="{
    resolveModal: false,
    comparisonModal: false,
    bulkModal: false,
    activeFlag: null,
    pagePendingIds: @js($pagePendingIds ?? []),
    pageExactIds: @js($pageExactIds ?? []),
    pageHighRiskIds: @js($pageHighRiskIds ?? []),
    pageModerateIds: @js($pageModerateIds ?? []),
    pageLowRiskIds: @js($pageLowRiskIds ?? []),
    bandCounts: @js($pendingBandCounts ?? ['exact' => 0, 'high' => 0, 'moderate' => 0, 'low' => 0]),
    totalMatchingPending: {{ (int) ($pendingMatchingCount ?? 0) }},
    selectedIds: [],
    selectedBand: null,
    selectAllMatching: false,
    bulkDecision: 'resolved_not_duplicate',
    bulkConfirm: false,
    showPreviewNames: false,
    previewNamesLoading: false,
    previewNames: [],
    previewNamesTotal: 0,
    showResolveModal(flag) {
        this.activeFlag = flag;
        this.resolveModal = true;
    },
    showComparisonModal(flag) {
        this.activeFlag = flag;
        this.comparisonModal = true;
    },
    get allPageSelected() {
        return !this.selectedBand && this.pagePendingIds.length > 0 && this.pagePendingIds.every(id => this.selectedIds.includes(id));
    },
    get activeSelectedCount() {
        if (this.selectedBand) return this.bandCounts[this.selectedBand] || 0;
        return this.selectAllMatching ? this.totalMatchingPending : this.selectedIds.length;
    },
    get hasExactSelected() {
        if (this.selectedBand === 'exact') return true;
        if (this.selectedBand && this.selectedBand !== 'exact') return false;
        return this.selectedIds.some(id => this.pageExactIds.includes(id));
    },
    get needsBulkConfirmation() {
        return this.selectAllMatching || this.selectedBand !== null || this.bulkDecision === 'resolved_duplicate' || this.hasExactSelected;
    },
    get selectedBandLabel() {
        const labels = {
            exact: '100% Exact Match',
            high: '85%–99% Wrong Spelling / Human Error',
            moderate: '70%–84% Missing Suffix / Middle Name',
            low: '< 70% Below Threshold',
        };
        return this.selectedBand ? (labels[this.selectedBand] || this.selectedBand) : '';
    },
    toggleSelectPage() {
        this.selectedBand = null;
        if (this.allPageSelected) {
            this.selectedIds = [];
            this.selectAllMatching = false;
        } else {
            this.selectedIds = [...this.pagePendingIds];
            this.selectAllMatching = false;
        }
    },
    toggleFlag(id) {
        this.selectedBand = null;
        this.selectAllMatching = false;
        if (this.selectedIds.includes(id)) {
            this.selectedIds = this.selectedIds.filter(item => item !== id);
        } else {
            this.selectedIds = [...this.selectedIds, id];
        }
    },
    selectBandAll(band, pageIds) {
        this.selectedBand = band;
        this.selectAllMatching = true;
        this.selectedIds = [...pageIds];
    },
    clearSelection() {
        this.selectedBand = null;
        this.selectedIds = [];
        this.selectAllMatching = false;
    },
    openBulkModal() {
        if (this.activeSelectedCount === 0) return;
        this.bulkConfirm = false;
        this.showPreviewNames = false;
        this.previewNames = [];
        this.previewNamesTotal = 0;
        this.bulkModal = true;
    },
    async togglePreviewNames() {
        this.showPreviewNames = !this.showPreviewNames;
        if (this.showPreviewNames && this.previewNames.length === 0) {
            await this.loadPreviewNames();
        }
    },
    async loadPreviewNames() {
        this.previewNamesLoading = true;
        try {
            const params = new URLSearchParams();
            if (this.selectAllMatching) {
                params.set('select_all_matching', '1');
                const activeBand = this.selectedBand || '{{ request('risk_level') }}';
                if (activeBand) params.set('risk_level', activeBand);
                @foreach(['program', 'municipality', 'barangay', 'sex', 'filter', 'search', 'match_type'] as $fKey)
                    @if(request()->filled($fKey))
                        params.set('{{ $fKey }}', @js(request($fKey)));
                    @endif
                @endforeach
            } else {
                this.selectedIds.forEach(id => params.append('flag_ids[]', id));
            }
            const res = await fetch('{{ route('duplicates.bulk-preview') }}?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (res.ok) {
                const data = await res.json();
                this.previewNames = data.items || [];
                this.previewNamesTotal = data.total || this.previewNames.length;
            }
        } catch (err) {
            console.error('Failed to load affected names preview:', err);
        } finally {
            this.previewNamesLoading = false;
        }
    },
    getScoreBadgeClass(score) {
        if (score >= 100) return 'bg-rose-600 text-white';
        if (score >= 85) return 'bg-orange-600 text-white';
        if (score >= 70) return 'bg-amber-500 text-white';
        return 'bg-emerald-600 text-white';
    },
    getScoreTextClass(score) {
        if (score >= 100) return 'text-rose-700';
        if (score >= 85) return 'text-orange-700';
        if (score >= 70) return 'text-amber-700';
        return 'text-emerald-700';
    },
    getScoreRiskLabel(score) {
        if (score >= 100) return 'Exact Match (100%)';
        if (score >= 85) return 'Wrong Spelling / Human Error (' + score + '%)';
        if (score >= 70) return 'Missing Suffix / Middle Name (' + score + '%)';
        return 'Below Threshold (' + score + '%)';
    },
    isMatch(val1, val2) {
        if (!val1 || !val2) return false;
        return String(val1).trim().toLowerCase() === String(val2).trim().toLowerCase();
    },
    isMissing(val) {
        return !val || String(val).trim() === '' || String(val).trim() === '—';
    },
    formatPrograms(beneficiary) {
        const list = beneficiary?.beneficiary_programs || [];
        if (!list.length) return '—';
        const isTupadOnly = {{ \App\Models\Program::isTupadOnly() ? 'true' : 'false' }};
        const seen = new Set();
        const labels = [];
        list.forEach(bp => {
            const code = bp?.program?.code;
            const projName = bp?.project?.name;
            let label = '';
            if (isTupadOnly && projName) {
                label = bp.availment_year ? (projName + ' (' + bp.availment_year + ')') : projName;
            } else if (isTupadOnly && code) {
                label = 'Unassigned (' + code + (bp.availment_year ? ' ' + bp.availment_year : '') + ')';
            } else if (code) {
                label = bp.availment_year ? (code + ' (' + bp.availment_year + ')') : code;
            }
            if (label && !seen.has(label)) {
                seen.add(label);
                labels.push(label);
            }
        });
        return labels.length ? labels.join(', ') : '—';
    }
}">
    {{-- Bulk Selection & Action Toolbar --}}
    @if(!empty($pagePendingIds))
        <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-xs space-y-2.5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-800 cursor-pointer select-none">
                        <input type="checkbox"
                               :checked="allPageSelected"
                               @change="toggleSelectPage()"
                               class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600 cursor-pointer">
                        <span>Select all pending on page (<span class="tabular-nums">{{ count($pagePendingIds) }}</span>)</span>
                    </label>

                    @if(($pendingBandCounts['exact'] ?? 0) > 0)
                        <button type="button" @click="selectBandAll('exact', pageExactIds)"
                                :class="selectedBand === 'exact' ? 'ring-2 ring-rose-500 bg-rose-100' : 'bg-rose-50 hover:bg-rose-100'"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 px-2.5 py-1 text-[11px] font-extrabold text-rose-800 transition cursor-pointer">
                            <span>Select 100% Exact</span>
                            <span class="rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] text-white tabular-nums">{{ $pendingBandCounts['exact'] }}</span>
                        </button>
                    @endif

                    @if(($pendingBandCounts['high'] ?? 0) > 0)
                        <button type="button" @click="selectBandAll('high', pageHighRiskIds)"
                                :class="selectedBand === 'high' ? 'ring-2 ring-orange-500 bg-orange-100' : 'bg-orange-50 hover:bg-orange-100'"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-orange-200 px-2.5 py-1 text-[11px] font-extrabold text-orange-800 transition cursor-pointer">
                            <span>Select 85%–99%</span>
                            <span class="rounded-full bg-orange-600 px-1.5 py-0.5 text-[10px] text-white tabular-nums">{{ $pendingBandCounts['high'] }}</span>
                        </button>
                    @endif

                    @if(($pendingBandCounts['moderate'] ?? 0) > 0)
                        <button type="button" @click="selectBandAll('moderate', pageModerateIds)"
                                :class="selectedBand === 'moderate' ? 'ring-2 ring-amber-500 bg-amber-100' : 'bg-amber-50 hover:bg-amber-100'"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 px-2.5 py-1 text-[11px] font-extrabold text-amber-800 transition cursor-pointer">
                            <span>Select 70%–84%</span>
                            <span class="rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] text-white tabular-nums">{{ $pendingBandCounts['moderate'] }}</span>
                        </button>
                    @endif

                    @if(($pendingBandCounts['low'] ?? 0) > 0)
                        <button type="button" @click="selectBandAll('low', pageLowRiskIds)"
                                :class="selectedBand === 'low' ? 'ring-2 ring-emerald-500 bg-emerald-100' : 'bg-emerald-50 hover:bg-emerald-100'"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 px-2.5 py-1 text-[11px] font-extrabold text-emerald-800 transition cursor-pointer">
                            <span>Select &lt; 70%</span>
                            <span class="rounded-full bg-emerald-600 px-1.5 py-0.5 text-[10px] text-white tabular-nums">{{ $pendingBandCounts['low'] }}</span>
                        </button>
                    @endif
                </div>

                <div class="flex items-center gap-2" x-show="activeSelectedCount > 0" style="display: none;">
                    <span class="rounded-lg bg-blue-50 border border-blue-200 px-2.5 py-1 text-xs font-extrabold text-blue-900 tabular-nums">
                        <span x-text="activeSelectedCount"></span> selected
                    </span>
                    <button type="button" @click="clearSelection()"
                            class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        Clear
                    </button>
                    <button type="button" @click="openBulkModal()"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-800 px-4 py-1.5 text-xs font-extrabold text-white shadow-xs hover:bg-blue-900 transition cursor-pointer">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        <span>Bulk Resolve (<span x-text="activeSelectedCount"></span>)</span>
                    </button>
                </div>
            </div>

            {{-- Gmail-style Select All Matching Across All Pages Banner --}}
            <div x-show="allPageSelected && totalMatchingPending > pagePendingIds.length" x-transition style="display: none;"
                 class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-blue-200 bg-blue-50/70 px-3.5 py-2 text-xs text-blue-950">
                <template x-if="!selectAllMatching">
                    <div class="flex flex-wrap items-center gap-2">
                        <span>All <strong class="tabular-nums" x-text="pagePendingIds.length"></strong> pending flags on this page are selected.</span>
                        <button type="button" @click="selectAllMatching = true"
                                class="font-extrabold text-blue-800 underline hover:text-blue-950 cursor-pointer">
                            Select all <span class="tabular-nums" x-text="totalMatchingPending"></span> matching pending flags across all pages
                        </button>
                    </div>
                </template>
                <template x-if="selectAllMatching">
                    <div class="flex flex-wrap items-center gap-2">
                        <span>All <strong class="tabular-nums" x-text="totalMatchingPending"></strong> matching pending flags across all pages are selected.</span>
                        <button type="button" @click="selectAllMatching = false"
                                class="font-extrabold text-blue-800 underline hover:text-blue-950 cursor-pointer">
                            Select this page only
                        </button>
                    </div>
                </template>
            </div>
        </div>
    @endif

    @forelse($flags as $flag)
        @php
            $score = (int) $flag->match_score;
            $isHousehold = (bool) $flag->household_match_flag;

            if ($score >= 100) {
                $badgeBg = 'bg-rose-600 text-white';
                $scoreTextColor = 'text-rose-700';
                $badgeLabel = 'Exact Match';
            } elseif ($score >= 85) {
                $badgeBg = 'bg-orange-600 text-white';
                $scoreTextColor = 'text-orange-700';
                $badgeLabel = 'Wrong Spelling / Human Error';
            } elseif ($score >= 70) {
                $badgeBg = 'bg-amber-500 text-white';
                $scoreTextColor = 'text-amber-700';
                $badgeLabel = 'Missing Suffix / Middle Name';
            } else {
                $badgeBg = 'bg-emerald-600 text-white';
                $scoreTextColor = 'text-emerald-700';
                $badgeLabel = 'Below Threshold';
            }

            $isTupadOnly = \App\Models\Program::isTupadOnly();
            $candFirstName = $flag->beneficiary?->first_name ?? 'Candidate';
            $matchFirstName = $flag->matchedBeneficiary?->first_name ?? 'Masterlist';
            $candPrograms = $flag->beneficiary?->beneficiaryPrograms
                ?->map(fn ($bp) => ($isTupadOnly && $bp->project?->name)
                    ? ($bp->project->name.($bp->availment_year ? " ({$bp->availment_year})" : ''))
                    : ($isTupadOnly && $bp->program
                        ? ('Unassigned ('.$bp->program->code.($bp->availment_year ? " {$bp->availment_year}" : '').')')
                        : ($bp->program ? ($bp->program->code.($bp->availment_year ? " ({$bp->availment_year})" : '')) : null)))
                ->filter()->unique()->values() ?? collect();
            $matchPrograms = $flag->matchedBeneficiary?->beneficiaryPrograms
                ?->map(fn ($bp) => ($isTupadOnly && $bp->project?->name)
                    ? ($bp->project->name.($bp->availment_year ? " ({$bp->availment_year})" : ''))
                    : ($isTupadOnly && $bp->program
                        ? ('Unassigned ('.$bp->program->code.($bp->availment_year ? " {$bp->availment_year}" : '').')')
                        : ($bp->program ? ($bp->program->code.($bp->availment_year ? " ({$bp->availment_year})" : '')) : null)))
                ->filter()->unique()->values() ?? collect();
            $headerPrograms = $isTupadOnly
                ? collect()
                : ($flag->beneficiary?->beneficiaryPrograms?->pluck('program.code')
                    ->merge($flag->matchedBeneficiary?->beneficiaryPrograms?->pluck('program.code') ?? collect())
                    ->filter()->unique()->values() ?? collect());
        @endphp

        <div class="rounded-2xl border bg-white p-5 sm:p-6 shadow-xs transition hover:shadow-sm"
             :class="selectedIds.includes({{ $flag->id }}) ? 'border-blue-500 ring-1 ring-blue-500/20 bg-blue-50/15' : 'border-slate-200/80'">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3.5">
                    @if($flag->status === 'pending')
                        <input type="checkbox"
                               aria-label="Select flag #{{ $flag->id }}"
                               :checked="selectedIds.includes({{ $flag->id }})"
                               @change="toggleFlag({{ $flag->id }})"
                               class="h-4 w-4 shrink-0 rounded border-slate-300 text-blue-700 focus:ring-blue-600 cursor-pointer">
                    @endif
                    {{-- Match Score / Policy Badge --}}
                    <div class="flex h-13 w-13 shrink-0 flex-col items-center justify-center rounded-2xl {{ $badgeBg }} font-black shadow-2xs tabular-nums">
                        @if($isHousehold)
                            <span class="text-sm leading-none text-white">{{ $flag->match_score }}%</span>
                            <span class="text-[8px] font-bold uppercase tracking-wider opacity-90 mt-0.5 text-white/90">Family</span>
                        @else
                            <span class="text-sm leading-none">{{ $flag->match_score }}%</span>
                            <span class="text-[8px] font-bold uppercase tracking-wider opacity-90 mt-0.5">Match</span>
                        @endif
                    </div>

                    <div>
                        {{-- Comparison Names (Grammatically clean syntax) --}}
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-extrabold text-slate-900 text-sm sm:text-base">
                                {{ $flag->beneficiary?->full_name ?? ($flag->matched_fields['rejected_applicant_name'] ?? 'Candidate') }}
                            </h4>
                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-slate-100 text-slate-500 text-xs font-black shrink-0" title="Compared against">
                                ⇄
                            </span>
                            <h4 class="font-extrabold text-slate-900 text-sm sm:text-base">
                                {{ $flag->matchedBeneficiary?->full_name }}
                            </h4>
                        </div>

                        {{-- Category Pills --}}
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            @if($isHousehold)
                                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[10px] font-extrabold text-emerald-900">
                                    Household Co-Availment Review
                                </span>
                                <span class="inline-flex items-center rounded-md bg-slate-100 border border-slate-200 px-1.5 py-0.5 text-[10px] font-bold text-slate-600 tabular-nums">
                                    {{ $score }}% Name Similarity (Distinct Individuals)
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md {{ $badgeBg }} px-2 py-0.5 text-[10px] font-black uppercase tracking-wider shadow-2xs">
                                    {{ $badgeLabel }}
                                </span>
                            @endif

                            @foreach($headerPrograms as $progCode)
                                <span class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-extrabold text-blue-900 uppercase">
                                    {{ $progCode }}
                                </span>
                            @endforeach

                            <span class="text-[11px] text-slate-500 font-medium ml-1 tabular-nums">
                                Flag #{{ $flag->id }} &middot; Match Score: <span class="font-extrabold {{ $scoreTextColor }}">{{ $flag->match_score }}%</span> (<span class="uppercase font-bold text-slate-700">{{ strtoupper(str_replace('_', ' ', $flag->match_type)) }}</span>) &middot; Status: <span class="font-bold text-slate-800 capitalize">{{ ucfirst(str_replace('_', ' ', $flag->status)) }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons: Clean 2-Button Focal Hierarchy (Compare + Resolve) --}}
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <button type="button" @click="showComparisonModal({{ json_encode($flag) }})"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 hover:border-blue-600 hover:text-blue-800 transition cursor-pointer active:scale-[0.98]">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                        </svg>
                        <span>Compare</span>
                    </button>

                    @if(!$flag->beneficiary)
                        <span class="rounded-xl bg-rose-100 border border-rose-200 px-2.5 py-1 text-[11px] font-extrabold text-rose-900">
                            Candidate Removed
                        </span>
                    @endif

                    @if($flag->status === 'pending')
                        <button @click="showResolveModal({{ json_encode($flag) }})" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-800 px-4 py-1.5 text-xs font-extrabold text-white hover:bg-blue-900 shadow-2xs transition cursor-pointer active:scale-[0.98]">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span>Resolve</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Side-by-Side Quick Summary --}}
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-xl border {{ $isHousehold ? 'border-blue-200 bg-white' : 'border-amber-200 bg-white' }} p-3.5 shadow-2xs">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-[10px] font-extrabold uppercase tracking-wider {{ $isHousehold ? 'text-blue-800' : 'text-amber-800' }}">Incoming / Uploaded Record (Candidate)</p>
                        @if($flag->beneficiary)
                            <a href="{{ route('beneficiaries.show', $flag->beneficiary) }}" class="text-[10px] font-bold text-blue-700 hover:underline">View Profile &rarr;</a>
                        @else
                            <span class="rounded px-1.5 py-0.5 text-[9px] font-bold bg-slate-100 text-slate-700">NEW</span>
                        @endif
                    </div>
                    <p class="text-sm font-bold text-slate-900">
                        @if($flag->beneficiary)
                            <a href="{{ route('beneficiaries.show', $flag->beneficiary) }}" class="hover:text-blue-800 hover:underline">{{ $flag->beneficiary->full_name }}</a>
                        @else
                            {{ $flag->matched_fields['rejected_applicant_name'] ?? 'Candidate' }}
                        @endif
                    </p>
                    <p class="text-xs font-bold text-slate-700 mt-0.5">
                        {{ $isTupadOnly ? 'Project / Batch:' : 'Program:' }}
                        @if($candPrograms->isNotEmpty())
                            <span class="inline-flex rounded bg-blue-50 border border-blue-200 px-1.5 py-0.5 text-[10px] font-extrabold text-blue-900">{{ $candPrograms->implode(', ') }}</span>
                        @else
                            <span class="font-medium text-slate-400">—</span>
                        @endif
                    </p>
                    <p class="text-xs text-slate-600">DOB: {{ $flag->beneficiary?->date_of_birth?->format('M d, Y') ?? ($flag->matched_fields['rejected_applicant_dob'] ?? '—') }}</p>
                    <p class="text-xs font-bold text-slate-900">House Number / Address: <span class="text-blue-800 font-extrabold">{{ $flag->beneficiary?->address ?? ($flag->matched_fields['rejected_applicant_address'] ?? '(not specified)') }}</span></p>
                    <p class="text-xs text-slate-600">Barangay: {{ $flag->beneficiary?->barangay ?? '' }}, {{ $flag->beneficiary?->municipality ?? '' }}</p>
                    <p class="text-xs text-slate-600">Gov ID: {{ $flag->beneficiary?->government_id_number ?? ($flag->matched_fields['rejected_applicant_gov_id'] ?? '—') }}</p>
                </div>
                <div class="rounded-xl border {{ $isHousehold ? 'border-blue-200 bg-white' : 'border-amber-200 bg-white' }} p-3.5 shadow-2xs">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-[10px] font-extrabold uppercase tracking-wider {{ $isHousehold ? 'text-blue-800' : 'text-amber-800' }}">Existing Masterlist Record</p>
                        @if($flag->matchedBeneficiary)
                            <a href="{{ route('beneficiaries.show', $flag->matchedBeneficiary) }}" class="text-[10px] font-bold text-blue-700 hover:underline">View Profile &rarr;</a>
                        @else
                            <span class="rounded px-1.5 py-0.5 text-[9px] font-bold bg-slate-100 text-slate-700">EXISTING</span>
                        @endif
                    </div>
                    <p class="text-sm font-bold text-slate-900">
                        @if($flag->matchedBeneficiary)
                            <a href="{{ route('beneficiaries.show', $flag->matchedBeneficiary) }}" class="hover:text-blue-800 hover:underline">{{ $flag->matchedBeneficiary->full_name }}</a>
                        @else
                            —
                        @endif
                    </p>
                    <p class="text-xs font-bold text-slate-700 mt-0.5">
                        {{ $isTupadOnly ? 'Project / Batch:' : 'Program:' }}
                        @if($matchPrograms->isNotEmpty())
                            <span class="inline-flex rounded bg-blue-50 border border-blue-200 px-1.5 py-0.5 text-[10px] font-extrabold text-blue-900">{{ $matchPrograms->implode(', ') }}</span>
                        @else
                            <span class="font-medium text-slate-400">—</span>
                        @endif
                    </p>
                    <p class="text-xs text-slate-600">DOB: {{ $flag->matchedBeneficiary?->date_of_birth?->format('M d, Y') ?? '—' }}</p>
                    <p class="text-xs font-bold text-slate-900">House Number / Address: <span class="text-blue-800 font-extrabold">{{ $flag->matchedBeneficiary?->address ?? '(not specified)' }}</span></p>
                    <p class="text-xs text-slate-600">Barangay: {{ $flag->matchedBeneficiary?->barangay }}, {{ $flag->matchedBeneficiary?->municipality }}</p>
                    <p class="text-xs text-slate-600">Gov ID: {{ $flag->matchedBeneficiary?->government_id_number ?? '—' }}</p>
                </div>
            </div>

            {{-- Detection Details Breakdown --}}
            @if(!empty($flag->matched_fields))
                <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200 p-3.5 text-xs text-slate-700 space-y-2">
                    <p class="font-black text-slate-800 uppercase tracking-wider text-[10px]">{{ $isHousehold ? 'Household & Address Verification Insights:' : 'Detection & Audit Insights Breakdown:' }}</p>
                    <div class="space-y-1.5">
                        @foreach($flag->matched_fields as $key => $detail)
                            @if($key === 'cross_program')
                                @foreach(explode(' | ', $detail) as $conflict)
                                    <div class="flex items-start gap-2.5 rounded-lg bg-red-50/80 border border-red-200/80 p-2.5 text-xs text-red-950 font-semibold">
                                        <svg class="h-4 w-4 shrink-0 text-red-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                        <span>{{ $conflict }}</span>
                                    </div>
                                @endforeach
                            @elseif($key === 'purok_insight')
                                <div class="flex items-start gap-2.5 rounded-lg bg-blue-50/80 border border-blue-200/80 p-2.5 text-xs text-blue-950 font-semibold">
                                    <svg class="h-4 w-4 shrink-0 text-blue-700 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    <span>{{ $detail }}</span>
                                </div>
                            @elseif($key === 'same_program')
                                <div class="flex items-start gap-2.5 rounded-lg bg-amber-50/80 border border-amber-200/80 p-2.5 text-xs text-amber-950 font-semibold">
                                    <svg class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>{{ $detail }}</span>
                                </div>
                            @elseif($key === 'household_surname')
                                {{-- Surname / Family Match Icon --}}
                                <div class="flex items-start gap-2.5 rounded-lg bg-indigo-50/80 border border-indigo-200/80 p-2.5 text-xs text-indigo-950 font-semibold">
                                    <svg class="h-4 w-4 shrink-0 text-indigo-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                                    </svg>
                                    <span>{{ $detail }}</span>
                                </div>
                            @elseif($key === 'household_program')
                                {{-- Program / Cycle Year Icon --}}
                                <div class="flex items-start gap-2.5 rounded-lg bg-amber-50/80 border border-amber-200/80 p-2.5 text-xs text-amber-950 font-semibold">
                                    <svg class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                    </svg>
                                    <span>{{ $detail }}</span>
                                </div>
                            @elseif($key === 'household_address')
                                {{-- Address / Location Icon --}}
                                <div class="flex items-start gap-2.5 rounded-lg bg-blue-50/80 border border-blue-200/80 p-2.5 text-xs text-blue-950 font-semibold">
                                    <svg class="h-4 w-4 shrink-0 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                    </svg>
                                    <span>{{ $flag->getHouseholdAddressDetail() }}</span>
                                </div>
                            @elseif($key === 'household_action')
                                {{-- Verification Needed / Action Required Icon --}}
                                <div class="flex items-start gap-2.5 rounded-lg bg-rose-50/80 border border-rose-200/80 p-2.5 text-xs text-rose-950 font-bold">
                                    <svg class="h-4 w-4 shrink-0 text-rose-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                                    </svg>
                                    <span>{{ $detail }}</span>
                                </div>
                            @else
                                <div class="flex items-center gap-2 rounded-lg bg-white border border-slate-200 p-2 text-slate-800">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                    <span>{{ $detail }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            @if($flag->remarks)
                <p class="mt-3 text-xs text-slate-500 font-medium italic">
                    Resolution Remarks (by {{ $flag->reviewer?->name ?? 'System' }}): {{ $flag->remarks }}
                </p>
            @endif
        </div>
    @empty
        <div class="rounded-2xl border border-slate-200/80 bg-white p-12 text-center text-slate-400 font-bold">
            No duplicate flags found matching your criteria.
        </div>
    @endforelse

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $flags->links() }}
    </div>

    {{-- 2. Side-by-Side Duplication Comparison Modal (Panelist: Mark Ian Mukara) --}}
    <div x-show="comparisonModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm" style="display: none;">
        <div class="w-full max-w-5xl max-h-[92vh] flex flex-col rounded-3xl bg-white shadow-2xl border border-slate-200 overflow-hidden" @click.away="comparisonModal = false">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50/90 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-800 text-white shadow-xs">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">Side-by-Side Beneficiary Record Comparison</h3>
                        <p class="text-xs font-bold text-slate-500">
                            Flag #<span x-text="activeFlag?.id"></span> &middot; Match Score: <span class="font-extrabold" :class="getScoreTextClass(activeFlag?.match_score)" x-text="(activeFlag?.match_score ?? 0) + '%'"></span> (<span class="uppercase font-bold text-slate-700" x-text="activeFlag?.match_type"></span>) &middot; Status: <span class="capitalize text-slate-800" x-text="activeFlag?.status"></span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="rounded-xl px-3 py-1 text-xs font-black shadow-2xs"
                          :class="getScoreBadgeClass(activeFlag?.match_score)"
                          x-text="getScoreRiskLabel(activeFlag?.match_score)">
                    </span>
                    <button type="button" @click="comparisonModal = false" class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-200 hover:text-slate-700 transition cursor-pointer">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Color Legend Helper --}}
            <div class="border-b border-slate-200 bg-slate-100/60 px-6 py-2 flex flex-wrap items-center justify-between text-[11px] font-extrabold text-slate-600 gap-2">
                <span>Field Comparison Color Guide:</span>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 text-emerald-800">
                        <span class="h-3 w-3 rounded-md bg-emerald-100 border border-emerald-400"></span> Exact Match
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-amber-800">
                        <span class="h-3 w-3 rounded-md bg-amber-100 border border-amber-400"></span> Differing Data
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-rose-800">
                        <span class="h-3 w-3 rounded-md bg-rose-100 border border-rose-400"></span> Incomplete / Missing
                    </span>
                </div>
            </div>

            {{-- Modal Body: Comparison Table --}}
            <div class="overflow-y-auto p-6 space-y-6">
                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-xs">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-100/80 text-[11px] font-black uppercase text-slate-700">
                                <th class="w-1/4 px-4 py-3 border-r border-slate-200">Data Field</th>
                                <th class="w-3/8 px-4 py-3 border-r border-slate-200 bg-blue-50/40 text-blue-950">
                                    <div class="flex items-center justify-between">
                                        <span>Left: Existing Master Record</span>
                                        <span class="rounded bg-blue-700 px-1.5 py-0.5 text-[9px] font-black text-white">MASTER</span>
                                    </div>
                                </th>
                                <th class="w-3/8 px-4 py-3 bg-amber-50/40 text-amber-950">
                                    <div class="flex items-center justify-between">
                                        <span>Right: Newly Uploaded / Candidate</span>
                                        <span class="rounded bg-amber-600 px-1.5 py-0.5 text-[9px] font-black text-white">INCOMING</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs font-semibold">
                            {{-- DOLE Project / Program --}}
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3 font-extrabold text-slate-900 bg-slate-50/50 border-r border-slate-200">{{ \App\Models\Program::isTupadOnly() ? 'TUPAD Project / Batch' : 'DOLE Program(s)' }}</td>
                                <td class="px-4 py-3 border-r border-slate-200"
                                    :class="isMatch(formatPrograms(activeFlag?.matched_beneficiary), formatPrograms(activeFlag?.beneficiary)) && formatPrograms(activeFlag?.matched_beneficiary) !== '—' ? 'bg-emerald-50 text-emerald-950 font-extrabold' : 'bg-blue-50/40 text-blue-950 font-extrabold'">
                                    <span x-text="formatPrograms(activeFlag?.matched_beneficiary)"></span>
                                </td>
                                <td class="px-4 py-3"
                                    :class="isMatch(formatPrograms(activeFlag?.matched_beneficiary), formatPrograms(activeFlag?.beneficiary)) && formatPrograms(activeFlag?.beneficiary) !== '—' ? 'bg-emerald-50 text-emerald-950 font-extrabold' : 'bg-blue-50/40 text-blue-950 font-extrabold'">
                                    <span x-text="formatPrograms(activeFlag?.beneficiary)"></span>
                                </td>
                            </tr>

                            {{-- Full Name --}}
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3 font-extrabold text-slate-900 bg-slate-50/50 border-r border-slate-200">Full Name</td>
                                <td class="px-4 py-3 border-r border-slate-200"
                                    :class="isMatch(activeFlag?.matched_beneficiary?.full_name, (activeFlag?.beneficiary?.full_name || activeFlag?.matched_fields?.rejected_applicant_name)) ? 'bg-emerald-50 text-emerald-950 font-black' : 'bg-amber-50 text-amber-950 font-black'">
                                    <span x-text="activeFlag?.matched_beneficiary?.full_name || '—'"></span>
                                </td>
                                <td class="px-4 py-3"
                                    :class="isMatch(activeFlag?.matched_beneficiary?.full_name, (activeFlag?.beneficiary?.full_name || activeFlag?.matched_fields?.rejected_applicant_name)) ? 'bg-emerald-50 text-emerald-950 font-black' : 'bg-amber-50 text-amber-950 font-black'">
                                    <span x-text="activeFlag?.beneficiary?.full_name || (activeFlag?.matched_fields?.rejected_applicant_name || '—')"></span>
                                </td>
                            </tr>

                            {{-- First Name --}}
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-2.5 font-extrabold text-slate-800 bg-slate-50/50 border-r border-slate-200">First Name</td>
                                <td class="px-4 py-2.5 border-r border-slate-200"
                                    :class="isMatch(activeFlag?.matched_beneficiary?.first_name, activeFlag?.beneficiary?.first_name) ? 'bg-emerald-50/60 text-emerald-900' : 'text-slate-800'">
                                    <span x-text="activeFlag?.matched_beneficiary?.first_name || '—'"></span>
                                </td>
                                <td class="px-4 py-2.5"
                                    :class="isMatch(activeFlag?.matched_beneficiary?.first_name, activeFlag?.beneficiary?.first_name) ? 'bg-emerald-50/60 text-emerald-900' : 'text-slate-800'">
                                    <span x-text="activeFlag?.beneficiary?.first_name || '—'"></span>
                                </td>
                            </tr>

                            {{-- Last Name --}}
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-2.5 font-extrabold text-slate-800 bg-slate-50/50 border-r border-slate-200">Last Name / Surname</td>
                                <td class="px-4 py-2.5 border-r border-slate-200"
                                    :class="isMatch(activeFlag?.matched_beneficiary?.last_name, activeFlag?.beneficiary?.last_name) ? 'bg-emerald-50/60 text-emerald-900' : 'text-slate-800'">
                                    <span x-text="activeFlag?.matched_beneficiary?.last_name || '—'"></span>
                                </td>
                                <td class="px-4 py-2.5"
                                    :class="isMatch(activeFlag?.matched_beneficiary?.last_name, activeFlag?.beneficiary?.last_name) ? 'bg-emerald-50/60 text-emerald-900' : 'text-slate-800'">
                                    <span x-text="activeFlag?.beneficiary?.last_name || '—'"></span>
                                </td>
                            </tr>

                            {{-- Date of Birth & Age --}}
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3 font-extrabold text-slate-900 bg-slate-50/50 border-r border-slate-200">Date of Birth & Age</td>
                                <td class="px-4 py-3 border-r border-slate-200"
                                    :class="isMatch(activeFlag?.matched_beneficiary?.date_of_birth, (activeFlag?.beneficiary?.date_of_birth || activeFlag?.matched_fields?.rejected_applicant_dob)) ? 'bg-emerald-50 text-emerald-950 font-bold' : 'bg-amber-50 text-amber-950 font-bold'">
                                    <span x-text="activeFlag?.matched_beneficiary?.date_of_birth ? activeFlag?.matched_beneficiary?.date_of_birth.substring(0, 10) : '—'"></span>
                                    <span class="block text-[11px] text-slate-500" x-text="activeFlag?.matched_beneficiary?.age ? activeFlag?.matched_beneficiary?.age + ' yrs old' : ''"></span>
                                </td>
                                <td class="px-4 py-3"
                                    :class="isMatch(activeFlag?.matched_beneficiary?.date_of_birth, (activeFlag?.beneficiary?.date_of_birth || activeFlag?.matched_fields?.rejected_applicant_dob)) ? 'bg-emerald-50 text-emerald-950 font-bold' : 'bg-amber-50 text-amber-950 font-bold'">
                                    <span x-text="activeFlag?.beneficiary?.date_of_birth ? activeFlag?.beneficiary?.date_of_birth.substring(0, 10) : (activeFlag?.matched_fields?.rejected_applicant_dob || '—')"></span>
                                    <span class="block text-[11px] text-slate-500" x-text="activeFlag?.beneficiary?.age ? activeFlag?.beneficiary?.age + ' yrs old' : ''"></span>
                                </td>
                            </tr>

                            {{-- Sex & Civil Status --}}
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-2.5 font-extrabold text-slate-800 bg-slate-50/50 border-r border-slate-200">Sex / Civil Status</td>
                                <td class="px-4 py-2.5 border-r border-slate-200">
                                    <span x-text="(activeFlag?.matched_beneficiary?.sex || '—') + ' / ' + (activeFlag?.matched_beneficiary?.civil_status || '—')"></span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span x-text="(activeFlag?.beneficiary?.sex || '—') + ' / ' + (activeFlag?.beneficiary?.civil_status || '—')"></span>
                                </td>
                            </tr>

                            {{-- House Number & Address --}}
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3 font-extrabold text-slate-900 bg-slate-50/50 border-r border-slate-200">House Number / Address</td>
                                <td class="px-4 py-3 border-r border-slate-200"
                                    :class="isMissing(activeFlag?.matched_beneficiary?.address) ? 'bg-rose-50 text-rose-800' : (isMatch(activeFlag?.matched_beneficiary?.address, (activeFlag?.beneficiary?.address || activeFlag?.matched_fields?.rejected_applicant_address)) ? 'bg-emerald-50 text-emerald-900 font-bold' : 'text-slate-900 font-bold')">
                                    <span x-text="activeFlag?.matched_beneficiary?.address || '— (Incomplete / Missing House No.)'"></span>
                                </td>
                                <td class="px-4 py-3"
                                    :class="isMissing(activeFlag?.beneficiary?.address || activeFlag?.matched_fields?.rejected_applicant_address) ? 'bg-rose-50 text-rose-800' : (isMatch(activeFlag?.matched_beneficiary?.address, (activeFlag?.beneficiary?.address || activeFlag?.matched_fields?.rejected_applicant_address)) ? 'bg-emerald-50 text-emerald-900 font-bold' : 'text-slate-900 font-bold')">
                                    <span x-text="(activeFlag?.beneficiary?.address || activeFlag?.matched_fields?.rejected_applicant_address) || '— (Incomplete / Missing House No.)'"></span>
                                </td>
                            </tr>

                            {{-- Location: Barangay & Municipality --}}
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-2.5 font-extrabold text-slate-800 bg-slate-50/50 border-r border-slate-200">Barangay & Municipality</td>
                                <td class="px-4 py-2.5 border-r border-slate-200"
                                    :class="isMatch(activeFlag?.matched_beneficiary?.barangay, activeFlag?.beneficiary?.barangay) ? 'bg-emerald-50/60 font-bold text-emerald-950' : 'text-slate-900 font-bold'">
                                    <span x-text="'Brgy. ' + (activeFlag?.matched_beneficiary?.barangay || '') + ', ' + (activeFlag?.matched_beneficiary?.municipality || '')"></span>
                                </td>
                                <td class="px-4 py-2.5"
                                    :class="isMatch(activeFlag?.matched_beneficiary?.barangay, activeFlag?.beneficiary?.barangay) ? 'bg-emerald-50/60 font-bold text-emerald-950' : 'text-slate-900 font-bold'">
                                    <span x-text="'Brgy. ' + (activeFlag?.beneficiary?.barangay || '') + ', ' + (activeFlag?.beneficiary?.municipality || '')"></span>
                                </td>
                            </tr>

                            {{-- Government ID --}}
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3 font-extrabold text-slate-900 bg-slate-50/50 border-r border-slate-200">Government ID Number</td>
                                <td class="px-4 py-3 border-r border-slate-200"
                                    :class="isMatch(activeFlag?.matched_beneficiary?.government_id_number, (activeFlag?.beneficiary?.government_id_number || activeFlag?.matched_fields?.rejected_applicant_gov_id)) ? 'bg-emerald-50 text-emerald-950 font-bold' : (isMissing(activeFlag?.matched_beneficiary?.government_id_number) ? 'bg-rose-50 text-rose-800' : 'text-slate-800')">
                                    <span x-text="(activeFlag?.matched_beneficiary?.government_id_type || 'ID') + ': ' + (activeFlag?.matched_beneficiary?.government_id_number || '— (Missing)')"></span>
                                </td>
                                <td class="px-4 py-3"
                                    :class="isMatch(activeFlag?.matched_beneficiary?.government_id_number, (activeFlag?.beneficiary?.government_id_number || activeFlag?.matched_fields?.rejected_applicant_gov_id)) ? 'bg-emerald-50 text-emerald-950 font-bold' : (isMissing(activeFlag?.beneficiary?.government_id_number || activeFlag?.matched_fields?.rejected_applicant_gov_id) ? 'bg-rose-50 text-rose-800' : 'text-slate-800')">
                                    <span x-text="(activeFlag?.beneficiary?.government_id_type || 'ID') + ': ' + (activeFlag?.beneficiary?.government_id_number || (activeFlag?.matched_fields?.rejected_applicant_gov_id || '— (Missing)'))"></span>
                                </td>
                            </tr>

                            {{-- Contact Number --}}
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-2.5 font-extrabold text-slate-800 bg-slate-50/50 border-r border-slate-200">Contact Number</td>
                                <td class="px-4 py-2.5 border-r border-slate-200">
                                    <span x-text="activeFlag?.matched_beneficiary?.contact_number || '—'"></span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span x-text="activeFlag?.beneficiary?.contact_number || '—'"></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Conflict Analysis Insight --}}
                <div class="rounded-2xl border border-blue-200 bg-blue-50/50 p-4">
                    <h4 class="text-xs font-black uppercase tracking-wider text-blue-900 mb-1.5">Algorithmic Match & Field Analysis</h4>
                    <p class="text-xs text-blue-950 font-medium leading-relaxed"
                       x-text="activeFlag?.household_match_flag ? 'House Number / Household verification detection: verify if candidate and master record are distinct family units or the same household.' : 'Algorithmic cross-check matched key demographic tokens across Bukidnon Field Office beneficiary registry.'">
                    </p>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-6 py-4">
                <button type="button" @click="comparisonModal = false" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-extrabold text-slate-700 hover:bg-slate-100 transition shadow-2xs">
                    Close Comparison
                </button>
                <div class="flex items-center gap-2">
                    <template x-if="activeFlag?.status === 'pending'">
                        <button type="button" @click="comparisonModal = false; showResolveModal(activeFlag)" 
                                class="rounded-xl bg-blue-800 hover:bg-blue-900 px-5 py-2 text-xs font-extrabold text-white shadow-md transition">
                            Proceed to Resolve Flag →
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Resolve Decision Modal --}}
    <div x-show="resolveModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm" style="display: none;">
        <div class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl border border-slate-200" @click.away="resolveModal = false">
            <h3 class="mb-1 text-lg font-black text-slate-900" x-text="activeFlag?.household_match_flag ? 'Resolve House Number / Address Flag' : 'Resolve Duplicate Flag'"></h3>
            <p class="mb-4 text-xs font-semibold text-slate-500">
                Flag #<span x-text="activeFlag?.id"></span> &middot; Match Score: <span class="font-extrabold" :class="getScoreTextClass(activeFlag?.match_score)" x-text="(activeFlag?.match_score ?? 0) + '%'"></span> (<span class="uppercase font-bold text-slate-700" x-text="activeFlag?.match_type"></span>) &middot; Resolving flag for <span class="font-bold text-slate-800" x-text="activeFlag?.beneficiary?.full_name ?? ''"></span>
            </p>

            <template x-if="activeFlag?.household_match_flag">
                <div class="mb-4 rounded-xl bg-blue-50 border border-blue-200 p-3.5 text-xs text-blue-900 font-semibold leading-relaxed">
                    This is a <strong>House Number Verification</strong> flag. If verified as living in separate physical houses/units,
                    select "Approve & Keep Record" and document House Number details in Remarks.
                </div>
            </template>

            <form :action="'{{ url('duplicates') }}/' + activeFlag?.id + '/resolve'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Decision Outcome *</label>
                    <select name="status" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                        <option value="overridden">Approve & Keep Record (Override Warning)</option>
                        <option value="resolved_duplicate">Confirm True Duplicate / Same House Number (Reject)</option>
                        <option value="resolved_not_duplicate">Not a Duplicate / Distinct House Number (Verified)</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-extrabold text-slate-800">
                        Remarks / Justification * <span class="text-slate-400 font-normal" x-show="activeFlag?.household_match_flag">(e.g., "Verified separate house numbers - House 12 vs House 14")</span>
                    </label>
                    <textarea name="remarks" required rows="3" placeholder="State official validator justification for audit logs..."
                              class="w-full rounded-xl border border-slate-300 p-3 text-xs font-semibold focus:border-blue-700 focus:outline-none shadow-2xs"></textarea>
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                    <button type="button" @click="resolveModal = false" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-xl bg-blue-800 px-5 py-2 text-xs font-extrabold text-white shadow-md hover:bg-blue-900 transition cursor-pointer">
                        Submit Decision
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk Resolve Decision Modal --}}
    <div x-show="bulkModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm" style="display: none;">
        <div class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl border border-slate-200" @click.away="bulkModal = false">
            <h3 class="mb-1 text-lg font-black text-slate-900">Bulk Resolve Duplicate Flags</h3>
            <p class="mb-4 text-xs font-semibold text-slate-500">
                Applying one validator decision &amp; audit justification to
                <strong class="text-blue-900 tabular-nums" x-text="activeSelectedCount + ' pending flag(s)'"></strong>
                <span x-show="selectAllMatching" class="font-bold text-blue-800">(across all matching pages)</span>.
            </p>

            {{-- Safety Warning Banner --}}
            <div x-show="bulkDecision === 'resolved_duplicate' || hasExactSelected || selectAllMatching"
                 class="mb-4 rounded-xl border p-3 text-xs font-semibold leading-relaxed"
                 :class="(bulkDecision === 'resolved_duplicate' || hasExactSelected) ? 'border-rose-300 bg-rose-50 text-rose-900' : 'border-amber-300 bg-amber-50 text-amber-900'">
                <p class="font-extrabold mb-0.5" x-text="bulkDecision === 'resolved_duplicate' ? 'Warning: Destructive Action' : 'Bulk Verification Notice'"></p>
                <p x-show="bulkDecision === 'resolved_duplicate'">
                    Confirming true duplicates will <strong>remove all selected candidate beneficiary records</strong> from the active masterlist (recoverable via Archives).
                </p>
                <p x-show="bulkDecision !== 'resolved_duplicate' && hasExactSelected">
                    Your selection includes one or more <strong>100% Exact Match</strong> flags. Please verify carefully before approving.
                </p>
                <p x-show="bulkDecision !== 'resolved_duplicate' && !hasExactSelected && selectAllMatching">
                    This decision will apply to all <strong class="tabular-nums" x-text="activeSelectedCount"></strong> pending flags
                    <span x-show="selectedBand"> in the <strong x-text="selectedBandLabel"></strong> band</span>
                    matching your active filters across every page.
                </p>
            </div>

            {{-- Collapsible List of Affected Beneficiary Names --}}
            <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50/80 overflow-hidden">
                <button type="button" @click="togglePreviewNames()"
                        class="flex w-full items-center justify-between px-3.5 py-2.5 text-xs font-extrabold text-slate-800 hover:bg-slate-100/80 transition cursor-pointer">
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                        </svg>
                        <span>View list of affected names (<span class="tabular-nums" x-text="activeSelectedCount"></span>)</span>
                    </span>
                    <svg class="h-4 w-4 text-slate-400 transition-transform duration-150" :class="showPreviewNames ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                <div x-show="showPreviewNames" x-transition class="border-t border-slate-200 bg-white">
                    <div x-show="previewNamesLoading" class="px-3.5 py-4 text-center text-xs font-semibold text-slate-500">
                        Loading affected beneficiaries...
                    </div>
                    <div x-show="!previewNamesLoading && previewNames.length > 0" class="max-h-48 overflow-y-auto divide-y divide-slate-100">
                        <template x-for="item in previewNames" :key="item.id">
                            <div class="flex items-center justify-between gap-2 px-3.5 py-2 text-[11px]">
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-slate-900 truncate" x-text="item.candidate"></p>
                                    <p class="text-[10px] text-slate-500 truncate" x-text="'⇄ ' + item.matched"></p>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span x-show="item.programs && item.programs !== '—'"
                                          class="rounded-md border border-blue-200 bg-blue-50 px-1.5 py-0.5 text-[10px] font-extrabold text-blue-900"
                                          x-text="item.programs"></span>
                                    <span class="rounded-md px-1.5 py-0.5 text-[10px] font-extrabold tabular-nums"
                                          :class="getScoreBadgeClass(item.score)"
                                          x-text="item.score + '%'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div x-show="!previewNamesLoading && previewNamesTotal > previewNames.length"
                         class="border-t border-slate-100 bg-slate-50 px-3.5 py-1.5 text-center text-[10px] font-bold text-slate-500 tabular-nums">
                        Showing first <span x-text="previewNames.length"></span> of <span x-text="previewNamesTotal"></span> matching flags
                    </div>
                </div>
            </div>

            <form action="{{ route('duplicates.bulk-resolve') }}" method="POST" class="space-y-4">
                @csrf

                {{-- Pass selected IDs when not selecting all matching --}}
                <template x-if="!selectAllMatching">
                    <div>
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="flag_ids[]" :value="id">
                        </template>
                    </div>
                </template>
                <input type="hidden" name="select_all_matching" :value="selectAllMatching ? 1 : 0">
                <template x-if="selectedBand">
                    <input type="hidden" name="risk_level" :value="selectedBand">
                </template>

                {{-- Preserve active filter parameters so select_all_matching scopes identically --}}
                @foreach(['program', 'municipality', 'barangay', 'sex', 'filter', 'search', 'match_type'] as $filterKey)
                    @if(request()->filled($filterKey))
                        <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                    @endif
                @endforeach
                @if(request()->filled('risk_level'))
                    <template x-if="!selectedBand">
                        <input type="hidden" name="risk_level" value="{{ request('risk_level') }}">
                    </template>
                @endif

                <div>
                    <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Bulk Decision Outcome *</label>
                    <select name="status" x-model="bulkDecision" required
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                        <option value="resolved_not_duplicate">Not a Duplicate / Distinct Individuals (Approve All)</option>
                        <option value="overridden">Approve &amp; Keep Records (Override Warning)</option>
                        <option value="resolved_duplicate">Confirm True Duplicate / Same House (Remove All)</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Shared Remarks / Justification *</label>
                    <textarea name="remarks" required rows="3" placeholder="e.g., Verified distinct family members / separate house numbers in barangay validation..."
                              class="w-full rounded-xl border border-slate-300 p-3 text-xs font-semibold focus:border-blue-700 focus:outline-none shadow-2xs"></textarea>
                </div>

                {{-- Explicit Confirmation Checkbox for Cross-Page or Destructive Actions --}}
                <label x-show="needsBulkConfirmation" class="flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs font-semibold text-slate-800 cursor-pointer">
                    <input type="checkbox" name="confirm_bulk" value="1" x-model="bulkConfirm" :required="needsBulkConfirmation"
                           class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600 cursor-pointer">
                    <span>I confirm that I have reviewed this batch and want to resolve <strong class="tabular-nums" x-text="activeSelectedCount"></strong> pending flag(s).</span>
                </label>

                <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                    <button type="button" @click="bulkModal = false" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="needsBulkConfirmation && !bulkConfirm"
                            class="rounded-xl px-5 py-2 text-xs font-extrabold text-white shadow-md transition cursor-pointer disabled:opacity-50"
                            :class="bulkDecision === 'resolved_duplicate' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-blue-800 hover:bg-blue-900'">
                        Apply to <span x-text="activeSelectedCount"></span> Flag(s)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
