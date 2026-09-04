@extends('layouts.app')
@section('title', 'DILP Projects')
@section('page-title', 'DILP Livelihood Projects')
@section('page-subtitle', 'Track DILP individual and group livelihood projects, implementation timelines, and liquidation compliance')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <a href="{{ route('dilp.projects.create') }}" 
           class="inline-flex items-center gap-2 rounded-xl bg-blue-800 hover:bg-blue-900 px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition cursor-pointer">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span>Create DILP Project</span>
        </a>
        <span class="rounded-full bg-slate-100 border border-slate-200 px-3 py-1 text-xs font-bold text-slate-700">
            Total Projects: <span class="font-extrabold text-slate-900">{{ $projects->total() }}</span>
        </span>
    </div>
</div>

<div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs min-w-[750px]">
            <thead class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3.5 min-w-[220px]">Project Name</th>
                    <th class="px-5 py-3.5 whitespace-nowrap min-w-[180px]">Associated Group / Cooperative</th>
                    <th class="px-5 py-3.5 whitespace-nowrap min-w-[170px]">Project Timeline</th>
                    <th class="px-5 py-3.5 whitespace-nowrap min-w-[130px]">Liquidation Status</th>
                    <th class="px-5 py-3.5 text-right whitespace-nowrap min-w-[130px]">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium bg-white">
                @forelse($projects as $p)
                    @php
                        $liqStatus = strtolower($p->liquidation_status);
                        if ($liqStatus === 'liquidated') {
                            $liqBadge = 'bg-emerald-50 text-emerald-900 border-emerald-200';
                            $liqDot = 'bg-emerald-500';
                        } elseif ($liqStatus === 'partial') {
                            $liqBadge = 'bg-amber-50 text-amber-900 border-amber-200';
                            $liqDot = 'bg-amber-500';
                        } else {
                            $liqBadge = 'bg-rose-50 text-rose-900 border-rose-200';
                            $liqDot = 'bg-rose-500';
                        }
                    @endphp
                    <tr class="transition hover:bg-slate-50/80">
                        {{-- Project Name --}}
                        <td class="px-5 py-3.5 min-w-[220px]">
                            <p class="font-extrabold text-slate-900 text-xs sm:text-sm tracking-tight truncate">{{ $p->project_name }}</p>
                            <span class="text-[11px] text-slate-400 font-normal">Livelihood Project Grant</span>
                        </td>

                        {{-- Group --}}
                        <td class="px-5 py-3.5 whitespace-nowrap text-slate-700">
                            <span class="font-bold text-slate-800">{{ $p->group?->group_name ?? 'Individual Grant' }}</span>
                        </td>

                        {{-- Timeline --}}
                        <td class="px-5 py-3.5 whitespace-nowrap text-slate-600">
                            <span class="font-bold text-slate-800">{{ $p->start_date?->format('M d, Y') ?? '—' }}</span>
                            <span class="text-slate-400"> to </span>
                            <span class="font-bold text-slate-800">{{ $p->end_date?->format('M d, Y') ?? '—' }}</span>
                        </td>

                        {{-- Liquidation Status --}}
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 rounded-md border {{ $liqBadge }} px-2.5 py-0.5 text-[10px] font-extrabold uppercase shadow-2xs">
                                <span class="h-1.5 w-1.5 rounded-full {{ $liqDot }}"></span>
                                {{ $p->liquidation_status }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <a href="{{ route('dilp.projects.edit', $p) }}"
                                   class="inline-flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                                    <span>Edit</span>
                                </a>
                                <form action="{{ route('dilp.projects.destroy', $p) }}" method="POST"
                                      data-confirm="Are you sure you want to delete project {{ addslashes($p->project_name) }}?"
                                      data-confirm-title="Delete DILP Project"
                                      data-confirm-btn="Yes, Delete Project"
                                      data-confirm-variant="danger"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 rounded-xl border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100 transition shadow-2xs cursor-pointer">
                                        <span>Delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">
                            No DILP projects created yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">{{ $projects->links() }}</div>
</div>
@endsection
