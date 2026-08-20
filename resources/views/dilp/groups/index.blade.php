@extends('layouts.app')
@section('title', 'DILP Groups')
@section('page-title', 'DILP Groups & Cooperatives')
@section('page-subtitle', 'Manage DILP group associations and co-partner details')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('dilp.groups.create') }}" class="rounded-lg bg-dole-blue px-4 py-2 text-sm font-semibold text-white hover:bg-dole-blue-dark">
        + Create DILP Group
    </a>
</div>

<div class="rounded-xl border border-gray-200/60 bg-white shadow-sm overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-100 bg-gray-50/70 text-xs uppercase text-gray-500">
            <tr>
                <th class="px-6 py-3 font-semibold">Group Name</th>
                <th class="px-6 py-3 font-semibold">Co-Partner Organization</th>
                <th class="px-6 py-3 font-semibold">Co-Partner Contact</th>
                <th class="px-6 py-3 font-semibold">Total Projects</th>
                <th class="px-6 py-3 text-right font-semibold">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($groups as $g)
                <tr>
                    <td class="px-6 py-4 font-bold text-gray-900">{{ $g->group_name }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $g->co_partner_name ?? '—' }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $g->co_partner_contact ?? '—' }}</td>
                    <td class="px-6 py-4"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-dole-blue">{{ $g->projects_count }}</span></td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('dilp.groups.edit', $g) }}" class="text-xs font-medium text-dole-blue hover:underline">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No DILP groups created yet</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t">{{ $groups->links() }}</div>
</div>
@endsection
