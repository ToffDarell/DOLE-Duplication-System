@extends('layouts.app')
@section('title', 'Duplicate Flags')
@section('page-title', 'Duplicate Resolution Console')
@section('page-subtitle', 'Review, compare side-by-side, and resolve flagged duplicate beneficiary records')

@section('content')
{{-- Indicator Key / Legend Card (Panelists: Mark Ian Mukara & Glaiza Mae Libe) --}}
<div x-data="{ showLegend: false }" class="mb-5 rounded-2xl border border-slate-200/80 bg-white shadow-2xs overflow-hidden transition-all">
    <div class="flex items-center justify-between p-3.5 sm:px-5 cursor-pointer hover:bg-slate-50/70 select-none" @click="showLegend = !showLegend">
        <div class="flex items-center gap-2.5">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-800 shrink-0">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Indicator Key &amp; Percentage Threshold Definitions</h3>
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
                        <span class="inline-flex items-center justify-center min-w-14 px-2 py-0.5 rounded-lg bg-red-600 text-[10px] font-black text-white shadow-2xs shrink-0">100%</span>
                        <h4 class="font-extrabold text-red-950 text-xs">Exact Duplicate</h4>
                    </div>
                    <p class="text-[11px] text-red-800 leading-relaxed">Identical Full Name, Birthdate, and Government ID match.</p>
                </div>
            </div>

            {{-- High Similarity Risk (85% - 99%) --}}
            <div class="rounded-xl border border-orange-200 bg-orange-50/60 p-3 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center justify-center min-w-14 px-2 py-0.5 rounded-lg bg-orange-500 text-[10px] font-black text-white shadow-2xs shrink-0">85%–99%</span>
                        <h4 class="font-extrabold text-orange-950 text-xs">High Similarity</h4>
                    </div>
                    <p class="text-[11px] text-orange-800 leading-relaxed">Near-identical name spellings, phonetic match, or birthdates.</p>
                </div>
            </div>

            {{-- Moderate Similarity Risk (70% - 84%) --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center justify-center min-w-14 px-2 py-0.5 rounded-lg bg-amber-500 text-[10px] font-black text-white shadow-2xs shrink-0">70%–84%</span>
                        <h4 class="font-extrabold text-amber-950 text-xs">Moderate Risk</h4>
                    </div>
                    <p class="text-[11px] text-amber-800 leading-relaxed">Partial field match (e.g. same house number / address or sibling name).</p>
                </div>
            </div>

            {{-- Low Similarity / Distinct (< 70%) --}}
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center justify-center min-w-14 px-2 py-0.5 rounded-lg bg-emerald-600 text-[10px] font-black text-white shadow-2xs shrink-0">&lt; 70%</span>
                        <h4 class="font-extrabold text-emerald-950 text-xs">Low Similarity</h4>
                    </div>
                    <p class="text-[11px] text-emerald-800 leading-relaxed">Divergent record details with minor overlap; low collision risk.</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Tabs & Search Toolbar --}}
<form method="GET" action="{{ route('duplicates.index') }}" class="mb-6 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs flex flex-col gap-3.5">
    @if(request('filter'))
        <input type="hidden" name="filter" value="{{ request('filter') }}">
    @endif

    {{-- Tier 1: Search Bar & Global Program Selector --}}
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        {{-- Live Search Input (Expansive flex container) --}}
        <div class="flex items-center rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2 shadow-2xs transition focus-within:bg-white focus-within:border-blue-700 focus-within:ring-2 focus-within:ring-blue-100 flex-1 max-w-lg">
            <svg class="h-4 w-4 text-slate-400 shrink-0 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Live search by beneficiary name, ID, or address..."
                   class="w-full border-0 bg-transparent p-0 text-xs font-medium text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0">
            @if(request('search'))
                <a href="{{ route('duplicates.index', array_filter(['filter' => request('filter'), 'program' => request('program')])) }}" 
                   class="ml-2 text-slate-400 hover:text-slate-600 shrink-0" title="Clear search">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            @endif
        </div>

        {{-- Program Filter Dropdown & Reset Button --}}
        <div class="flex items-center gap-2 shrink-0">
            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Program:</label>
            <select name="program" onchange="this.form.submit()"
                    class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs {{ request('program') ? 'border-blue-700 bg-blue-50/50 text-blue-900 ring-1 ring-blue-700' : '' }}">
                <option value="">All Programs</option>
                @foreach(['TUPAD', 'SPES', 'DILP', 'GIP'] as $prog)
                    <option value="{{ $prog }}" {{ strcasecmp(request('program'), $prog) === 0 ? 'selected' : '' }}>{{ $prog }}</option>
                @endforeach
            </select>
            @if(request('program') || request('search') || request('filter'))
                <a href="{{ route('duplicates.index') }}" 
                   class="rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition"
                   title="Reset all filters">
                    Reset
                </a>
            @endif
        </div>
    </div>

    <div class="h-px bg-slate-100 w-full"></div>

    {{-- Tier 2: Category & Status Filter Pills --}}
    <div class="flex flex-wrap items-center gap-2">
        {{-- 1. All Flags --}}
        <a href="{{ route('duplicates.index', array_filter(['program' => request('program'), 'search' => request('search')])) }}" 
           class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $filter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
            <span>All Flags</span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'all' ? 'bg-slate-700 text-white' : 'bg-white text-slate-700 border border-slate-200' }}">
                {{ number_format($counts['all'] ?? 0) }}
            </span>
        </a>

        {{-- 2. First Name Match --}}
        <a href="{{ route('duplicates.index', array_filter(['filter' => 'first_name', 'program' => request('program'), 'search' => request('search')])) }}" 
           class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $filter === 'first_name' ? 'bg-amber-600 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
            <span>First Name</span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'first_name' ? 'bg-amber-800 text-amber-100' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                {{ number_format($counts['first_name'] ?? 0) }}
            </span>
        </a>

        {{-- 3. Surname Match --}}
        <a href="{{ route('duplicates.index', array_filter(['filter' => 'surname', 'program' => request('program'), 'search' => request('search')])) }}" 
           class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $filter === 'surname' ? 'bg-indigo-600 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
            <span>Surname</span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'surname' ? 'bg-indigo-800 text-indigo-100' : 'bg-indigo-50 text-indigo-800 border border-indigo-200' }}">
                {{ number_format($counts['surname'] ?? 0) }}
            </span>
        </a>

        {{-- 4. Address Match --}}
        <a href="{{ route('duplicates.index', array_filter(['filter' => 'address', 'program' => request('program'), 'search' => request('search')])) }}" 
           class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $filter === 'address' ? 'bg-blue-700 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
            <span>Address Match</span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'address' ? 'bg-blue-900 text-blue-100' : 'bg-blue-50 text-blue-800 border border-blue-200' }}">
                {{ number_format($counts['address'] ?? 0) }}
            </span>
        </a>

        {{-- 5. Resolved & Approved --}}
        <a href="{{ route('duplicates.index', array_filter(['filter' => 'resolved', 'program' => request('program'), 'search' => request('search')])) }}" 
           class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $filter === 'resolved' ? 'bg-emerald-600 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
            <span>Resolved</span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'resolved' ? 'bg-emerald-800 text-emerald-100' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">
                {{ number_format($counts['resolved'] ?? 0) }}
            </span>
        </a>

        {{-- 6. Duplicate & Removed --}}
        <a href="{{ route('duplicates.index', array_filter(['filter' => 'removed', 'program' => request('program'), 'search' => request('search')])) }}" 
           class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-bold transition shadow-2xs {{ $filter === 'removed' ? 'bg-rose-600 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
            <span>Removed</span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'removed' ? 'bg-rose-800 text-rose-100' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                {{ number_format($counts['removed'] ?? 0) }}
            </span>
        </a>
    </div>
</form>

{{-- Duplicate Flags List with Alpine State --}}
<div class="space-y-4" x-data="{
    resolveModal: false,
    comparisonModal: false,
    activeFlag: null,
    showResolveModal(flag) {
        this.activeFlag = flag;
        this.resolveModal = true;
    },
    showComparisonModal(flag) {
        this.activeFlag = flag;
        this.comparisonModal = true;
    },
    getScoreBadgeClass(score) {
        if (score >= 100) return 'bg-red-600 text-white';
        if (score >= 85) return 'bg-orange-500 text-white';
        if (score >= 70) return 'bg-amber-500 text-white';
        return 'bg-emerald-600 text-white';
    },
    getScoreRiskLabel(score) {
        if (score >= 100) return 'Exact Duplicate (100%)';
        if (score >= 85) return 'High Similarity Risk (' + score + '%)';
        if (score >= 70) return 'Moderate Risk (' + score + '%)';
        return 'Low Similarity / Distinct (' + score + '%)';
    },
    isMatch(val1, val2) {
        if (!val1 || !val2) return false;
        return String(val1).trim().toLowerCase() === String(val2).trim().toLowerCase();
    },
    isMissing(val) {
        return !val || String(val).trim() === '' || String(val).trim() === '—';
    }
}">
    @forelse($flags as $flag)
        @php
            $score = (int) $flag->match_score;
            $isHousehold = (bool) $flag->household_match_flag;

            if ($isHousehold) {
                // Household / Co-availment policy check: Distinguish policy conflict from single-person duplicate
                $badgeBg = 'bg-blue-800 text-white';
                $badgeLabel = 'Household / Family Conflict';
            } elseif ($score >= 100) {
                $badgeBg = 'bg-red-600 text-white';
                $badgeLabel = 'Exact Duplicate';
            } elseif ($score >= 85) {
                $badgeBg = 'bg-orange-500 text-white';
                $badgeLabel = 'High Similarity Risk';
            } elseif ($score >= 70) {
                $badgeBg = 'bg-amber-500 text-white';
                $badgeLabel = 'Moderate Risk';
            } else {
                $badgeBg = 'bg-slate-700 text-white';
                $badgeLabel = 'Low Similarity / Distinct';
            }

            $candFirstName = $flag->beneficiary?->first_name ?? 'Candidate';
            $matchFirstName = $flag->matchedBeneficiary?->first_name ?? 'Masterlist';
        @endphp

        <div class="rounded-2xl border {{ $isHousehold ? 'border-blue-200/90 bg-blue-50/20' : 'border-slate-200/80 bg-white' }} p-5 sm:p-6 shadow-xs transition hover:shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3.5">
                    {{-- Match Score / Policy Badge --}}
                    <div class="flex h-13 w-13 shrink-0 flex-col items-center justify-center rounded-2xl {{ $badgeBg }} font-black shadow-2xs">
                        @if($isHousehold)
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                            </svg>
                            <span class="text-[8px] font-bold uppercase tracking-wider opacity-90 mt-0.5">Family</span>
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
                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-100 border border-blue-200 px-2 py-0.5 text-[10px] font-extrabold text-blue-900">
                                    Household Co-Availment Review
                                </span>
                                <span class="inline-flex items-center rounded-md bg-slate-100 border border-slate-200 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">
                                    {{ $score }}% Name Similarity (Distinct Individuals)
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md {{ $badgeBg }} px-2 py-0.5 text-[10px] font-black uppercase tracking-wider shadow-2xs">
                                    {{ $badgeLabel }}
                                </span>
                            @endif

                            <span class="text-[11px] text-slate-500 font-medium ml-1">
                                Flag #{{ $flag->id }} &middot; Type: <span class="uppercase font-bold text-slate-700">{{ str_replace('_', ' ', $flag->match_type) }}</span> &middot;
                                Status: <span class="font-bold text-slate-800 capitalize">{{ str_replace('_', ' ', $flag->status) }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons: Contextual Names (No mystery Profile 1 / Profile 2) --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Side-by-Side Comparison Trigger Button --}}
                    <button type="button" @click="showComparisonModal({{ json_encode($flag) }})"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-blue-700 bg-blue-50 px-3 py-1.5 text-xs font-extrabold text-blue-800 shadow-2xs hover:bg-blue-700 hover:text-white transition cursor-pointer">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                        </svg>
                        <span>Compare</span>
                    </button>

                    @if($flag->beneficiary)
                        <a href="{{ route('beneficiaries.show', $flag->beneficiary) }}" 
                           class="inline-flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-2xs"
                           title="View {{ $flag->beneficiary->full_name }}'s profile">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            <span>Profile: {{ $candFirstName }}</span>
                        </a>
                    @else
                        <span class="rounded-xl bg-rose-100 border border-rose-200 px-2.5 py-1 text-[11px] font-extrabold text-rose-900">
                            Candidate Removed
                        </span>
                    @endif

                    @if($flag->matchedBeneficiary)
                        <a href="{{ route('beneficiaries.show', $flag->matchedBeneficiary) }}" 
                           class="inline-flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-2xs"
                           title="View {{ $flag->matchedBeneficiary->full_name }}'s profile">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            <span>Profile: {{ $matchFirstName }}</span>
                        </a>
                    @endif

                    @if($flag->status === 'pending')
                        <button @click="showResolveModal({{ json_encode($flag) }})" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-800 px-3.5 py-1.5 text-xs font-extrabold text-white hover:bg-blue-900 shadow-2xs cursor-pointer">
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
                        <span class="rounded px-1.5 py-0.5 text-[9px] font-bold bg-slate-100 text-slate-700">NEW</span>
                    </div>
                    <p class="text-sm font-bold text-slate-900">{{ $flag->beneficiary?->full_name ?? ($flag->matched_fields['rejected_applicant_name'] ?? 'Candidate') }}</p>
                    <p class="text-xs text-slate-600">DOB: {{ $flag->beneficiary?->date_of_birth?->format('M d, Y') ?? ($flag->matched_fields['rejected_applicant_dob'] ?? '—') }}</p>
                    <p class="text-xs font-bold text-slate-900">House Number / Address: <span class="text-blue-800 font-extrabold">{{ $flag->beneficiary?->address ?? ($flag->matched_fields['rejected_applicant_address'] ?? '(not specified)') }}</span></p>
                    <p class="text-xs text-slate-600">Barangay: {{ $flag->beneficiary?->barangay ?? '' }}, {{ $flag->beneficiary?->municipality ?? '' }}</p>
                    <p class="text-xs text-slate-600">Gov ID: {{ $flag->beneficiary?->government_id_number ?? ($flag->matched_fields['rejected_applicant_gov_id'] ?? '—') }}</p>
                </div>
                <div class="rounded-xl border {{ $isHousehold ? 'border-blue-200 bg-white' : 'border-amber-200 bg-white' }} p-3.5 shadow-2xs">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-[10px] font-extrabold uppercase tracking-wider {{ $isHousehold ? 'text-blue-800' : 'text-amber-800' }}">Existing Masterlist Record</p>
                        <span class="rounded px-1.5 py-0.5 text-[9px] font-bold bg-slate-100 text-slate-700">EXISTING</span>
                    </div>
                    <p class="text-sm font-bold text-slate-900">{{ $flag->matchedBeneficiary?->full_name }}</p>
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
                            Flag ID: #<span x-text="activeFlag?.id"></span> | Match Type: <span class="uppercase text-blue-700" x-text="activeFlag?.match_type"></span>
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
            <p class="mb-4 text-xs font-semibold text-slate-500" x-text="'Resolving flag for ' + (activeFlag?.beneficiary?.full_name ?? '')"></p>

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
</div>
@endsection
