@extends('layouts.app')
@section('title', 'Beneficiaries')
@section('page-title', 'Beneficiary Registry')
@section('page-subtitle', 'Search, filter, and manage program beneficiaries')

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
    {{-- Actions & Filter Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            @hasanyrole('Admin|Encoder')
            <a href="{{ route('beneficiaries.create') }}" id="btn-add-beneficiary"
               class="flex items-center gap-2 rounded-xl bg-blue-800 px-4 py-2.5 text-sm font-extrabold text-white shadow-md transition hover:bg-blue-900 focus:ring-2 focus:ring-blue-500">
                <svg class="h-4 w-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Register Beneficiary
            </a>
            @endhasanyrole

            {{-- Export Buttons --}}
            <a href="{{ route('export.csv', request()->query()) }}" class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50">
                <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Export CSV
            </a>

            <a href="{{ route('export.pdf', request()->query()) }}" class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-bold text-slate-700 shadow-2xs transition hover:bg-slate-50">
                <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                Export PDF
            </a>
        </div>

        <p class="text-xs font-semibold text-slate-600">Showing <span class="font-extrabold text-slate-900">{{ $beneficiaries->total() }}</span> records</p>
    </div>

    {{-- Bulk Action Confirmation Bar --}}
    @hasanyrole('Admin|Encoder')
    <form action="{{ route('beneficiaries.bulk-delete') }}" method="POST"
          x-show="selected.length > 0"
          x-transition:enter="transition ease-out duration-200"
          x-transition:enter-start="opacity-0 -translate-y-2"
          x-transition:enter-end="opacity-100 translate-y-0"
          @submit.prevent="
              const countText = selectOverall ? 'ALL {{ $beneficiaries->total() }} matching' : selected.length;
              window.confirmAction({
                  title: 'Delete Beneficiary Records',
                  message: 'Are you sure you want to delete ' + countText + ' beneficiary record(s)? This action CANNOT be undone.',
                  confirmText: 'Yes, Delete ' + countText + ' Record(s)',
                  variant: 'danger'
              }).then(confirmed => { if (confirmed) $el.submit(); });
          "
          class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm" style="display: none;">
        @csrf

        <input type="hidden" name="delete_all_matching" :value="selectOverall ? '1' : '0'">
        <input type="hidden" name="search" value="{{ request('search') }}">
        <input type="hidden" name="program" value="{{ request('program') }}">
        <input type="hidden" name="municipality" value="{{ request('municipality') }}">

        <div class="flex flex-col sm:flex-row sm:items-center gap-3 text-xs font-extrabold text-red-900">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2.25 2.25 0 0115.938 21H8.062a2.25 2.25 0 01-2.244-2.058L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                <span x-show="!selectOverall"><span x-text="selected.length" class="text-sm font-black"></span> record(s) on this page selected.</span>
                <span x-show="selectOverall" class="text-sm font-black text-red-700">All {{ $beneficiaries->total() }} matching beneficiaries in database selected!</span>
            </div>

            @if($beneficiaries->total() > $beneficiaries->count())
            <div class="flex items-center">
                <button type="button" x-show="selectAll && !selectOverall" @click="selectOverall = true"
                        class="rounded-lg bg-red-100 hover:bg-red-200 px-3 py-1 text-xs font-extrabold text-red-900 border border-red-300 transition cursor-pointer">
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

        <button type="submit" class="rounded-xl bg-red-600 hover:bg-red-700 px-4 py-2 text-xs font-extrabold text-white shadow-sm transition cursor-pointer">
            Delete Selected Records
        </button>
    </form>
    @endhasanyrole

    {{-- Filter Card --}}
    <div class="mb-6 rounded-2xl border border-slate-200/60 bg-white p-5 shadow-xs">
        <form method="GET" action="{{ route('beneficiaries.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-800">Search Beneficiary</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or ID Number..."
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-900 placeholder:text-slate-400 focus:border-blue-700 focus:outline-none">
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-800">Program Filter</label>
                <select name="program" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                    <option value="">All Programs</option>
                    @foreach($programs as $prog)
                        <option value="{{ $prog->code }}" {{ request('program') === $prog->code ? 'selected' : '' }}>
                            {{ $prog->name }} ({{ $prog->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-800">Municipality (Bukidnon)</label>
                <select name="municipality" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                    <option value="">All Municipalities</option>
                    @foreach($municipalities as $muni)
                        <option value="{{ $muni }}" {{ request('municipality') === $muni ? 'selected' : '' }}>
                            {{ $muni }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-800">Sector / Category</label>
                <select name="sector" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                    <option value="">All Sectors</option>
                    <option value="senior" {{ request('sector') === 'senior' ? 'selected' : '' }}>Senior Citizens (60+)</option>
                    <option value="pwd" {{ request('sector') === 'pwd' ? 'selected' : '' }}>Persons with Disability (PWD)</option>
                    <option value="student" {{ request('sector') === 'student' ? 'selected' : '' }}>Students</option>
                </select>
            </div>

            <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-4">
                <button type="submit" class="rounded-xl bg-blue-800 px-5 py-2 text-xs font-extrabold text-white shadow-2xs transition hover:bg-blue-900">
                    Apply Filters
                </button>
                @if(request()->hasAny(['search', 'program', 'municipality', 'sector']))
                    <a href="{{ route('beneficiaries.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">
                        Clear Filters
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Beneficiaries Table --}}
    <div class="rounded-2xl border border-slate-200/60 bg-white shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50/80 text-xs font-bold uppercase text-slate-700">
                    <tr>
                        @hasanyrole('Admin|Encoder')
                        <th class="px-4 py-3.5 w-10 text-center font-bold">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()" title="Select All on Page"
                                   class="rounded border-slate-300 text-blue-800 focus:ring-blue-500/20 cursor-pointer">
                        </th>
                        @endhasanyrole
                        <th class="px-6 py-3.5 font-bold">Beneficiary Name</th>
                        <th class="px-6 py-3.5 font-bold">DOB / Age</th>
                        <th class="px-6 py-3.5 font-bold">Location</th>
                        <th class="px-6 py-3.5 font-bold">Gov ID / Contact</th>
                        <th class="px-6 py-3.5 font-bold">Program(s)</th>
                        <th class="px-6 py-3.5 text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs font-medium bg-white">
                    @forelse($beneficiaries as $b)
                        <tr class="transition hover:bg-slate-50" :class="selected.includes('{{ $b->id }}') ? 'bg-blue-50/50' : ''">
                            @hasanyrole('Admin|Encoder')
                            <td class="px-4 py-4 w-10 text-center">
                                <input type="checkbox" value="{{ $b->id }}" x-model="selected" @change="updateSelectAll()"
                                       class="beneficiary-cb rounded border-slate-300 text-blue-800 focus:ring-blue-500/20 cursor-pointer">
                            </td>
                            @endhasanyrole
                            <td class="px-6 py-4">
                                <a href="{{ route('beneficiaries.show', $b) }}" class="font-extrabold text-slate-900 hover:text-blue-800">
                                    {{ $b->full_name }}
                                </a>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @if($b->is_senior_citizen) <span class="inline-flex rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-900 border border-amber-200">Senior</span> @endif
                                    @if($b->is_pwd) <span class="inline-flex rounded bg-purple-100 px-2 py-0.5 text-[10px] font-bold text-purple-900 border border-purple-200">PWD</span> @endif
                                    @if($b->is_student) <span class="inline-flex rounded bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-900 border border-blue-200">Student</span> @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ $b->date_of_birth ? $b->date_of_birth->format('M d, Y') : '—' }}
                                <span class="block text-xs text-slate-500 font-semibold">{{ $b->age }} yrs old</span>
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                <span class="font-bold text-slate-900">{{ $b->municipality }}</span>
                                <span class="block text-xs text-slate-500 font-semibold">{{ $b->address ? $b->address . ', ' : '' }}Brgy. {{ $b->barangay }}</span>
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                <span class="font-semibold">{{ $b->government_id_number ?? '—' }}</span>
                                <span class="block text-xs text-slate-500 font-semibold">{{ $b->contact_number ?? 'No contact' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @forelse($b->beneficiaryPrograms as $bp)
                                        <span class="inline-flex items-center justify-center text-center whitespace-nowrap rounded-full bg-blue-100 border border-blue-200 px-3 py-1 text-xs font-extrabold text-blue-900 shadow-2xs">
                                            {{ $bp->program?->code }} ({{ $bp->availment_year }})
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400 font-semibold">None</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end items-center gap-1.5">
                                    <a href="{{ route('beneficiaries.show', $b) }}" class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100 hover:text-slate-900" title="View Details">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.573 16.49 16.638 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </a>
                                    @hasanyrole('Admin|Validator')
                                    <button type="button" @click="openMergeModal({{ json_encode($b) }})"
                                            class="rounded-lg p-1.5 text-indigo-600 hover:bg-indigo-50 hover:text-indigo-800 transition cursor-pointer"
                                            title="Merge Duplicate Record">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 004.5 9.75v7.5a2.25 2.25 0 002.25 2.25h7.5a2.25 2.25 0 002.25-2.25v-.75m-6-3l3 3m0 0l3-3m-3 3V3.75" />
                                        </svg>
                                    </button>
                                    @endhasanyrole
                                    @hasanyrole('Admin|Encoder')
                                    <a href="{{ route('beneficiaries.edit', $b) }}" class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100 hover:text-slate-900" title="Edit">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                    </a>
                                    <form action="{{ route('beneficiaries.destroy', $b) }}" method="POST" data-confirm="Are you sure you want to delete beneficiary {{ addslashes($b->full_name) }}?" data-confirm-title="Delete Beneficiary" data-confirm-btn="Yes, Delete Record" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-1.5 text-red-600 hover:bg-red-50 hover:text-red-800 transition cursor-pointer" title="Delete Beneficiary">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    </form>
                                    @endhasanyrole
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500 font-bold">
                                No beneficiaries found matching your query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="border-t border-slate-200 px-6 py-4">
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
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
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
                                Secondary (Merge & Remove)
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
                        Swap Primary & Secondary Roles
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
                        Execute Merge & Transfer History
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endhasanyrole
</div>
@endsection
