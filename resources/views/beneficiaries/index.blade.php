@extends('layouts.app')
@section('title', 'Beneficiaries')
@section('page-title', 'Beneficiary Registry')
@section('page-subtitle', 'Search, filter, and manage program beneficiaries')

@section('content')
<div x-data="{
    selected: [],
    selectAll: false,
    selectOverall: false,
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

        <div class="flex items-center gap-2">
            <button type="button" @click="selected = []; selectAll = false; selectOverall = false" class="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer">
                Deselect All
            </button>
            <button type="submit" class="rounded-xl bg-red-600 hover:bg-red-700 px-4 py-1.5 text-xs font-extrabold text-white shadow-sm transition cursor-pointer flex items-center gap-1.5">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                Delete <span x-text="selectOverall ? 'All {{ $beneficiaries->total() }}' : 'Selected (' + selected.length + ')'"></span>
            </button>
        </div>
    </form>
    @endhasanyrole

    {{-- Filters Card --}}
    <div class="mb-6 gov-card p-4">
        <form method="GET" action="{{ route('beneficiaries.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">Search Name/ID</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, Gov ID, Contact..."
                       class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-700 focus:outline-none">
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">Program</label>
                <select name="program" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-700 focus:outline-none">
                    <option value="">All Programs</option>
                    @foreach($programs as $p)
                        <option value="{{ $p->code }}" {{ request('program') == $p->code ? 'selected' : '' }}>{{ $p->code }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">Municipality</label>
                <select name="municipality" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-700 focus:outline-none">
                    <option value="">All Municipalities</option>
                    @foreach($municipalities as $muni)
                        <option value="{{ $muni }}" {{ request('municipality') == $muni ? 'selected' : '' }}>{{ $muni }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">Sort By</label>
                <select name="sort" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-700 focus:outline-none">
                    <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Date Added</option>
                    <option value="full_name" {{ request('sort') == 'full_name' ? 'selected' : '' }}>Full Name</option>
                    <option value="date_of_birth" {{ request('sort') == 'date_of_birth' ? 'selected' : '' }}>Date of Birth</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white transition hover:bg-slate-800 shadow-2xs cursor-pointer">
                    Filter
                </button>
                <a href="{{ route('beneficiaries.index') }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Beneficiaries Table --}}
    <div class="gov-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-700">
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
                                <span class="block text-xs text-slate-500 font-semibold">Brgy. {{ $b->barangay }}</span>
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
</div>
@endsection
