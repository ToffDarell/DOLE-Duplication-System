@extends('layouts.app')
@section('title', 'Report Generator & Exports')
@section('page-title', 'Report Generation & Multi-Category Export Center')
@section('page-subtitle', 'Configure cross-category filters, preview matched masterlist records, and export official PDF, Excel, and CSV reports')

@section('content')
<div x-data="{
    municipality: '{{ request('municipality') }}',
    selectedBarangay: '{{ request('barangay') }}',
    barangays: @json($barangays),
    loadingBarangays: false,
    async fetchBarangays() {
        if (!this.municipality) {
            this.barangays = [];
            this.selectedBarangay = '';
            return;
        }
        this.loadingBarangays = true;
        try {
            const res = await fetch(`{{ route('api.barangays') }}?municipality=${encodeURIComponent(this.municipality)}`);
            this.barangays = await res.json();
        } catch (e) {
            this.barangays = [];
        } finally {
            this.loadingBarangays = false;
        }
    }
}">
    {{-- DOLE Header Banner --}}
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between rounded-2xl border border-blue-700/50 bg-blue-800 p-6 text-white shadow-lg relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 h-44 w-44 rounded-full bg-white/5 blur-3xl pointer-events-none"></div>
        <div class="flex items-center gap-4 relative z-10">
            <div class="rounded-2xl bg-white p-2 shadow-md border border-white/20 shrink-0">
                <img src="{{ asset('logo dole.png') }}" alt="DOLE Official Logo" class="h-12 w-12 object-contain bg-white">
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-white/15 border border-white/20 px-2.5 py-0.5 text-[10px] font-extrabold text-white uppercase tracking-wider">DOLE Bukidnon Field Office</span>
                    <span class="rounded-full bg-emerald-400/20 border border-emerald-300/30 px-2.5 py-0.5 text-[10px] font-extrabold text-emerald-200 uppercase tracking-wider">Official Report Engine</span>
                </div>
                <h2 class="mt-1 text-xl font-black tracking-tight text-white">Custom Beneficiary Report & Export Console</h2>
            </div>
        </div>
        <div class="mt-4 sm:mt-0 text-left sm:text-right relative z-10">
            <p class="text-[11px] font-extrabold text-blue-200 uppercase tracking-wider">Alphabetical Arrangement</p>
            <p class="text-xs font-bold text-white tracking-tight">ORDER BY Last Name ASC, First Name ASC</p>
        </div>
    </div>

    {{-- Indicator Key / Legend Card (Panelists: Mark Ian Mukara & Glaiza Mae Libe) --}}
    <div class="mb-6 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
        <div class="mb-3 flex items-center justify-between border-b border-slate-100 pb-2.5">
            <div class="flex items-center gap-2">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                    </svg>
                </div>
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Duplication Risk Level Definitions for Report Filtering</h3>
            </div>
            <span class="text-[11px] font-bold text-slate-500">Classification Scale</span>
        </div>
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

    {{-- Filter Controls Form Card (Panelist: Mark Ian Mukara) --}}
    <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">Multi-Category Report Parameters</h3>
                <p class="text-xs text-slate-500">Customize target parameters before exporting official document bundles</p>
            </div>
            <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-extrabold text-blue-800">Live Dynamic Cascade</span>
        </div>

        <form method="GET" action="{{ route('reports.index') }}" class="space-y-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- 1. Sex Filter --}}
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Sex / Gender</label>
                    <select name="sex" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                        <option value="">All (Male & Female)</option>
                        <option value="Male" {{ request('sex') === 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ request('sex') === 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>

                {{-- 2. Municipality Filter --}}
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Municipality (Bukidnon)</label>
                    <select name="municipality" x-model="municipality" @change="fetchBarangays()"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                        <option value="">All Municipalities</option>
                        @foreach($municipalities as $muni)
                            <option value="{{ $muni }}" {{ request('municipality') === $muni ? 'selected' : '' }}>
                                {{ $muni }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 3. Dependent Barangay Filter --}}
                <div>
                    <label class="mb-1.5 flex items-center justify-between text-xs font-extrabold text-slate-800">
                        <span>Barangay</span>
                        <span x-show="loadingBarangays" class="text-[10px] text-blue-700 animate-pulse font-normal">Loading...</span>
                    </label>
                    <select name="barangay" x-model="selectedBarangay"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                        <option value="">All Barangays</option>
                        <template x-for="b in barangays" :key="b">
                            <option :value="b" :selected="selectedBarangay === b" x-text="b"></option>
                        </template>
                    </select>
                </div>

                {{-- 4. Age Range Filter --}}
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Age Range</label>
                    <select name="age_range" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
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
                    <select name="risk_level" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
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
                    <select name="program" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
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
                           class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                </div>

                {{-- 8. Date Range --}}
                <div class="flex items-center gap-2">
                    <div class="w-1/2">
                        <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-2 py-2 text-xs font-semibold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                    </div>
                    <div class="w-1/2">
                        <label class="mb-1.5 block text-xs font-extrabold text-slate-800">Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-2 py-2 text-xs font-semibold text-slate-900 focus:border-blue-700 focus:outline-none shadow-2xs">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                <div class="flex items-center gap-2">
                    <button type="submit" class="rounded-xl bg-blue-800 hover:bg-blue-900 px-5 py-2.5 text-xs font-extrabold text-white shadow-md transition cursor-pointer flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/>
                        </svg>
                        Apply Filters & Preview
                    </button>
                    @if(request()->hasAny(['sex', 'municipality', 'barangay', 'age_range', 'risk_level', 'program', 'search', 'date_from', 'date_to']))
                        <a href="{{ route('reports.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                            Reset All Filters
                        </a>
                    @endif
                </div>

                {{-- Direct Export Buttons Form Action Links (Panelists: Glaiza Mae Libe & Mark Ian Mukara) --}}
                <div class="flex flex-wrap items-center gap-2.5">
                    {{-- Excel Export --}}
                    <a href="{{ route('export.excel', request()->query()) }}" 
                       class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 px-4 py-2.5 text-xs font-extrabold text-white shadow-md transition cursor-pointer">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Export Excel (.xlsx)
                    </a>

                    {{-- PDF Export --}}
                    <a href="{{ route('export.pdf', request()->query()) }}" 
                       class="inline-flex items-center gap-2 rounded-xl bg-red-700 hover:bg-red-800 px-4 py-2.5 text-xs font-extrabold text-white shadow-md transition cursor-pointer">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                        Export PDF Report
                    </a>

                    {{-- CSV Export --}}
                    <a href="{{ route('export.csv', request()->query()) }}" 
                       class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                        Export CSV
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Filter Summary KPI Cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4">
            <p class="text-xs font-bold text-blue-900 uppercase tracking-wider">Filtered Beneficiaries</p>
            <p class="text-2xl font-black text-blue-950 mt-1">{{ number_format($totalCount) }}</p>
            <p class="text-[11px] font-semibold text-blue-700 mt-0.5">Matching all active parameters</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">Sex Breakdown</p>
            <p class="text-xl font-extrabold text-slate-900 mt-1">{{ number_format($maleCount) }} Male / {{ number_format($femaleCount) }} Female</p>
            <p class="text-[11px] font-semibold text-slate-500 mt-0.5">In current filtered dataset</p>
        </div>

        <div class="rounded-2xl border border-red-200 bg-red-50/60 p-4">
            <p class="text-xs font-bold text-red-900 uppercase tracking-wider">Exact Duplicates (100%)</p>
            <p class="text-2xl font-black text-red-950 mt-1">{{ number_format($exactDuplicateCount) }}</p>
            <p class="text-[11px] font-semibold text-red-800 mt-0.5">High-priority policy collisions</p>
        </div>

        <div class="rounded-2xl border border-orange-200 bg-orange-50/60 p-4">
            <p class="text-xs font-bold text-orange-900 uppercase tracking-wider">High Risk Similarity (85–99%)</p>
            <p class="text-2xl font-black text-orange-950 mt-1">{{ number_format($highRiskCount) }}</p>
            <p class="text-[11px] font-semibold text-orange-800 mt-0.5">Phonetic & demographic warnings</p>
        </div>
    </div>

    {{-- Live Matched Records Preview Table (Alphabetical Order) --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50/80 px-6 py-4">
            <div>
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Filtered Masterlist Records Preview</h3>
                <p class="text-[11px] font-semibold text-slate-500">Sorted strictly alphabetically by Last Name, First Name (Showing up to 25 preview records)</p>
            </div>
            <span class="rounded-full bg-slate-200/80 px-3 py-1 text-xs font-black text-slate-800">
                Total Matches: {{ number_format($totalCount) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-slate-200 bg-slate-100/80 text-[11px] font-extrabold uppercase text-slate-700">
                    <tr>
                        <th class="px-5 py-3.5 w-12 text-center">#</th>
                        <th class="px-5 py-3.5 font-bold">Full Name</th>
                        <th class="px-5 py-3.5 font-bold">Date of Birth & Age</th>
                        <th class="px-5 py-3.5 font-bold">Sex</th>
                        <th class="px-5 py-3.5 font-bold">Municipality & Barangay</th>
                        <th class="px-5 py-3.5 font-bold">House Number / Address</th>
                        <th class="px-5 py-3.5 font-bold">Gov ID / Contact</th>
                        <th class="px-5 py-3.5 font-bold">Program(s)</th>
                        <th class="px-5 py-3.5 text-center font-bold">Risk Status</th>
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
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-3.5 text-center text-slate-500 font-bold">{{ $index + 1 }}</td>
                            <td class="px-5 py-3.5">
                                <a href="{{ route('beneficiaries.show', $b) }}" class="font-extrabold text-slate-900 hover:text-blue-800">
                                    {{ $b->full_name }}
                                </a>
                            </td>
                            <td class="px-5 py-3.5 text-slate-700">
                                {{ $b->date_of_birth ? $b->date_of_birth->format('M d, Y') : '—' }}
                                <span class="block text-[11px] text-slate-500 font-semibold">{{ $b->age }} yrs old</span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-700 font-semibold">{{ $b->sex }}</td>
                            <td class="px-5 py-3.5 text-slate-700">
                                <span class="font-bold text-slate-900">{{ $b->municipality }}</span>
                                <span class="block text-[11px] text-slate-500 font-semibold">Brgy. {{ $b->barangay }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-700 font-semibold">
                                {{ $b->address ?: '—' }}
                            </td>
                            <td class="px-5 py-3.5 text-slate-700">
                                <span class="font-bold text-slate-800">{{ $b->government_id_number ?: '—' }}</span>
                                <span class="block text-[11px] text-slate-500 font-semibold">{{ $b->contact_number ?: 'No contact' }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-wrap items-center gap-1">
                                    @forelse($b->beneficiaryPrograms as $bp)
                                        <span class="inline-flex rounded bg-blue-100 border border-blue-200 px-2 py-0.5 text-[10px] font-bold text-blue-900">
                                            {{ $bp->program?->code }} ({{ $bp->availment_year }})
                                        </span>
                                    @empty
                                        <span class="text-slate-400 text-xs">None</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex rounded-md {{ $rBadge }} px-2 py-0.5 text-[10px] font-black shadow-2xs">
                                    {{ $rText }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-slate-500 font-bold">
                                No beneficiary records match the specified parameter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
