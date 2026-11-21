@extends('layouts.app')
@section('title', 'DILP Projects')
@section('page-title', 'DILP Livelihood Projects')
@section('page-subtitle', 'Track Group (Association) and Individual DILP livelihood grants, Amount Grant, DA / ACP mode, and liquidation status')

@section('content')
{{-- Top Category Tabs (Group vs Individual) + Create Action --}}
<div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('dilp.projects.index', array_filter(['search' => request('search'), 'implementation_mode' => request('implementation_mode'), 'liquidation_status' => request('liquidation_status')])) }}"
           class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition {{ $activeType === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
            <span>All DILP</span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-extrabold tabular-nums {{ $activeType === 'all' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-700' }}">{{ number_format($counts['all']) }}</span>
        </a>
        <a href="{{ route('dilp.projects.index', array_filter(['type' => 'group', 'search' => request('search'), 'implementation_mode' => request('implementation_mode'), 'liquidation_status' => request('liquidation_status')])) }}"
           class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition {{ $activeType === 'group' ? 'bg-blue-800 text-white shadow-xs' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
            <span>Group Projects</span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-extrabold tabular-nums {{ $activeType === 'group' ? 'bg-blue-700 text-white' : 'bg-blue-50 text-blue-800' }}">{{ number_format($counts['group']) }}</span>
        </a>
        <a href="{{ route('dilp.projects.index', array_filter(['type' => 'individual', 'search' => request('search'), 'implementation_mode' => request('implementation_mode'), 'liquidation_status' => request('liquidation_status')])) }}"
           class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition {{ $activeType === 'individual' ? 'bg-emerald-700 text-white shadow-xs' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
            <span>Individual Projects</span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-extrabold tabular-nums {{ $activeType === 'individual' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-800' }}">{{ number_format($counts['individual']) }}</span>
        </a>
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
        <span class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 tabular-nums">
            Total Grant: <span class="font-extrabold text-slate-900">&#8369;{{ number_format($totalGrantAmount, 2) }}</span>
        </span>
        <a href="{{ route('dilp.projects.create', $activeType !== 'all' ? ['type' => $activeType] : []) }}"
           class="inline-flex items-center gap-2 rounded-xl bg-blue-800 hover:bg-blue-900 px-4 py-2 text-xs font-extrabold text-white shadow-xs transition cursor-pointer">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span>Create DILP Project</span>
        </a>
    </div>
</div>

{{-- Filter Bar --}}
<form method="GET" action="{{ route('dilp.projects.index') }}" class="mb-5 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
    @if($activeType !== 'all')
        <input type="hidden" name="type" value="{{ $activeType }}">
    @endif
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search project, association, individual name, or ACP..."
                   class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-xs font-medium text-slate-900 focus:border-blue-700 focus:outline-none">
        </div>
        <div>
            <select name="implementation_mode" onchange="this.form.submit()"
                    class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-800 focus:border-blue-700 focus:outline-none">
                <option value="">All Modes (DA / ACP)</option>
                <option value="DA" {{ request('implementation_mode') === 'DA' ? 'selected' : '' }}>Direct Admin (DA)</option>
                <option value="ACP" {{ request('implementation_mode') === 'ACP' ? 'selected' : '' }}>Accredited Co-Partner (ACP)</option>
            </select>
        </div>
        <div class="flex gap-2">
            <select name="liquidation_status" onchange="this.form.submit()"
                    class="h-10 flex-1 rounded-xl border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-800 focus:border-blue-700 focus:outline-none">
                <option value="">All Liquidation</option>
                <option value="pending" {{ request('liquidation_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="partial" {{ request('liquidation_status') === 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="liquidated" {{ request('liquidation_status') === 'liquidated' ? 'selected' : '' }}>Liquidated</option>
            </select>
            <button type="submit" class="h-10 rounded-xl bg-slate-900 px-4 text-xs font-bold text-white hover:bg-slate-800 transition cursor-pointer">Filter</button>
        </div>
    </div>
</form>

{{-- DILP Projects Table --}}
<div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs min-w-[820px]">
            <thead class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3.5 whitespace-nowrap">
                        @if($activeType === 'individual')
                            Name
                        @elseif($activeType === 'group')
                            Association
                        @else
                            Association / Name
                        @endif
                    </th>
                    <th class="px-5 py-3.5 min-w-[200px]">Project</th>
                    <th class="px-5 py-3.5 whitespace-nowrap">Amount Grant</th>
                    <th class="px-5 py-3.5 whitespace-nowrap">
                        {{ $activeType === 'individual' ? 'Direct Admin / ACP' : 'DA / ACP' }}
                    </th>
                    <th class="px-5 py-3.5 whitespace-nowrap">Timeline</th>
                    <th class="px-5 py-3.5 whitespace-nowrap">Liquidation</th>
                    <th class="px-5 py-3.5 text-right whitespace-nowrap">Actions</th>
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
                        $isIndividual = $p->project_type === \App\Models\DilpProject::TYPE_INDIVIDUAL;
                    @endphp
                    <tr class="transition hover:bg-slate-50/80">
                        {{-- Association / Name --}}
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <p class="font-extrabold text-slate-900">{{ $p->grantee_display_name }}</p>
                            @if($activeType === 'all')
                                <span class="mt-0.5 inline-flex rounded px-1.5 py-0.5 text-[10px] font-bold {{ $isIndividual ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-blue-50 text-blue-800 border border-blue-200' }}">
                                    {{ $isIndividual ? 'Individual' : 'Group' }}
                                </span>
                            @endif
                        </td>

                        {{-- Project --}}
                        <td class="px-5 py-3.5 min-w-[200px]">
                            <p class="font-bold text-slate-900 text-xs sm:text-sm">{{ $p->project_name }}</p>
                            @if($p->description)
                                <p class="text-[11px] text-slate-400 truncate max-w-[240px]">{{ $p->description }}</p>
                            @endif
                        </td>

                        {{-- Amount Grant --}}
                        <td class="px-5 py-3.5 whitespace-nowrap font-extrabold text-slate-900 tabular-nums">
                            @if($p->grant_amount !== null)
                                &#8369;{{ number_format((float) $p->grant_amount, 2) }}
                            @else
                                <span class="text-slate-400 font-medium">—</span>
                            @endif
                        </td>

                        {{-- DA / ACP --}}
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            @if($p->implementation_mode === 'DA')
                                <span class="inline-flex rounded-md border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-extrabold text-blue-900">Direct Admin (DA)</span>
                            @elseif($p->implementation_mode === 'ACP')
                                <span class="inline-flex rounded-md border border-purple-200 bg-purple-50 px-2 py-0.5 text-[10px] font-extrabold text-purple-900">ACP</span>
                                @if($p->acp_name)
                                    <span class="block text-[11px] text-slate-500 mt-0.5">{{ $p->acp_name }}</span>
                                @endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>

                        {{-- Timeline --}}
                        <td class="px-5 py-3.5 whitespace-nowrap text-slate-600 tabular-nums">
                            <span class="font-bold text-slate-800">{{ $p->start_date?->format('M d, Y') ?? '—' }}</span>
                            <span class="text-slate-400"> to </span>
                            <span class="font-bold text-slate-800">{{ $p->end_date?->format('M d, Y') ?? '—' }}</span>
                        </td>

                        {{-- Liquidation Status --}}
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 rounded-md border {{ $liqBadge }} px-2.5 py-0.5 text-[10px] font-extrabold uppercase">
                                <span class="h-1.5 w-1.5 rounded-full {{ $liqDot }}"></span>
                                {{ $p->liquidation_status }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <a href="{{ route('dilp.projects.edit', $p) }}"
                                   class="inline-flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
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
                                            class="inline-flex items-center gap-1 rounded-xl border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100 transition cursor-pointer">
                                        <span>Delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-medium">
                            No DILP {{ $activeType !== 'all' ? $activeType : '' }} projects recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($projects->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">{{ $projects->links() }}</div>
    @endif
</div>
@endsection
