@extends('layouts.app')
@section('title', 'Duplicate Flags')
@section('page-title', 'Duplicate Resolution Console')
@section('page-subtitle', 'Review, compare side-by-side, and resolve flagged duplicate beneficiary records')

@section('content')
{{-- Indicator Key / Legend Card (Panelists: Mark Ian Mukara & Glaiza Mae Libe) --}}
<div class="mb-6 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
    <div class="mb-3 flex items-center justify-between border-b border-slate-100 pb-2.5">
        <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                </svg>
            </div>
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Indicator Key & Percentage Threshold Definitions</h3>
        </div>
        <span class="text-[11px] font-bold text-slate-500">DOLE Standardization Metric</span>
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

{{-- Filter Tabs & Auto Search Bar --}}
<form method="GET" action="{{ route('duplicates.index') }}" class="mb-6 flex flex-col gap-4">
    @if(request('filter'))
        <input type="hidden" name="filter" value="{{ request('filter') }}">
    @endif

    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        {{-- Match Type & Status Filter Pills (Left) --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- 1. All Flags --}}
            <a href="{{ route('duplicates.index', array_filter(['program' => request('program'), 'search' => request('search')])) }}" 
               class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition shadow-2xs {{ $filter === 'all' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50' }}">
                <span>All Flags</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'all' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                    {{ number_format($counts['all'] ?? 0) }}
                </span>
            </a>

            {{-- 2. First Name Match --}}
            <a href="{{ route('duplicates.index', array_filter(['filter' => 'first_name', 'program' => request('program'), 'search' => request('search')])) }}" 
               class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition shadow-2xs {{ $filter === 'first_name' ? 'bg-amber-600 text-white' : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50' }}">
                <span>First Name Match</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'first_name' ? 'bg-amber-800 text-amber-100' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                    {{ number_format($counts['first_name'] ?? 0) }}
                </span>
            </a>

            {{-- 3. Surname Match --}}
            <a href="{{ route('duplicates.index', array_filter(['filter' => 'surname', 'program' => request('program'), 'search' => request('search')])) }}" 
               class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition shadow-2xs {{ $filter === 'surname' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50' }}">
                <span>Surname Match</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'surname' ? 'bg-indigo-800 text-indigo-100' : 'bg-indigo-50 text-indigo-800 border border-indigo-200' }}">
                    {{ number_format($counts['surname'] ?? 0) }}
                </span>
            </a>

            {{-- 4. Address / House Number Match --}}
            <a href="{{ route('duplicates.index', array_filter(['filter' => 'address', 'program' => request('program'), 'search' => request('search')])) }}" 
               class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition shadow-2xs {{ $filter === 'address' ? 'bg-blue-700 text-white' : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50' }}">
                <span>House Number / Address Match</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'address' ? 'bg-blue-900 text-blue-100' : 'bg-blue-50 text-blue-800 border border-blue-200' }}">
                    {{ number_format($counts['address'] ?? 0) }}
                </span>
            </a>

            {{-- 5. Resolved & Approved --}}
            <a href="{{ route('duplicates.index', array_filter(['filter' => 'resolved', 'program' => request('program'), 'search' => request('search')])) }}" 
               class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition shadow-2xs {{ $filter === 'resolved' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50' }}">
                <span>Resolved & Approved</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'resolved' ? 'bg-emerald-800 text-emerald-100' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">
                    {{ number_format($counts['resolved'] ?? 0) }}
                </span>
            </a>

            {{-- 6. Duplicate & Removed --}}
            <a href="{{ route('duplicates.index', array_filter(['filter' => 'removed', 'program' => request('program'), 'search' => request('search')])) }}" 
               class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition shadow-2xs {{ $filter === 'removed' ? 'bg-rose-600 text-white' : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50' }}">
                <span>Duplicate & Removed</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $filter === 'removed' ? 'bg-rose-800 text-rose-100' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                    {{ number_format($counts['removed'] ?? 0) }}
                </span>
            </a>
        </div>

        {{-- Program Filter Dropdown & Search (Right) --}}
        <div class="flex flex-wrap items-center gap-3 shrink-0">
            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-600 uppercase tracking-wider hidden sm:inline">Program:</label>
                <select name="program" onchange="this.form.submit()"
                        class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs {{ request('program') ? 'border-blue-700 bg-blue-50/50 text-blue-900 ring-1 ring-blue-700' : '' }}">
                    <option value="">All Programs</option>
                    @foreach(['TUPAD', 'SPES', 'DILP', 'GIP'] as $prog)
                        <option value="{{ $prog }}" {{ strcasecmp(request('program'), $prog) === 0 ? 'selected' : '' }}>{{ $prog }}</option>
                    @endforeach
                </select>
            </div>

            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Live search beneficiary..."
                       class="w-full rounded-xl border border-slate-300 bg-white pl-9 pr-4 py-2 text-xs font-medium focus:border-blue-700 focus:outline-none shadow-2xs">
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </div>
        </div>
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
            if ($score >= 100) {
                $badgeBg = 'bg-red-600 text-white';
                $badgeLabel = 'Exact Duplicate';
            } elseif ($score >= 85) {
                $badgeBg = 'bg-orange-500 text-white';
                $badgeLabel = 'High Similarity Risk';
            } elseif ($score >= 70) {
                $badgeBg = 'bg-amber-500 text-white';
                $badgeLabel = 'Moderate Risk';
            } else {
                $badgeBg = 'bg-emerald-600 text-white';
                $badgeLabel = 'Low Similarity / Distinct';
            }
        @endphp

        <div class="rounded-2xl border {{ $flag->household_match_flag ? 'border-blue-200 bg-blue-50/20' : 'border-slate-200/80 bg-white' }} p-6 shadow-xs transition hover:shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-2xl {{ $badgeBg }} font-black shadow-2xs">
                        <span class="text-sm leading-none">{{ $flag->match_score }}%</span>
                        <span class="text-[9px] font-bold uppercase tracking-wider opacity-90 mt-0.5">Match</span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-extrabold text-slate-900 text-base">
                                {{ $flag->beneficiary?->full_name ?? ($flag->matched_fields['rejected_applicant_name'] ?? 'Applicant') }}
                            </h4>
                            <span class="text-xs font-bold text-slate-400">
                                {{ $flag->household_match_flag ? 'house number with' : 'matches' }}
                            </span>
                            <h4 class="font-extrabold text-slate-900 text-base">
                                {{ $flag->matchedBeneficiary?->full_name }}
                            </h4>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-md {{ $badgeBg }} px-2 py-0.5 text-[10px] font-black uppercase tracking-wider shadow-2xs">
                                {{ $badgeLabel }}
                            </span>
                            @if($flag->household_match_flag)
                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-100 border border-blue-200 px-2 py-0.5 text-[10px] font-extrabold text-blue-900">
                                    House Number Verification
                                </span>
                            @endif
                            <span class="text-xs text-slate-500 font-medium">
                                Flag #{{ $flag->id }} | Type: <span class="uppercase font-bold text-slate-800">{{ str_replace('_', ' ', $flag->match_type) }}</span> |
                                Status: <span class="font-bold text-slate-800 capitalize">{{ str_replace('_', ' ', $flag->status) }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons including View Comparison Modal Button --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Side-by-Side Comparison Trigger Button (Panelist: Mark Ian Mukara) --}}
                    <button type="button" @click="showComparisonModal({{ json_encode($flag) }})"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-blue-700 bg-blue-50 px-3.5 py-1.5 text-xs font-extrabold text-blue-800 shadow-2xs hover:bg-blue-700 hover:text-white transition cursor-pointer">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                        </svg>
                        View Comparison
                    </button>

                    @if($flag->beneficiary)
                        <a href="{{ route('beneficiaries.show', $flag->beneficiary) }}" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-2xs">
                            Profile 1
                        </a>
                    @else
                        <span class="rounded-xl bg-rose-100 border border-rose-200 px-2.5 py-1 text-[11px] font-extrabold text-rose-900">
                            Candidate Removed
                        </span>
                    @endif

                    @if($flag->matchedBeneficiary)
                        <a href="{{ route('beneficiaries.show', $flag->matchedBeneficiary) }}" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-2xs">
                            Profile 2
                        </a>
                    @endif

                    @if($flag->status === 'pending')
                        <button @click="showResolveModal({{ json_encode($flag) }})" class="rounded-xl bg-blue-800 px-4 py-1.5 text-xs font-extrabold text-white hover:bg-blue-900 shadow-2xs cursor-pointer">
                            Resolve Flag
                        </button>
                    @endif
                </div>
            </div>

            {{-- Side-by-Side Quick Summary --}}
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-xl border {{ $flag->household_match_flag ? 'border-blue-200 bg-white' : 'border-amber-200 bg-white' }} p-3.5 shadow-2xs">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-[10px] font-extrabold uppercase tracking-wider {{ $flag->household_match_flag ? 'text-blue-800' : 'text-amber-800' }}">Incoming / Uploaded Record (Candidate)</p>
                        <span class="rounded px-1.5 py-0.5 text-[9px] font-bold bg-slate-100 text-slate-700">NEW</span>
                    </div>
                    <p class="text-sm font-bold text-slate-900">{{ $flag->beneficiary?->full_name ?? ($flag->matched_fields['rejected_applicant_name'] ?? 'Candidate') }}</p>
                    <p class="text-xs text-slate-600">DOB: {{ $flag->beneficiary?->date_of_birth?->format('M d, Y') ?? ($flag->matched_fields['rejected_applicant_dob'] ?? '—') }}</p>
                    <p class="text-xs font-bold text-slate-900">House Number / Address: <span class="text-blue-800 font-extrabold">{{ $flag->beneficiary?->address ?? ($flag->matched_fields['rejected_applicant_address'] ?? '(not specified)') }}</span></p>
                    <p class="text-xs text-slate-600">Barangay: {{ $flag->beneficiary?->barangay ?? '' }}, {{ $flag->beneficiary?->municipality ?? '' }}</p>
                    <p class="text-xs text-slate-600">Gov ID: {{ $flag->beneficiary?->government_id_number ?? ($flag->matched_fields['rejected_applicant_gov_id'] ?? '—') }}</p>
                </div>
                <div class="rounded-xl border {{ $flag->household_match_flag ? 'border-blue-200 bg-white' : 'border-amber-200 bg-white' }} p-3.5 shadow-2xs">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-[10px] font-extrabold uppercase tracking-wider {{ $flag->household_match_flag ? 'text-blue-800' : 'text-amber-800' }}">Existing Masterlist Record</p>
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
                <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200 p-3 text-xs text-slate-700 space-y-1.5">
                    <p class="font-black text-slate-800 uppercase tracking-wider text-[10px]">{{ $flag->household_match_flag ? 'House Number Detection Insights:' : 'Detection & Sibling Insights Breakdown:' }}</p>
                    @foreach($flag->matched_fields as $key => $detail)
                        @if($key === 'cross_program')
                            @foreach(explode(' | ', $detail) as $conflict)
                                <p class="flex items-start gap-2 rounded-lg bg-red-50 border border-red-200 p-2 text-red-900 font-bold">
                                    <svg class="h-4 w-4 shrink-0 text-red-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                    {{ $conflict }}
                                </p>
                            @endforeach
                        @elseif($key === 'purok_insight')
                            <p class="flex items-start gap-2 rounded-lg bg-blue-50 border border-blue-200 p-2 text-blue-900 font-bold">
                                <svg class="h-4 w-4 shrink-0 text-blue-700 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                {{ $detail }}
                            </p>
                        @elseif($key === 'same_program')
                            <p class="flex items-start gap-2 rounded-lg bg-amber-50 border border-amber-200 p-2 text-amber-900 font-bold">
                                <svg class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $detail }}
                            </p>
                        @elseif(str_starts_with($key, 'household_'))
                            <p class="flex items-start gap-2 rounded-lg bg-blue-50 border border-blue-200 p-2 text-blue-900 font-bold">
                                <svg class="h-4 w-4 shrink-0 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205l3 1m1.5.5l-1.5-.5M6.75 7.364V3h-3v18m3-13.636l10.5-3.819"/></svg>
                                {{ str_replace('household', 'house number', $key === 'household_address' ? $flag->getHouseholdAddressDetail() : $detail) }}
                            </p>
                        @else
                            <p class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                {{ $detail }}
                            </p>
                        @endif
                    @endforeach
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
