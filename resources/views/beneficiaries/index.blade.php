@extends('layouts.app')
@section('title', 'Beneficiaries')
@section('page-title', 'Beneficiary Registry')
@section('page-subtitle', 'Search, filter, and manage official masterlist beneficiaries')

@section('content')
<div x-data="{
    selected: [],
    selectAll: false,
    selectOverall: false,
    mergeModal: false,
    primaryBeneficiary: null,
    secondaryBeneficiary: null,
    searchQuery: '',
    candidates: [],
    searching: false,
    submittingMerge: false,
    mergeRemarks: '',

    toggleAll() {
        if (this.selectAll) {
            this.selected = Array.from(document.querySelectorAll('.beneficiary-cb')).map(cb => cb.value);
        } else {
            this.selected = [];
            this.selectOverall = false;
        }
    },
    updateSelectAll() {
        const allBoxes = document.querySelectorAll('.beneficiary-cb');
        this.selectAll = allBoxes.length > 0 && this.selected.length === allBoxes.length;
        if (!this.selectAll) {
            this.selectOverall = false;
        }
    },
    openMergeModal(beneficiary) {
        this.primaryBeneficiary = beneficiary;
        this.secondaryBeneficiary = null;
        this.searchQuery = beneficiary.last_name || '';
        this.mergeRemarks = 'Merged duplicate record history into primary master profile';
        this.mergeModal = true;
        this.searchCandidates();
    },
    swapPrimarySecondary() {
        const temp = this.primaryBeneficiary;
        this.primaryBeneficiary = this.secondaryBeneficiary;
        this.secondaryBeneficiary = temp;
    },
    selectSecondary(candidate) {
        this.secondaryBeneficiary = candidate;
    },
    async searchCandidates() {
        if (!this.searchQuery || this.searchQuery.trim().length < 2) {
            this.candidates = [];
            return;
        }
        this.searching = true;
        try {
            const excludeId = this.primaryBeneficiary ? this.primaryBeneficiary.id : '';
            const res = await fetch(`{{ route('beneficiaries.search-candidates') }}?q=${encodeURIComponent(this.searchQuery)}&exclude_id=${excludeId}`);
            this.candidates = await res.json();
        } catch (e) {
            this.candidates = [];
        } finally {
            this.searching = false;
        }
    }
}">
    {{-- Calm, Structured Header --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-black text-slate-900 tracking-tight">Beneficiaries Masterlist</h2>
            <p class="text-xs text-slate-500 font-medium mt-0.5">
                Showing <strong class="text-slate-800 font-extrabold">{{ number_format($beneficiaries->total()) }}</strong> registered records in Bukidnon
            </p>
        </div>

        {{-- Action Buttons: Clear Hierarchy (Secondary vs Primary) --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Archives Page Link (Neutral Outline) --}}
            @php $trashCount = \App\Models\Beneficiary::onlyTrashed()->count(); @endphp
            <a href="{{ route('beneficiaries.archived') }}" 
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                </svg>
                <span>Archives</span>
                @if($trashCount > 0)
                    <span class="rounded-full bg-slate-100 border border-slate-200 px-1.5 py-0.2 text-[10px] font-bold text-slate-600">{{ $trashCount }}</span>
                @endif
            </a>

            {{-- Dedicated Reports Link (Neutral Outline) --}}
            <a href="{{ route('reports.index', request()->query()) }}" 
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
                <span>Reports</span>
            </a>

            {{-- Single Primary Action: Register Beneficiary --}}
            @hasanyrole('Admin|Encoder')
            <a href="{{ route('beneficiaries.create') }}" id="btn-add-beneficiary"
               class="inline-flex items-center gap-1.5 rounded-xl bg-blue-800 px-3.5 py-2 text-xs font-extrabold text-white shadow-2xs transition hover:bg-blue-900 focus:ring-2 focus:ring-blue-500">
                <svg class="h-4 w-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                <span>Register Beneficiary</span>
            </a>
            @endhasanyrole
        </div>
    </div>

    {{-- Bulk Archive Action Confirmation Bar --}}
    @hasanyrole('Admin|Encoder')
    <form action="{{ route('beneficiaries.bulk-delete') }}" method="POST"
          x-show="selected.length > 0"
          x-transition:enter="transition ease-out duration-200"
          x-transition:enter-start="opacity-0 -translate-y-2"
          x-transition:enter-end="opacity-100 translate-y-0"
          @submit.prevent="
              const countText = selectOverall ? 'ALL {{ $beneficiaries->total() }} matching' : selected.length;
              window.confirmAction({
                  title: 'Archive Beneficiary Records',
                  message: 'Are you sure you want to archive ' + countText + ' beneficiary record(s)? Archived records can be restored at any time from the Archives page.',
                  confirmText: 'Yes, Archive ' + countText + ' Record(s)',
                  variant: 'warning'
              }).then(confirmed => { if (confirmed) $el.submit(); });
          "
          class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200/80 bg-amber-50/70 p-3.5 shadow-2xs" style="display: none;">
        @csrf

        <input type="hidden" name="delete_all_matching" :value="selectOverall ? '1' : '0'">
        <input type="hidden" name="search" value="{{ request('search') }}">
        <input type="hidden" name="program" value="{{ request('program') }}">
        <input type="hidden" name="municipality" value="{{ request('municipality') }}">

        <div class="flex flex-col sm:flex-row sm:items-center gap-3 text-xs font-extrabold text-amber-900">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                <span x-show="!selectOverall"><span x-text="selected.length" class="text-sm font-black"></span> record(s) on this page selected for archiving.</span>
                <span x-show="selectOverall" class="text-sm font-black text-amber-700">All {{ $beneficiaries->total() }} matching beneficiaries in database selected for archiving!</span>
            </div>

            @if($beneficiaries->total() > $beneficiaries->count())
            <div class="flex items-center">
                <button type="button" x-show="selectAll && !selectOverall" @click="selectOverall = true"
                        class="rounded-lg bg-amber-100 hover:bg-amber-200 px-3 py-1 text-xs font-extrabold text-amber-900 border border-amber-300 transition cursor-pointer">
                    Select overall (All {{ $beneficiaries->total() }} beneficiaries)
                </button>
                <button type="button" x-show="selectOverall" @click="selectOverall = false"
                        class="rounded-lg bg-slate-100 hover:bg-slate-200 px-3 py-1 text-xs font-extrabold text-slate-800 border border-slate-300 transition cursor-pointer">
                    Clear overall selection
                </button>
            </div>
            @endif
        </div>

        <template x-for="id in selected" :key="id">
            <input type="hidden" name="ids[]" :value="id">
        </template>

        <button type="submit" class="rounded-xl bg-amber-600 hover:bg-amber-700 px-4 py-1.5 text-xs font-extrabold text-white shadow-2xs transition cursor-pointer">
            Archive Selected Records
        </button>
    </form>
    @endhasanyrole

    {{-- Clean, Low-Stress 2-Tier Search & Filter Toolbar --}}
    <div class="mb-5 rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-2xs flex flex-col gap-3">
        <form method="GET" action="{{ route('beneficiaries.index') }}" class="flex flex-col gap-2.5">
            {{-- Tier 1: Search Bar & Primary Selectors --}}
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2.5">
                {{-- Live Search Input (Full Flexbox, No Icon Overlap) --}}
                <div class="flex items-center rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2 shadow-2xs transition focus-within:bg-white focus-within:border-blue-700 focus-within:ring-2 focus-within:ring-blue-100 flex-1">
                    <svg class="h-4 w-4 text-slate-400 shrink-0 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, ID number, or address..."
                           class="w-full border-0 bg-transparent p-0 text-xs font-medium text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0">
                    @if(request('search'))
                        <a href="{{ route('beneficiaries.index', request()->except('search')) }}" 
                           class="ml-2 text-slate-400 hover:text-slate-600 shrink-0" title="Clear search">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                </div>

                {{-- Dropdowns Group --}}
                <div class="flex items-center gap-2 shrink-0">
                    <select name="program" onchange="this.form.submit()"
                            class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 focus:border-blue-700 focus:outline-none shadow-2xs {{ request('program') ? 'border-blue-700 text-blue-900 bg-blue-50/50 ring-1 ring-blue-700' : '' }}">
                        <option value="">All Programs</option>
                        @foreach($programs as $prog)
                            <option value="{{ $prog->code }}" {{ request('program') === $prog->code ? 'selected' : '' }}>
                                {{ $prog->code }}
                            </option>
                        @endforeach
                    </select>

                    <select name="municipality" onchange="this.form.submit()"
                            class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 focus:border-blue-700 focus:outline-none shadow-2xs {{ request('municipality') ? 'border-blue-700 text-blue-900 bg-blue-50/50 ring-1 ring-blue-700' : '' }}">
                        <option value="">All LGUs</option>
                        @foreach($municipalities as $muni)
                            <option value="{{ $muni }}" {{ request('municipality') === $muni ? 'selected' : '' }}>
                                {{ $muni }}
                            </option>
                        @endforeach
                    </select>

                    @if(request()->hasAny(['search', 'program', 'municipality', 'sector', 'has_duplicates']))
                        <a href="{{ route('beneficiaries.index') }}" 
                           class="rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition"
                           title="Reset all filters">
                            Reset
                        </a>
                    @endif
                </div>
            </div>

            {{-- Tier 2: 1-Click Quick Filters (Muted, Clean & Calm) --}}
            <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-100">
                <span class="text-[11px] font-semibold text-slate-400 mr-1">Quick Filter:</span>
                
                {{-- All Records --}}
                <a href="{{ route('beneficiaries.index', request()->except(['program', 'sector', 'has_duplicates'])) }}"
                   class="rounded-lg px-2.5 py-1 text-xs font-bold transition {{ !request('program') && !request('sector') && !request('has_duplicates') ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100' }}">
                    All Records
                </a>

                {{-- TUPAD --}}
                <a href="{{ route('beneficiaries.index', array_merge(request()->except('program'), ['program' => 'TUPAD'])) }}"
                   class="rounded-lg px-2.5 py-1 text-xs font-bold transition {{ request('program') === 'TUPAD' ? 'bg-blue-800 text-white' : 'bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100' }}">
                    TUPAD
                </a>

                {{-- SPES --}}
                <a href="{{ route('beneficiaries.index', array_merge(request()->except('program'), ['program' => 'SPES'])) }}"
                   class="rounded-lg px-2.5 py-1 text-xs font-bold transition {{ request('program') === 'SPES' ? 'bg-purple-800 text-white' : 'bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100' }}">
                    SPES
                </a>

                {{-- DILP --}}
                <a href="{{ route('beneficiaries.index', array_merge(request()->except('program'), ['program' => 'DILP'])) }}"
                   class="rounded-lg px-2.5 py-1 text-xs font-bold transition {{ request('program') === 'DILP' ? 'bg-emerald-800 text-white' : 'bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100' }}">
                    DILP
                </a>

                {{-- GIP --}}
                <a href="{{ route('beneficiaries.index', array_merge(request()->except('program'), ['program' => 'GIP'])) }}"
                   class="rounded-lg px-2.5 py-1 text-xs font-bold transition {{ request('program') === 'GIP' ? 'bg-cyan-800 text-white' : 'bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100' }}">
                    GIP
                </a>

                {{-- Senior Citizens --}}
                <a href="{{ route('beneficiaries.index', array_merge(request()->except('sector'), ['sector' => 'senior'])) }}"
                   class="rounded-lg px-2.5 py-1 text-xs font-bold transition {{ request('sector') === 'senior' ? 'bg-slate-800 text-white' : 'bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100' }}">
                    Seniors (60+)
                </a>

                {{-- PWD --}}
                <a href="{{ route('beneficiaries.index', array_merge(request()->except('sector'), ['sector' => 'pwd'])) }}"
                   class="rounded-lg px-2.5 py-1 text-xs font-bold transition {{ request('sector') === 'pwd' ? 'bg-slate-800 text-white' : 'bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100' }}">
                    PWD
                </a>

                {{-- Duplicate Warning Filter --}}
                <a href="{{ route('beneficiaries.index', array_merge(request()->except('has_duplicates'), ['has_duplicates' => 1])) }}"
                   class="rounded-lg px-2.5 py-1 text-xs font-bold transition {{ request('has_duplicates') ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' }}">
                    <svg class="inline-block h-3.5 w-3.5 -mt-0.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    Flagged Duplicates
                </a>
            </div>
        </form>
    </div>

    {{-- Beneficiaries Table: Clean, High Scannability --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[950px]">
                <thead class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        @hasanyrole('Admin|Encoder')
                        <th class="w-10 px-4 py-3.5 text-center shrink-0">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()" title="Select All on Page"
                                   class="rounded border-slate-300 text-blue-800 focus:ring-blue-500/20 cursor-pointer">
                        </th>
                        @endhasanyrole
                        <th class="px-5 py-3.5 min-w-[220px]">Beneficiary Name</th>
                        <th class="px-5 py-3.5 whitespace-nowrap min-w-[130px]">DOB &amp; Age</th>
                        <th class="px-5 py-3.5 whitespace-nowrap min-w-[160px]">Location</th>
                        <th class="px-5 py-3.5 whitespace-nowrap min-w-[160px]">Gov ID &amp; Contact</th>
                        <th class="px-5 py-3.5 whitespace-nowrap min-w-[130px]">Program(s)</th>
                        <th class="px-5 py-3.5 text-right whitespace-nowrap w-32 shrink-0">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium bg-white">
                    @forelse($beneficiaries as $b)
                        @php
                            $initials = strtoupper(substr($b->first_name, 0, 1) . substr($b->last_name, 0, 1));
                            $hasDuplicate = $b->duplicateFlags->isNotEmpty() || $b->matchedDuplicateFlags->isNotEmpty();
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/80" :class="selected.includes('{{ $b->id }}') ? 'bg-blue-50/40' : ''">
                            @hasanyrole('Admin|Encoder')
                            <td class="w-10 px-4 py-3.5 text-center shrink-0">
                                <input type="checkbox" value="{{ $b->id }}" x-model="selected" @change="updateSelectAll()"
                                       class="beneficiary-cb rounded border-slate-300 text-blue-800 focus:ring-blue-500/20 cursor-pointer">
                            </td>
                            @endhasanyrole

                            {{-- Beneficiary Name with Initials Badge --}}
                            <td class="px-5 py-3.5 min-w-[220px]">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-slate-100 border border-slate-200 text-slate-600 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ $initials }}
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('beneficiaries.show', $b) }}" class="font-bold text-slate-900 text-xs sm:text-sm hover:text-blue-800 hover:underline truncate block" title="{{ $b->full_name }}">
                                            {{ rtrim($b->full_name, " \\\t\n\r\0\x0B.") }}
                                        </a>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-1">
                                            @if($hasDuplicate)
                                                <a href="{{ route('duplicates.index', ['search' => $b->last_name]) }}" 
                                                   class="inline-flex items-center gap-1 rounded bg-amber-50 border border-amber-200 px-1.5 py-0.2 text-[10px] font-bold text-amber-800 hover:bg-amber-100 whitespace-nowrap"
                                                   title="Review duplicate flags in console">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                                    <span>Flagged Match</span>
                                                </a>
                                            @endif
                                            @if($b->is_senior_citizen) 
                                                <span class="rounded bg-slate-100 border border-slate-200/80 px-1.5 py-0.2 text-[10px] font-semibold text-slate-600 whitespace-nowrap">Senior</span> 
                                            @endif
                                            @if($b->is_pwd) 
                                                <span class="rounded bg-slate-100 border border-slate-200/80 px-1.5 py-0.2 text-[10px] font-semibold text-slate-600 whitespace-nowrap">PWD</span> 
                                            @endif
                                            @if($b->is_student) 
                                                <span class="rounded bg-slate-100 border border-slate-200/80 px-1.5 py-0.2 text-[10px] font-semibold text-slate-600 whitespace-nowrap">Student</span> 
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- DOB / Age --}}
                            <td class="px-5 py-3.5 text-slate-700 whitespace-nowrap">
                                <span class="font-semibold text-slate-800">{{ $b->date_of_birth ? $b->date_of_birth->format('M d, Y') : '—' }}</span>
                                <span class="block text-[11px] text-slate-400 font-normal mt-0.5">{{ $b->age ? $b->age . ' yrs' : '' }} &middot; {{ $b->sex }}</span>
                            </td>

                            {{-- Location --}}
                            <td class="px-5 py-3.5 text-slate-700 whitespace-nowrap">
                                <span class="font-semibold text-slate-800 block">{{ $b->municipality }}</span>
                                <span class="block text-[11px] text-slate-400 font-normal mt-0.5 truncate max-w-[200px]" title="{{ $b->address ? $b->address . ', ' : '' }}Brgy. {{ $b->barangay }}">
                                    {{ $b->address ? $b->address . ', ' : '' }}Brgy. {{ $b->barangay }}
                                </span>
                            </td>

                            {{-- Government ID & Contact --}}
                            <td class="px-5 py-3.5 text-slate-700 whitespace-nowrap">
                                <span class="font-mono text-xs font-semibold text-slate-700 block">{{ $b->government_id_number ?? '—' }}</span>
                                <span class="block text-[11px] text-slate-400 font-normal mt-0.5">{{ $b->contact_number ?? 'No contact' }}</span>
                            </td>

                            {{-- Programs Column (Program-Specific Calm Color Coding) --}}
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @forelse($b->beneficiaryPrograms as $bp)
                                        @php
                                            $progCode = $bp->program?->code;
                                            if ($progCode === 'TUPAD') {
                                                $pillStyle = 'bg-blue-50 text-blue-900 border-blue-200';
                                            } elseif ($progCode === 'SPES') {
                                                $pillStyle = 'bg-purple-50 text-purple-900 border-purple-200';
                                            } elseif ($progCode === 'DILP') {
                                                $pillStyle = 'bg-emerald-50 text-emerald-900 border-emerald-200';
                                            } else {
                                                $pillStyle = 'bg-amber-50 text-amber-900 border-amber-200';
                                            }
                                        @endphp
                                        <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[11px] font-bold shadow-2xs {{ $pillStyle }}">
                                            {{ $progCode }} <span class="ml-1 opacity-70">({{ $bp->availment_year }})</span>
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400 font-normal italic">None registered</span>
                                    @endforelse
                                </div>
                            </td>

                            {{-- Actions Column: Gentle, Quiet Icons (Fixed Width, Fully Visible) --}}
                            <td class="px-5 py-3.5 text-right whitespace-nowrap w-32 shrink-0">
                                <div class="flex justify-end items-center gap-1">
                                    {{-- View Profile --}}
                                    <a href="{{ route('beneficiaries.show', $b) }}" 
                                       class="rounded-lg p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" 
                                       title="View Profile Details">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.573 16.49 16.638 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </a>

                                    {{-- Merge Duplicate --}}
                                    @hasanyrole('Admin|Validator')
                                    <button type="button" @click="openMergeModal({{ json_encode($b) }})"
                                            class="rounded-lg p-1.5 text-slate-400 hover:text-indigo-700 hover:bg-indigo-50 transition cursor-pointer"
                                            title="Merge Duplicate Record">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 004.5 9.75v7.5a2.25 2.25 0 002.25 2.25h7.5a2.25 2.25 0 002.25-2.25v-.75m-6-3l3 3m0 0l3-3m-3 3V3.75" />
                                        </svg>
                                    </button>
                                    @endhasanyrole

                                    {{-- Edit Beneficiary --}}
                                    @hasanyrole('Admin|Encoder')
                                    <a href="{{ route('beneficiaries.edit', $b) }}" 
                                       class="rounded-lg p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" 
                                       title="Edit Beneficiary">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                    </a>

                                    {{-- Archive Beneficiary --}}
                                    <form action="{{ route('beneficiaries.destroy', $b) }}" method="POST" 
                                          data-confirm="Are you sure you want to archive beneficiary {{ addslashes($b->full_name) }}? It will be moved to Archives and can be restored at any time." 
                                          data-confirm-title="Archive Beneficiary" 
                                          data-confirm-btn="Yes, Archive Record" 
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="rounded-lg p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" 
                                                title="Archive Beneficiary">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                                        </button>
                                    </form>
                                    @endhasanyrole
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-semibold">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                    </svg>
                                    <p>No beneficiaries found matching your filter criteria.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="border-t border-slate-100 px-6 py-3.5 bg-slate-50/50">
            {{ $beneficiaries->links() }}
        </div>
    </div>

    {{-- Beneficiary Merge Modal --}}
    @hasanyrole('Admin|Validator')
    <div x-show="mergeModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 sm:p-6 backdrop-blur-md overflow-hidden"
         style="display: none;">
        
        <div x-show="mergeModal"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="w-full max-w-3xl rounded-2xl border border-indigo-200 bg-white shadow-2xl flex flex-col max-h-[90vh] overflow-hidden"
             @click.away="mergeModal = false">
            
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-200 p-5 bg-white shrink-0">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-700 text-white font-extrabold shadow-md">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 004.5 9.75v7.5a2.25 2.25 0 002.25 2.25h7.5a2.25 2.25 0 002.25-2.25v-.75m-6-3l3 3m0 0l3-3m-3 3V3.75" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Merge Duplicate Beneficiary Records</h3>
                        <p class="text-xs font-semibold text-slate-600">Consolidate program availment history and remove duplicate profile</p>
                    </div>
                </div>
                <button type="button" @click="mergeModal = false" class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="overflow-y-auto p-5 space-y-4 flex-1 min-h-0">
                <div class="rounded-xl border border-indigo-100 bg-indigo-50/70 p-3.5 text-xs font-semibold text-indigo-950 leading-relaxed">
                    Select which record is the <strong>Primary Master Profile</strong> (retained) and which is the <strong>Secondary Duplicate</strong> (its linked programs and history will transfer to Primary, then secondary will be removed).
                </div>

                {{-- Side-by-Side Comparison --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Primary Box --}}
                    <div class="rounded-xl border-2 border-emerald-500 bg-emerald-50/40 p-4 relative">
                        <div class="flex items-center justify-between mb-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-600 px-2.5 py-0.5 text-[11px] font-black text-white uppercase tracking-wider">
                                Primary (Master to Keep)
                            </span>
                        </div>
                        <template x-if="primaryBeneficiary">
                            <div class="space-y-1 text-xs">
                                <h4 class="text-sm font-black text-slate-900" x-text="primaryBeneficiary.full_name"></h4>
                                <p class="text-slate-600 font-semibold">DOB: <span x-text="primaryBeneficiary.date_of_birth ? primaryBeneficiary.date_of_birth.split('T')[0] : '—'"></span> (<span x-text="primaryBeneficiary.age"></span> yrs old)</p>
                                <p class="text-slate-600 font-semibold">Location: <span x-text="primaryBeneficiary.municipality"></span>, Brgy. <span x-text="primaryBeneficiary.barangay"></span></p>
                                <p class="text-slate-600 font-semibold">Gov ID: <span x-text="primaryBeneficiary.government_id_number || 'None'"></span></p>
                                <p class="text-slate-600 font-semibold">Contact: <span x-text="primaryBeneficiary.contact_number || 'None'"></span></p>
                            </div>
                        </template>
                        <template x-if="!primaryBeneficiary">
                            <p class="text-xs text-slate-400 font-semibold py-4 text-center">No primary beneficiary selected</p>
                        </template>
                    </div>

                    {{-- Secondary Box --}}
                    <div class="rounded-xl border-2 border-rose-400 bg-rose-50/40 p-4 relative">
                        <div class="flex items-center justify-between mb-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-600 px-2.5 py-0.5 text-[11px] font-black text-white uppercase tracking-wider">
                                Secondary (Merge &amp; Remove)
                            </span>
                        </div>
                        <template x-if="secondaryBeneficiary">
                            <div class="space-y-1 text-xs">
                                <h4 class="text-sm font-black text-slate-900" x-text="secondaryBeneficiary.full_name"></h4>
                                <p class="text-slate-600 font-semibold">DOB: <span x-text="secondaryBeneficiary.date_of_birth ? secondaryBeneficiary.date_of_birth.split('T')[0] : '—'"></span> (<span x-text="secondaryBeneficiary.age"></span> yrs old)</p>
                                <p class="text-slate-600 font-semibold">Location: <span x-text="secondaryBeneficiary.municipality"></span>, Brgy. <span x-text="secondaryBeneficiary.barangay"></span></p>
                                <p class="text-slate-600 font-semibold">Gov ID: <span x-text="secondaryBeneficiary.government_id_number || 'None'"></span></p>
                                <p class="text-slate-600 font-semibold">Contact: <span x-text="secondaryBeneficiary.contact_number || 'None'"></span></p>
                            </div>
                        </template>
                        <template x-if="!secondaryBeneficiary">
                            <p class="text-xs text-slate-500 font-semibold py-4 text-center">Search and select a secondary record below</p>
                        </template>
                    </div>
                </div>

                {{-- Swap Button --}}
                <div class="flex justify-center" x-show="primaryBeneficiary && secondaryBeneficiary">
                    <button type="button" @click="swapPrimarySecondary()"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-extrabold text-slate-700 hover:bg-slate-100 transition shadow-2xs cursor-pointer">
                        <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                        Swap Primary &amp; Secondary Roles
                    </button>
                </div>

                {{-- Candidate Search --}}
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Search Duplicate Candidate to Merge</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="searchQuery" @input.debounce.300ms="searchCandidates()"
                               placeholder="Type name (e.g., Caliao) or Gov ID..."
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-900 focus:border-indigo-600 focus:outline-none">
                        <button type="button" @click="searchCandidates()" class="rounded-xl bg-slate-800 px-4 py-2 text-xs font-bold text-white hover:bg-slate-900 transition">
                            Search
                        </button>
                    </div>

                    {{-- Candidate Results List --}}
                    <div class="mt-3 max-h-48 overflow-y-auto space-y-2 pr-1">
                        <div x-show="searching" class="text-xs font-semibold text-slate-500 text-center py-2">Searching candidates...</div>
                        <template x-for="c in candidates" :key="c.id">
                            <div class="flex items-center justify-between rounded-xl border p-3 text-xs transition"
                                 :class="secondaryBeneficiary && secondaryBeneficiary.id === c.id ? 'border-rose-400 bg-rose-50/60' : 'border-slate-200 bg-white hover:bg-slate-50'">
                                <div>
                                    <h5 class="font-black text-slate-900" x-text="c.full_name"></h5>
                                    <p class="text-slate-600">
                                        DOB: <span x-text="c.date_of_birth ? c.date_of_birth.split('T')[0] : '—'"></span> |
                                        <span x-text="c.municipality"></span>, Brgy. <span x-text="c.barangay"></span> |
                                        ID: <span x-text="c.government_id_number || 'N/A'"></span>
                                    </p>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        <template x-for="bp in c.beneficiary_programs" :key="bp.id">
                                            <span class="rounded bg-blue-100 text-blue-900 px-1.5 py-0.5 text-[10px] font-bold" x-text="(bp.program ? bp.program.code : 'Program') + ' (' + bp.availment_year + ')'"></span>
                                        </template>
                                    </div>
                                </div>
                                <button type="button" @click="selectSecondary(c)"
                                        class="rounded-lg px-3 py-1.5 text-xs font-extrabold transition cursor-pointer shadow-2xs"
                                        :class="secondaryBeneficiary && secondaryBeneficiary.id === c.id ? 'bg-rose-600 text-white' : 'bg-indigo-50 border border-indigo-300 text-indigo-800 hover:bg-indigo-100'">
                                    <span x-text="secondaryBeneficiary && secondaryBeneficiary.id === c.id ? 'Selected' : 'Select as Duplicate'"></span>
                                </button>
                            </div>
                        </template>
                        <div x-show="!searching && candidates.length === 0 && searchQuery.length >= 2" class="text-xs text-slate-400 text-center py-2">
                            No other matching candidates found.
                        </div>
                    </div>
                </div>

                {{-- Merge Remarks --}}
                <div x-show="primaryBeneficiary && secondaryBeneficiary">
                    <label class="mb-1 block text-xs font-bold text-slate-800">Merge Justification / Audit Remarks *</label>
                    <input type="text" x-model="mergeRemarks" placeholder="State reason for merging records..."
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-semibold text-slate-900 focus:border-indigo-600 focus:outline-none">
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-2.5 border-t border-slate-200 p-4 bg-slate-50 shrink-0">
                <button type="button" @click="mergeModal = false" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer">
                    Cancel
                </button>

                <form action="{{ route('beneficiaries.merge') }}" method="POST"
                      @submit="
                          if (!primaryBeneficiary || !secondaryBeneficiary) {
                              $event.preventDefault();
                              alert('Please select both a Primary Record and a Secondary Record to merge.');
                              return;
                          }
                          if (!confirm('Are you sure you want to merge ' + secondaryBeneficiary.full_name + ' into ' + primaryBeneficiary.full_name + '? This will reassign all past program history to ' + primaryBeneficiary.full_name + ' and remove the duplicate.')) {
                              $event.preventDefault();
                          }
                      ">
                    @csrf
                    <input type="hidden" name="primary_id" :value="primaryBeneficiary ? primaryBeneficiary.id : ''">
                    <input type="hidden" name="secondary_id" :value="secondaryBeneficiary ? secondaryBeneficiary.id : ''">
                    <input type="hidden" name="remarks" :value="mergeRemarks">

                    <button type="submit"
                            :disabled="!primaryBeneficiary || !secondaryBeneficiary"
                            class="rounded-xl bg-indigo-700 px-5 py-2 text-xs font-extrabold text-white shadow-md hover:bg-indigo-800 transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        Execute Merge &amp; Transfer History
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endhasanyrole
</div>
@endsection
