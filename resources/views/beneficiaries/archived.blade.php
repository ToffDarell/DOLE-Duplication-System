@extends('layouts.app')
@section('title', 'Archived Beneficiaries')
@section('page-title', 'Archived Beneficiaries & Trash Recovery')
@section('page-subtitle', 'View, restore, or permanently delete soft-deleted beneficiary masterlist records')

@section('content')
<div x-data="{
    selected: [],
    selectAll: false,
    selectOverall: false,
    toggleAll() {
        if (this.selectAll) {
            this.selected = Array.from(document.querySelectorAll('.archived-cb')).map(cb => cb.value);
        } else {
            this.selected = [];
            this.selectOverall = false;
        }
    },
    updateSelectAll() {
        const allBoxes = document.querySelectorAll('.archived-cb');
        this.selectAll = allBoxes.length > 0 && this.selected.length === allBoxes.length;
        if (!this.selectAll) {
            this.selectOverall = false;
        }
    }
}">
    {{-- Header Actions Bar --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('beneficiaries.index') }}" 
               class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-extrabold text-slate-800 shadow-2xs transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                Back to Active Registry
            </a>

            <span class="rounded-full bg-slate-100 border border-slate-200 px-3 py-1 text-xs font-bold text-slate-700">
                Archived Records: <span class="font-extrabold text-slate-900">{{ $beneficiaries->total() }}</span>
            </span>
        </div>

        <div class="rounded-xl bg-amber-50 border border-amber-200 px-3.5 py-1.5 text-xs font-bold text-amber-900 flex items-center gap-2">
            <svg class="h-4 w-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            Archived records are hidden from active registry and duplication checking until restored.
        </div>
    </div>

    {{-- Bulk Action Bar (Restore & Permanent Delete) --}}
    <div x-show="selected.length > 0"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-300 bg-white p-4 shadow-sm" style="display: none;">
        
        <div class="flex flex-wrap items-center gap-2 text-xs font-extrabold text-slate-900">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-800 font-black" x-text="selected.length"></span>
            <span>archived record(s) on this page selected:</span>

            @if($beneficiaries->total() > $beneficiaries->count())
            @hasanyrole('Admin|Encoder')
                <button type="button" x-show="selectAll && !selectOverall" @click="selectOverall = true"
                        class="rounded-lg bg-blue-100 hover:bg-blue-200 px-3 py-1 text-xs font-extrabold text-blue-900 border border-blue-300 transition cursor-pointer">
                    Select all {{ $beneficiaries->total() }} matching
                </button>
                <button type="button" x-show="selectOverall" @click="selectOverall = false"
                        class="rounded-lg bg-slate-100 hover:bg-slate-200 px-3 py-1 text-xs font-extrabold text-slate-800 border border-slate-300 transition cursor-pointer">
                    Clear overall selection
                </button>
            @endhasanyrole
            @endif

            <span x-show="selectOverall"
                  class="rounded-lg bg-amber-100 border border-amber-300 px-2 py-0.5 text-amber-900">
                Bulk actions target ALL {{ $beneficiaries->total() }} matching records
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Bulk Restore Form --}}
            @hasanyrole('Admin|Encoder')
            <form action="{{ route('beneficiaries.bulk-restore') }}" method="POST"
                  @submit.prevent="
                      const countText = selectOverall ? 'ALL {{ $beneficiaries->total() }} matching' : selected.length;
                      window.confirmAction({
                          title: 'Restore Archived Records',
                          message: 'Are you sure you want to restore ' + countText + ' archived beneficiary record(s) back to the active masterlist?',
                          confirmText: 'Yes, Restore Records',
                          variant: 'primary'
                      }).then(confirmed => { if (confirmed) $el.submit(); });
                  " class="inline-flex items-center gap-2">
                @csrf
                <input type="hidden" name="restore_all_matching" :value="selectOverall ? '1' : '0'">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="program" value="{{ request('program') }}">
                <input type="hidden" name="municipality" value="{{ request('municipality') }}">
                <template x-for="id in selected" :key="'restore-'+id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <button type="submit" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs font-extrabold text-white shadow-sm transition cursor-pointer flex items-center gap-1.5">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    <span x-show="!selectOverall">Restore Selected (<span x-text="selected.length"></span>)</span>
                    <span x-show="selectOverall">Restore ALL {{ $beneficiaries->total() }}</span>
                </button>
            </form>
            @endhasanyrole

            {{-- Bulk Permanent Delete Form --}}
            @role('Admin')
            <form action="{{ route('beneficiaries.bulk-force-delete') }}" method="POST"
                  @submit.prevent="
                      const countText = selectOverall ? 'ALL {{ $beneficiaries->total() }} matching' : selected.length;
                      window.confirmAction({
                          title: 'Permanent Delete Warning',
                          message: 'CRITICAL: This action cannot be undone. Are you sure you want to PERMANENTLY DELETE ' + countText + ' archived beneficiary record(s) and all their associated data from the database?',
                          confirmText: 'Yes, Delete Permanently',
                          variant: 'danger'
                      }).then(confirmed => { if (confirmed) $el.submit(); });
                  " class="inline-flex items-center gap-2">
                @csrf
                <input type="hidden" name="delete_all_matching" :value="selectOverall ? '1' : '0'">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="program" value="{{ request('program') }}">
                <input type="hidden" name="municipality" value="{{ request('municipality') }}">
                <template x-for="id in selected" :key="'delete-'+id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>

                <button type="submit" class="rounded-xl bg-red-600 hover:bg-red-700 px-4 py-2 text-xs font-extrabold text-white shadow-sm transition cursor-pointer flex items-center gap-1.5">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                    <span x-show="!selectOverall">Permanently Delete (<span x-text="selected.length"></span>)</span>
                    <span x-show="selectOverall">Permanently Delete ALL {{ $beneficiaries->total() }}</span>
                </button>
            </form>
            @endrole
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="mb-6 rounded-2xl border border-slate-200/60 bg-white p-5 shadow-xs">
        <form method="GET" action="{{ route('beneficiaries.archived') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-800">Search Archived Beneficiary</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, Gov ID, or Contact..."
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

            <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-3">
                <button type="submit" class="rounded-xl bg-blue-800 px-5 py-2 text-xs font-extrabold text-white shadow-2xs transition hover:bg-blue-900">
                    Apply Filter
                </button>
                @if(request()->hasAny(['search', 'program', 'municipality']))
                    <a href="{{ route('beneficiaries.archived') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">
                        Clear Filter
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Archived Beneficiaries Table --}}
    <div class="rounded-2xl border border-slate-200/60 bg-white shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50/80 text-xs font-bold uppercase text-slate-700">
                    <tr>
                        @hasanyrole('Admin|Encoder')
                        <th class="px-4 py-3.5 w-10 text-center font-bold">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()" title="Select All"
                                   class="rounded border-slate-300 text-blue-800 focus:ring-blue-500/20 cursor-pointer">
                        </th>
                        @endhasanyrole
                        <th class="px-6 py-3.5 font-bold">Beneficiary Name</th>
                        <th class="px-6 py-3.5 font-bold">DOB / Age</th>
                        <th class="px-6 py-3.5 font-bold">Location</th>
                        <th class="px-6 py-3.5 font-bold">Gov ID / Contact</th>
                        <th class="px-6 py-3.5 font-bold">Program(s)</th>
                        <th class="px-6 py-3.5 font-bold">Archived Date</th>
                        <th class="px-6 py-3.5 text-right font-bold whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs font-medium bg-white">
                    @forelse($beneficiaries as $b)
                        <tr class="transition hover:bg-slate-50" :class="selected.includes('{{ $b->id }}') ? 'bg-blue-50/40' : ''">
                            @hasanyrole('Admin|Encoder')
                            <td class="px-4 py-4 w-10 text-center">
                                <input type="checkbox" value="{{ $b->id }}" x-model="selected" @change="updateSelectAll()"
                                       class="archived-cb rounded border-slate-300 text-blue-800 focus:ring-blue-500/20 cursor-pointer">
                            </td>
                            @endhasanyrole
                            <td class="px-6 py-4">
                                <span class="font-extrabold text-slate-900 text-sm block">
                                    {{ $b->full_name }}
                                </span>
                                <span class="inline-flex rounded bg-rose-100 px-2 py-0.5 text-[10px] font-extrabold text-rose-900 border border-rose-200 mt-1">
                                    Archived
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ $b->date_of_birth ? $b->date_of_birth->format('M d, Y') : '—' }}
                                <span class="block text-xs text-slate-500 font-semibold">{{ $b->age }} yrs old ({{ $b->sex }})</span>
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
                                        <span class="inline-flex items-center justify-center text-center whitespace-nowrap rounded-full bg-slate-100 border border-slate-300 px-2.5 py-0.5 text-[11px] font-bold text-slate-700">
                                            {{ $bp->program?->code }} ({{ $bp->availment_year }})
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400 font-semibold">None</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <span class="font-bold text-slate-800">{{ $b->deleted_at?->format('M d, Y') }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $b->deleted_at?->format('h:i A') }}</span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-2 shrink-0">
                                    {{-- Restore Button --}}
                                    @hasanyrole('Admin|Encoder')
                                    <form action="{{ route('beneficiaries.restore', $b->id) }}" method="POST" 
                                          data-confirm="Are you sure you want to restore beneficiary {{ addslashes($b->full_name) }} to the active registry?" 
                                          data-confirm-title="Restore Beneficiary" 
                                          data-confirm-btn="Yes, Restore Record" class="inline-block m-0">
                                        @csrf
                                        <button type="submit" class="inline-flex h-8 items-center justify-center gap-1.5 whitespace-nowrap rounded-lg bg-emerald-50 border border-emerald-300 px-3 py-1.5 text-xs font-extrabold text-emerald-800 hover:bg-emerald-600 hover:text-white transition shadow-2xs cursor-pointer shrink-0 leading-none" title="Restore to Active Registry">
                                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                                            </svg>
                                            <span>Restore</span>
                                        </button>
                                    </form>
                                    @endhasanyrole

                                    {{-- Permanent Delete Button (Admin Only) --}}
                                    @role('Admin')
                                    <form action="{{ route('beneficiaries.force-delete', $b->id) }}" method="POST" 
                                          data-confirm="CRITICAL: This action cannot be undone. Are you sure you want to PERMANENTLY DELETE {{ addslashes($b->full_name) }} from the database?" 
                                          data-confirm-title="Permanently Delete Beneficiary" 
                                          data-confirm-btn="Yes, Delete Permanently" 
                                          data-confirm-variant="danger" class="inline-block m-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-8 items-center justify-center gap-1.5 whitespace-nowrap rounded-lg bg-red-50 border border-red-300 px-3 py-1.5 text-xs font-extrabold text-red-800 hover:bg-red-600 hover:text-white transition shadow-2xs cursor-pointer shrink-0 leading-none" title="Permanently Delete from Database">
                                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                            </svg>
                                            <span>Delete Permanently</span>
                                        </button>
                                    </form>
                                    @endrole
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500 font-bold">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-3">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-black text-slate-800">No Archived Beneficiaries Found</p>
                                    <p class="text-xs text-slate-500 font-medium mt-0.5">The trash archive is currently empty or no records match your filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($beneficiaries->hasPages())
        <div class="border-t border-slate-200 bg-slate-50/50 px-6 py-4">
            {{ $beneficiaries->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
