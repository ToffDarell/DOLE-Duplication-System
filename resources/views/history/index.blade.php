@extends('layouts.app')
@section('title', 'Participation History')
@section('page-title', 'Yearly Participation History')
@section('page-subtitle', 'Privacy-safe lookup showing beneficiary name, program, and year participated — sensitive personal details are hidden')

@section('content')
<div class="space-y-5">
    {{-- Privacy Notice & Filter Bar --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-4">
        <div class="flex flex-col gap-2 border-b border-slate-100 pb-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Beneficiary Participation Lookup</h3>
                <p class="text-xs text-slate-500">Verify if a beneficiary participated in a given year without displaying contact numbers, Gov IDs, or home addresses.</p>
            </div>
            <span class="inline-flex items-center gap-1.5 self-start rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-800 sm:self-auto">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                Privacy-Safe View (Names &amp; Years Only)
            </span>
        </div>

        <form method="GET" action="{{ route('history.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 {{ \App\Models\Program::isTupadOnly() ? 'lg:grid-cols-4' : 'lg:grid-cols-5' }}">
            <div class="lg:col-span-2">
                <label class="mb-1 block text-[11px] font-bold text-slate-600">Beneficiary Name</label>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Search last name or first name..."
                       class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-xs font-medium text-slate-900 focus:border-blue-700 focus:outline-none">
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-bold text-slate-600">Year Participated</label>
                <select name="year" onchange="this.form.submit()"
                        class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-800 focus:border-blue-700 focus:outline-none">
                    <option value="">All Years</option>
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ (string) $year === (string) $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            @if(!\App\Models\Program::isTupadOnly())
            <div>
                <label class="mb-1 block text-[11px] font-bold text-slate-600">Program</label>
                <select name="program" onchange="this.form.submit()"
                        class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-800 focus:border-blue-700 focus:outline-none">
                    <option value="">All Programs</option>
                    @foreach($programs as $prog)
                        <option value="{{ $prog->code }}" {{ $programCode === $prog->code ? 'selected' : '' }}>{{ $prog->code }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="flex items-end gap-2">
                <div class="flex-1 min-w-0">
                    <label class="mb-1 block text-[11px] font-bold text-slate-600">Municipality</label>
                    <select name="municipality" onchange="this.form.submit()"
                            class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-800 focus:border-blue-700 focus:outline-none">
                        <option value="">All Towns</option>
                        @foreach($municipalities as $mun)
                            <option value="{{ $mun }}" {{ $municipality === $mun ? 'selected' : '' }}>{{ $mun }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="h-10 shrink-0 rounded-xl bg-slate-900 px-4 text-xs font-bold text-white hover:bg-slate-800 transition cursor-pointer">
                    Search
                </button>
            </div>
        </form>
    </div>

    {{-- Lightweight Participation Table --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Beneficiary Name</th>
                        <th class="px-5 py-3.5">Program</th>
                        <th class="px-5 py-3.5">Year Participated</th>
                        <th class="px-5 py-3.5">Project</th>
                        <th class="px-5 py-3.5">Municipality / Barangay</th>
                        <th class="px-5 py-3.5 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white font-medium">
                    @forelse($records as $rec)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-5 py-3 font-extrabold text-slate-900">
                                {{ $rec->beneficiary?->full_name }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-md border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-extrabold text-blue-900">
                                    {{ $rec->program?->code ?? '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 font-extrabold text-slate-900 tabular-nums">
                                {{ $rec->availment_year }}
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $rec->project?->name ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $rec->beneficiary?->municipality ?? '—' }}
                                @if($rec->beneficiary?->barangay)
                                    <span class="text-slate-400">&middot; {{ $rec->beneficiary->barangay }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-extrabold uppercase
                                    {{ $rec->status === 'approved' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($rec->status === 'pending' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-700 border border-slate-200') }}">
                                    {{ $rec->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400 font-semibold">
                                No participation history records match your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
            <div class="border-t border-slate-100 bg-slate-50/50 px-6 py-3.5">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
