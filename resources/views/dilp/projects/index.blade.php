@extends('layouts.app')
@section('title', 'DILP Projects')
@section('page-title', 'DILP Livelihood Projects')
@section('page-subtitle', 'Track DILP projects and 3-stage liquidation status (pending, partial, liquidated)')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('dilp.projects.create') }}" class="rounded-xl bg-dole-blue px-4 py-2.5 text-xs font-extrabold text-white hover:bg-dole-blue-dark shadow-sm transition">
        + Create DILP Project
    </a>
</div>

<div class="rounded-2xl border border-gray-200/60 bg-white shadow-sm overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-100 bg-gray-50/70 text-xs uppercase text-gray-500 font-bold">
            <tr>
                <th class="px-6 py-3.5 font-bold">Project Name</th>
                <th class="px-6 py-3.5 font-bold">Associated Group</th>
                <th class="px-6 py-3.5 font-bold">Start / End Date</th>
                <th class="px-6 py-3.5 font-bold">Liquidation Status</th>
                <th class="px-6 py-3.5 text-right font-bold">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-xs">
            @forelse($projects as $p)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4 font-bold text-gray-900">{{ $p->project_name }}</td>
                    <td class="px-6 py-4 text-gray-600 font-medium">{{ $p->group?->group_name ?? 'Individual' }}</td>
                    <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                        {{ $p->start_date?->format('M d, Y') ?? '—' }} to {{ $p->end_date?->format('M d, Y') ?? '—' }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold uppercase
                            {{ match(strtolower($p->liquidation_status)) {
                                'liquidated' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                                'partial' => 'bg-amber-100 text-amber-800 border border-amber-200',
                                default => 'bg-red-100 text-red-800 border border-red-200'
                            } }}">
                            {{ $p->liquidation_status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('dilp.projects.edit', $p) }}"
                               class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition shadow-2xs">
                                Edit Status
                            </a>
                            <form action="{{ route('dilp.projects.destroy', $p) }}" method="POST"
                                  data-confirm="Are you sure you want to delete project {{ addslashes($p->project_name) }}?"
                                  data-confirm-title="Delete DILP Project"
                                  data-confirm-btn="Yes, Delete Project"
                                  class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100 hover:text-rose-900 transition shadow-2xs cursor-pointer">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400 font-medium">No DILP projects created yet</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $projects->links() }}</div>
</div>
@endsection
