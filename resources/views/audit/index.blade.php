@extends('layouts.app')
@section('title', 'Audit Logs')
@section('page-title', 'System Audit Trail')
@section('page-subtitle', 'Immutable security history of user actions, duplicate overrides, program enrollments, and system events')

@section('content')
{{-- Filter Toolbar --}}
<div class="mb-5 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xs">
    <form method="GET" action="{{ route('audit.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-700">Action Type</label>
            <select name="action" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-800 focus:border-blue-800 focus:outline-none shadow-2xs cursor-pointer">
                <option value="">All Actions</option>
                @foreach($actions as $act)
                    <option value="{{ $act }}" {{ request('action') == $act ? 'selected' : '' }}>{{ strtoupper(str_replace('_', ' ', $act)) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-700">Staff User</label>
            <select name="user_id" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-800 focus:border-blue-800 focus:outline-none shadow-2xs cursor-pointer">
                <option value="">All Users</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-700">Search Keywords</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description..."
                   class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-semibold text-slate-900 focus:border-blue-800 focus:outline-none shadow-2xs">
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 rounded-xl bg-blue-800 hover:bg-blue-900 px-4 py-2 text-xs font-extrabold text-white shadow-sm transition cursor-pointer">
                Filter Logs
            </button>
            @if(request()->hasAny(['action', 'user_id', 'search']))
                <a href="{{ route('audit.index') }}" class="rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition shadow-2xs">
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>

{{-- Audit Logs Table --}}
<div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs min-w-[800px]">
            <thead class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3.5 whitespace-nowrap min-w-[150px]">Timestamp</th>
                    <th class="px-5 py-3.5 whitespace-nowrap min-w-[160px]">Staff User</th>
                    <th class="px-5 py-3.5 whitespace-nowrap min-w-[130px]">Action</th>
                    <th class="px-5 py-3.5 min-w-[280px]">Activity Description</th>
                    <th class="px-5 py-3.5 whitespace-nowrap min-w-[110px] text-right">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium bg-white">
                @forelse($logs as $log)
                    @php
                        $act = $log->action;
                        if ($act === 'duplicate_override') {
                            $badgeStyle = 'bg-amber-50 text-amber-900 border-amber-200';
                            $iconColor = 'text-amber-600';
                        } elseif ($act === 'delete' || $act === 'force_delete') {
                            $badgeStyle = 'bg-rose-50 text-rose-900 border-rose-200';
                            $iconColor = 'text-rose-600';
                        } elseif ($act === 'create' || $act === 'restore') {
                            $badgeStyle = 'bg-emerald-50 text-emerald-900 border-emerald-200';
                            $iconColor = 'text-emerald-600';
                        } else {
                            $badgeStyle = 'bg-blue-50 text-blue-900 border-blue-200';
                            $iconColor = 'text-blue-600';
                        }
                    @endphp
                    <tr class="transition hover:bg-slate-50/80">
                        {{-- Timestamp --}}
                        <td class="px-5 py-3.5 whitespace-nowrap text-slate-700">
                            <span class="font-bold text-slate-800 block">{{ $log->created_at?->format('M d, Y') }}</span>
                            <span class="block text-[11px] text-slate-400 font-normal mt-0.5">{{ $log->created_at?->format('h:i:s A') }} ({{ $log->created_at?->diffForHumans() }})</span>
                        </td>

                        {{-- Staff User --}}
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-700 font-extrabold text-[10px] border border-slate-200">
                                    {{ strtoupper(substr($log->user?->name ?? 'SYS', 0, 2)) }}
                                </div>
                                <span class="font-extrabold text-slate-900 text-xs">{{ $log->user?->name ?? 'System' }}</span>
                            </div>
                        </td>

                        {{-- Action Badge --}}
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1 rounded-md border {{ $badgeStyle }} px-2 py-0.5 text-[10px] font-extrabold uppercase shadow-2xs">
                                @if($act === 'duplicate_override')
                                    <svg class="h-3 w-3 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                                    </svg>
                                @endif
                                {{ str_replace('_', ' ', $log->action) }}
                            </span>
                        </td>

                        {{-- Description --}}
                        <td class="px-5 py-3.5 text-slate-800 text-xs leading-relaxed">
                            {{ $log->description }}
                        </td>

                        {{-- IP Address --}}
                        <td class="px-5 py-3.5 whitespace-nowrap text-right font-mono text-[11px] text-slate-500">
                            {{ $log->ip_address ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium">
                            No audit trail records match the specified criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-100 px-6 py-4 bg-slate-50/50">
        {{ $logs->links() }}
    </div>
</div>
@endsection
