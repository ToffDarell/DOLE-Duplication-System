@extends('layouts.app')
@section('title', 'Audit Logs')
@section('page-title', 'System Audit Trail')
@section('page-subtitle', 'Immutable history of user actions, duplicate overrides, and system events')

@section('content')
<div class="mb-6 rounded-xl border border-gray-200/60 bg-white p-4 shadow-sm">
    <form method="GET" action="{{ route('audit.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600">Action Type</label>
            <select name="action" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                <option value="">All Actions</option>
                @foreach($actions as $act)
                    <option value="{{ $act }}" {{ request('action') == $act ? 'selected' : '' }}>{{ strtoupper($act) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600">User</label>
            <select name="user_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                <option value="">All Users</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-gray-600">Search Keywords</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description..."
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="w-full rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">
                Filter Logs
            </button>
            <a href="{{ route('audit.index') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">
                Reset
            </a>
        </div>
    </form>
</div>

<div class="rounded-xl border border-gray-200/60 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-100 bg-gray-50/70 text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3.5 font-semibold">Timestamp</th>
                    <th class="px-6 py-3.5 font-semibold">User</th>
                    <th class="px-6 py-3.5 font-semibold">Action</th>
                    <th class="px-6 py-3.5 font-semibold">Description</th>
                    <th class="px-6 py-3.5 font-semibold">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-xs">
                @forelse($logs as $log)
                    <tr class="transition hover:bg-gray-50/60">
                        <td class="whitespace-nowrap px-6 py-3.5 text-gray-500">{{ $log->created_at?->format('M d, Y h:i:s A') }}</td>
                        <td class="whitespace-nowrap px-6 py-3.5 font-medium text-gray-900">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="whitespace-nowrap px-6 py-3.5">
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase
                                {{ match($log->action) {
                                    'create' => 'bg-emerald-100 text-emerald-800',
                                    'delete' => 'bg-red-100 text-red-800',
                                    'duplicate_override' => 'bg-amber-100 text-amber-800',
                                    default => 'bg-blue-100 text-blue-800'
                                } }}">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-gray-700">{{ $log->description }}</td>
                        <td class="whitespace-nowrap px-6 py-3.5 text-gray-400 font-mono">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">No audit trail records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-gray-100 px-6 py-4">
        {{ $logs->links() }}
    </div>
</div>
@endsection
