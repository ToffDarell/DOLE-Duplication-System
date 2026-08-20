@extends('layouts.app')
@section('title', 'DILP Projects')
@section('page-title', 'DILP Livelihood Projects')
@section('page-subtitle', 'Track DILP projects and 3-stage liquidation status (pending, partial, liquidated)')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('dilp.projects.create') }}" class="rounded-lg bg-dole-blue px-4 py-2 text-sm font-semibold text-white hover:bg-dole-blue-dark">
        + Create DILP Project
    </a>
</div>

<div class="rounded-xl border border-gray-200/60 bg-white shadow-sm overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-100 bg-gray-50/70 text-xs uppercase text-gray-500">
            <tr>
                <th class="px-6 py-3 font-semibold">Project Name</th>
                <th class="px-6 py-3 font-semibold">Associated Group</th>
                <th class="px-6 py-3 font-semibold">Start / End Date</th>
                <th class="px-6 py-3 font-semibold">Liquidation Status</th>
                <th class="px-6 py-3 text-right font-semibold">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($projects as $p)
                <tr>
                    <td class="px-6 py-4 font-bold text-gray-900">{{ $p->project_name }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $p->group?->group_name ?? 'Individual' }}</td>
                    <td class="px-6 py-4 text-xs text-gray-500">
                        {{ $p->start_date?->format('M d, Y') ?? '—' }} to {{ $p->end_date?->format('M d, Y') ?? '—' }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold uppercase
                            {{ match($p->liquidation_status) {
                                'liquidated' => 'bg-emerald-100 text-emerald-800',
                                'partial' => 'bg-amber-100 text-amber-800',
                                default => 'bg-red-100 text-red-800'
                            } }}">
                            {{ $p->liquidation_status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('dilp.projects.edit', $p) }}" class="text-xs font-medium text-dole-blue hover:underline">Edit Status</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No DILP projects created yet</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t">{{ $projects->links() }}</div>
</div>
@endsection
