@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard Overview')
@section('page-subtitle', 'Real-time overview of beneficiary records, program allocations, and duplicate detection alerts')

@section('content')
{{-- DOLE Official Header Banner --}}
<div class="mb-6 flex items-center justify-between rounded-2xl border border-blue-800/60 bg-gradient-to-r from-blue-900 via-blue-800 to-blue-950 p-6 text-white shadow-md relative overflow-hidden">
    <div class="absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-amber-500/10 blur-2xl pointer-events-none"></div>
    <div class="flex items-center gap-4 relative z-10">
        <div class="rounded-xl bg-white p-1.5 shadow-md border border-white/20 shrink-0">
            <img src="{{ asset('dole-logo.png') }}" alt="DOLE Official Logo" class="h-12 w-12 object-contain bg-white">
        </div>
        <div>
            <h2 class="text-lg font-black tracking-tight text-white">DOLE Bukidnon Field Office</h2>
            <p class="text-xs font-semibold text-blue-100">Beneficiary Duplicate Detection & Cross-Program System</p>
        </div>
    </div>
    <div class="hidden sm:block text-right relative z-10">
        <p class="text-[11px] font-extrabold text-amber-400 uppercase tracking-wider">Active System Year</p>
        <p class="text-2xl font-black text-white tracking-tight">{{ date('Y') }}</p>
    </div>
</div>

{{-- Summary Metrics Grid --}}
<div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    {{-- Card 1: Total Beneficiaries --}}
    <div class="gov-card gov-card-hover p-5 border-l-4 border-l-blue-800">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-800 text-white shadow-2xs">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            </div>
            <span class="rounded-md bg-blue-100 px-2.5 py-0.5 text-[11px] font-bold text-blue-900">Total</span>
        </div>
        <p class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($totalBeneficiaries) }}</p>
        <p class="mt-1 text-xs font-semibold text-slate-600">Registered Beneficiaries</p>
    </div>

    {{-- Card 2: Duplicates Detected --}}
    <div class="gov-card gov-card-hover p-5 border-l-4 border-l-amber-500">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500 text-white shadow-2xs">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <span class="rounded-md bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-900">Flagged</span>
        </div>
        <p class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($totalDuplicates) }}</p>
        <p class="mt-1 text-xs font-semibold text-slate-600">Duplicates Detected</p>
    </div>

    {{-- Card 3: Pending Review --}}
    <div class="gov-card gov-card-hover p-5 border-l-4 border-l-red-600">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-600 text-white shadow-2xs">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="rounded-md bg-red-100 px-2.5 py-0.5 text-[11px] font-bold text-red-900">Action Req.</span>
        </div>
        <p class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($pendingDuplicates) }}</p>
        <p class="mt-1 text-xs font-semibold text-slate-600">Pending Flags</p>
    </div>

    {{-- Card 4: Resolved --}}
    <div class="gov-card gov-card-hover p-5 border-l-4 border-l-emerald-600">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-2xs">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="rounded-md bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-900">Completed</span>
        </div>
        <p class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($resolvedDuplicates) }}</p>
        <p class="mt-1 text-xs font-semibold text-slate-600">Resolved Flags</p>
    </div>
</div>

{{-- Main Grid: Program Breakdown & Alerts --}}
<div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
    {{-- Program Distribution Card --}}
    <div class="gov-card p-6">
        <div class="mb-5 flex items-center justify-between border-b border-slate-200 pb-3">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Program Distribution</h3>
            <span class="text-xs font-bold text-blue-800">Across 4 Programs</span>
        </div>

        <div class="space-y-4">
            @php
            $programs = [
                'TUPAD' => ['color' => 'bg-blue-800', 'name' => 'Emergency Employment'],
                'SPES' => ['color' => 'bg-red-600', 'name' => 'Student Employment'],
                'DILP' => ['color' => 'bg-amber-500', 'name' => 'Livelihood Program'],
                'GIP' => ['color' => 'bg-indigo-700', 'name' => 'Government Internship'],
            ];
            @endphp

            @foreach($programs as $code => $meta)
                @php
                    $count = $programStats[$code] ?? 0;
                    $pct = $totalBeneficiaries > 0 ? round(($count / max($totalBeneficiaries, 1)) * 100) : 0;
                @endphp
                <div>
                    <div class="mb-1.5 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-900">{{ $code }}</span>
                            <span class="ml-2 text-slate-600 font-semibold">({{ $meta['name'] }})</span>
                        </div>
                        <span class="font-bold text-slate-900">{{ number_format($count) }} <span class="text-slate-500 font-medium">({{ $pct }}%)</span></span>
                    </div>
                    <div class="h-3 w-full overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full {{ $meta['color'] }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Pending Alerts Panel --}}
    <div class="gov-card p-6">
        <div class="mb-5 flex items-center justify-between border-b border-slate-200 pb-3">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Pending Duplicate Alerts</h3>
            @hasanyrole('Admin|Validator')
            <a href="{{ route('duplicates.index') }}" class="text-xs font-bold text-blue-800 hover:underline">View All Console →</a>
            @endhasanyrole
        </div>

        @if($recentDuplicates->isEmpty())
            <div class="flex flex-col items-center justify-center py-10 text-center">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 mb-2">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-xs font-bold text-slate-900">All Clear!</p>
                <p class="text-xs text-slate-600">No pending duplicate alerts require review at this time.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($recentDuplicates->take(4) as $flag)
                    <div class="flex items-center gap-3.5 rounded-xl border border-amber-200 bg-amber-50/70 p-3.5 transition hover:bg-amber-100/60">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-500 font-extrabold text-white text-xs shadow-2xs">
                            {{ $flag->match_score }}%
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold text-slate-900">{{ $flag->beneficiary?->full_name }}</p>
                            <p class="truncate text-[11px] font-semibold text-slate-600">matches {{ $flag->matchedBeneficiary?->full_name }}</p>
                        </div>
                        @hasanyrole('Admin|Validator')
                        <a href="{{ route('duplicates.index') }}" class="rounded-lg bg-white border border-amber-300 px-3 py-1 text-xs font-bold text-amber-900 shadow-2xs hover:bg-amber-50 transition">
                            Review
                        </a>
                        @endhasanyrole
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Recent Beneficiaries Master Table --}}
<div class="gov-card overflow-hidden">
    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-4">
        <div>
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Recently Registered Beneficiaries</h3>
            <p class="text-xs text-slate-600 font-medium">Latest additions across Bukidnon municipalities</p>
        </div>
        <a href="{{ route('beneficiaries.index') }}" class="rounded-lg border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-800 shadow-2xs transition hover:bg-slate-50 hover:text-blue-800">
            View All Registry →
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-700">
                <tr>
                    <th class="px-6 py-3.5">Beneficiary Name</th>
                    <th class="px-6 py-3.5">Municipality</th>
                    <th class="px-6 py-3.5">Date of Birth</th>
                    <th class="px-6 py-3.5">Encoded By</th>
                    <th class="px-6 py-3.5">Date Added</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-xs font-medium bg-white">
                @forelse($recentBeneficiaries as $b)
                    <tr class="transition hover:bg-slate-50">
                        <td class="whitespace-nowrap px-6 py-3.5 font-bold text-slate-900">
                            <a href="{{ route('beneficiaries.show', $b) }}" class="hover:text-blue-800 hover:underline">{{ $b->full_name }}</a>
                        </td>
                        <td class="px-6 py-3.5 text-slate-700 font-medium">{{ $b->municipality }}</td>
                        <td class="px-6 py-3.5 text-slate-700 font-medium">{{ $b->date_of_birth?->format('M d, Y') }}</td>
                        <td class="px-6 py-3.5 text-slate-700 font-medium">{{ $b->creator?->name ?? '—' }}</td>
                        <td class="px-6 py-3.5 text-slate-600 font-medium">{{ $b->created_at?->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500 font-medium">No beneficiaries registered yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
