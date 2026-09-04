@extends('layouts.app')
@section('title', $beneficiary->full_name)
@section('page-title', 'Beneficiary Profile')
@section('page-subtitle', 'Detailed master profile, program availment history, and duplicate intelligence')

@section('content')
@php
    $initials = strtoupper(substr($beneficiary->first_name, 0, 1) . substr($beneficiary->last_name, 0, 1));
    $latestProgram = $beneficiary->beneficiaryPrograms->first();
@endphp

{{-- Top Profile Header --}}
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-center gap-3.5 min-w-0">
        <a href="{{ route('beneficiaries.index') }}" 
           class="flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition shadow-2xs shrink-0"
           title="Back to Beneficiaries Registry">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
        </a>
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-800 text-white font-black text-base shadow-xs">
            {{ $initials }}
        </div>
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight truncate">{{ $beneficiary->full_name }}</h2>
                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-100 border border-emerald-200 px-2 py-0.5 text-[11px] font-extrabold text-emerald-900">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                    Active Masterlist
                </span>
                @if($latestProgram)
                    <span class="inline-flex items-center rounded-md bg-blue-100 border border-blue-200 px-2 py-0.5 text-[11px] font-extrabold text-blue-900">
                        {{ $latestProgram->program?->code }} ({{ $latestProgram->availment_year }})
                    </span>
                @endif
            </div>
            <p class="text-xs font-semibold text-slate-500 mt-0.5">
                System Record ID: #{{ $beneficiary->id }} &middot; Added {{ $beneficiary->created_at?->format('M d, Y') }}
            </p>
        </div>
    </div>

    @hasanyrole('Admin|Encoder')
    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
        <a href="{{ route('beneficiaries.edit', $beneficiary) }}" 
           class="inline-flex items-center gap-1.5 rounded-xl bg-blue-800 px-4 py-2 text-xs sm:text-sm font-extrabold text-white shadow-2xs hover:bg-blue-900 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
            </svg>
            <span>Edit Beneficiary</span>
        </a>
        <form action="{{ route('beneficiaries.destroy', $beneficiary) }}" method="POST" 
              data-confirm="Are you sure you want to archive beneficiary {{ addslashes($beneficiary->full_name) }}? It will be moved to Archives and can be restored at any time." 
              data-confirm-title="Archive Beneficiary" 
              data-confirm-btn="Yes, Archive Record">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-4 py-2 text-xs sm:text-sm font-extrabold text-white shadow-2xs hover:bg-amber-700 transition cursor-pointer">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                </svg>
                <span>Archive Record</span>
            </button>
        </form>
    </div>
    @endhasanyrole
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Personal Info Card --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs flex flex-col justify-between">
        <div>
            <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-800 shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">Personal Details</h3>
            </div>

            <div class="space-y-4 text-xs">
                {{-- Date of Birth --}}
                <div class="flex items-start gap-3">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold uppercase tracking-wider text-slate-400 text-[10px]">Date of Birth</p>
                        <p class="font-extrabold text-slate-800 mt-0.5 text-sm">{{ $beneficiary->date_of_birth?->format('M d, Y') }} <span class="text-xs text-slate-500 font-semibold">({{ $beneficiary->age }} yrs old)</span></p>
                    </div>
                </div>

                {{-- Sex & Civil Status --}}
                <div class="flex items-start gap-3">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold uppercase tracking-wider text-slate-400 text-[10px]">Sex / Civil Status</p>
                        <p class="font-extrabold text-slate-800 mt-0.5 text-sm">{{ $beneficiary->sex }} &middot; {{ $beneficiary->civil_status ?? 'Not specified' }}</p>
                    </div>
                </div>

                {{-- Government ID --}}
                <div class="flex items-start gap-3">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold uppercase tracking-wider text-slate-400 text-[10px]">Government ID ({{ $beneficiary->government_id_type }})</p>
                        <p class="font-extrabold text-slate-900 mt-0.5 font-mono text-xs">{{ $beneficiary->government_id_number ?? '—' }}</p>
                    </div>
                </div>

                {{-- Contact Number --}}
                <div class="flex items-start gap-3">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold uppercase tracking-wider text-slate-400 text-[10px]">Contact Number</p>
                        <p class="font-extrabold text-slate-800 mt-0.5">{{ $beneficiary->contact_number ?? 'N/A' }}</p>
                    </div>
                </div>

                {{-- Location Details --}}
                <div class="flex items-start gap-3">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold uppercase tracking-wider text-slate-400 text-[10px]">Registered Location</p>
                        <p class="font-bold text-slate-800 mt-0.5 leading-relaxed">
                            @if($beneficiary->address)
                                <span class="font-black text-blue-800">{{ $beneficiary->address }}</span>,
                            @endif
                            Brgy. {{ $beneficiary->barangay }}, {{ $beneficiary->municipality }}{{ $beneficiary->province ? ', ' . $beneficiary->province : '' }}{{ $beneficiary->district ? ' (' . $beneficiary->district . ')' : '' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Special Demographic Badges (Rendered ONLY if at least one applies) --}}
        @if($beneficiary->is_senior_citizen || $beneficiary->is_pwd || $beneficiary->is_student)
            <div class="mt-6 border-t border-slate-100 pt-4">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">Special Classifications</p>
                <div class="flex flex-wrap gap-1.5">
                    @if($beneficiary->is_senior_citizen) 
                        <span class="rounded-md bg-amber-100 border border-amber-200 px-2.5 py-1 text-xs font-bold text-amber-800">Senior Citizen</span> 
                    @endif
                    @if($beneficiary->is_pwd) 
                        <span class="rounded-md bg-purple-100 border border-purple-200 px-2.5 py-1 text-xs font-bold text-purple-800">PWD</span> 
                    @endif
                    @if($beneficiary->is_student) 
                        <span class="rounded-md bg-blue-100 border border-blue-200 px-2.5 py-1 text-xs font-bold text-blue-800">Student</span> 
                    @endif
                </div>
            </div>
        @else
            <div class="mt-6 border-t border-slate-100 pt-3">
                <p class="text-[11px] font-medium text-slate-400 italic">No special demographic flags registered.</p>
            </div>
        @endif
    </div>

    {{-- Program History & Duplicate Alerts --}}
    <div class="space-y-6 lg:col-span-2">
        {{-- Program Availment History Card --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-800 shrink-0">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/>
                        </svg>
                    </div>
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">Program Availment History</h3>
                </div>
                <span class="text-[11px] font-bold text-slate-500">{{ $beneficiary->beneficiaryPrograms->count() }} Record(s)</span>
            </div>

            <div class="space-y-3.5">
                @forelse($beneficiary->beneficiaryPrograms as $bp)
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 transition hover:border-slate-300">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="rounded-lg bg-blue-800 px-3 py-1 text-xs font-black text-white shadow-2xs">{{ $bp->program?->code }}</span>
                                <span class="text-xs font-extrabold text-slate-700">{{ $bp->program?->name }}</span>
                            </div>
                            <span class="rounded-md bg-white border border-slate-200 px-2.5 py-0.5 text-xs font-extrabold text-slate-700 shadow-2xs">Year: {{ $bp->availment_year }}</span>
                        </div>

                        @if($bp->tupadProfile)
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs bg-white border border-slate-200/80 p-3 rounded-lg shadow-2xs">
                                <div>
                                    <span class="font-bold text-slate-400 text-[10px] uppercase">Account:</span>
                                    <p class="font-bold text-slate-800 mt-0.5">{{ $bp->tupadProfile->epayment_account_no ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-400 text-[10px] uppercase">Sector:</span>
                                    <p class="font-bold text-slate-800 mt-0.5">{{ $bp->tupadProfile->beneficiary_type ?? 'N/A' }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-slate-400 text-xs font-semibold">
                        No program availment history logged for this beneficiary.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Duplicate Flags History Card --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-amber-800 shrink-0">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <h3 class="text-xs font-black uppercase tracking-wider text-amber-900">Duplicate Alerts &amp; Conflicts</h3>
                </div>
                <a href="{{ route('duplicates.index', ['search' => $beneficiary->last_name]) }}" 
                   class="text-xs font-extrabold text-blue-700 hover:text-blue-900 transition flex items-center gap-1">
                    <span>Duplicate Center</span>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>

            @if($beneficiary->duplicateFlags->isNotEmpty())
                <div class="space-y-3.5">
                    @foreach($beneficiary->duplicateFlags as $flag)
                        @php
                            $score = (int) $flag->match_score;
                            if ($score >= 100) {
                                $badgeClass = 'bg-red-600 text-white';
                                $borderClass = 'border-red-200 bg-red-50/40';
                            } elseif ($score >= 85) {
                                $badgeClass = 'bg-orange-500 text-white';
                                $borderClass = 'border-orange-200 bg-orange-50/40';
                            } elseif ($score >= 70) {
                                $badgeClass = 'bg-amber-500 text-white';
                                $borderClass = 'border-amber-200 bg-amber-50/40';
                            } else {
                                $badgeClass = 'bg-emerald-600 text-white';
                                $borderClass = 'border-emerald-200 bg-emerald-50/40';
                            }
                        @endphp
                        <div class="rounded-xl border {{ $borderClass }} p-4 shadow-2xs">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-lg {{ $badgeClass }} px-2.5 py-0.5 text-xs font-black shadow-2xs">
                                            {{ $flag->match_score }}% Match
                                        </span>
                                        <span class="text-xs font-extrabold text-slate-900">
                                            Matched with: 
                                            @if($flag->matchedBeneficiary)
                                                <a href="{{ route('beneficiaries.show', $flag->matchedBeneficiary) }}" class="text-blue-800 hover:underline font-black">
                                                    {{ $flag->matchedBeneficiary->full_name }}
                                                </a>
                                            @else
                                                <span class="text-slate-700">Archived/Candidate Record</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                                        <span class="inline-flex items-center gap-1 font-semibold">
                                            Status: 
                                            <span class="rounded px-1.5 py-0.5 text-[11px] font-extrabold capitalize {{ $flag->status === 'pending' ? 'bg-amber-100 text-amber-900 border border-amber-200' : ($flag->status === 'overridden' ? 'bg-emerald-100 text-emerald-900 border border-emerald-200' : 'bg-slate-100 text-slate-800') }}">
                                                {{ str_replace('_', ' ', $flag->status) }}
                                            </span>
                                        </span>
                                        <span>&middot;</span>
                                        <span class="font-semibold">Type: <strong class="text-slate-800 uppercase">{{ str_replace('_', ' ', $flag->match_type) }}</strong></span>
                                        @if($flag->household_match_flag)
                                            <span class="rounded bg-blue-100 border border-blue-200 px-1.5 py-0.5 text-[10px] font-extrabold text-blue-900">
                                                House Number Match
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Action Link --}}
                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="{{ route('duplicates.index', ['search' => $beneficiary->last_name]) }}" 
                                       class="inline-flex items-center gap-1 rounded-xl bg-blue-800 hover:bg-blue-900 text-white px-3.5 py-1.5 text-xs font-extrabold shadow-2xs transition">
                                        <span>Review in Console</span>
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                    </a>
                                </div>
                            </div>

                            @if($flag->remarks)
                                <div class="mt-2.5 border-t border-slate-200/60 pt-2 text-xs text-slate-600 italic">
                                    Resolution Remarks: {{ $flag->remarks }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 shrink-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-emerald-900">Clean Beneficiary Record</p>
                        <p class="text-[11px] text-emerald-700 mt-0.5">No duplicate records, household limit conflicts, or cross-program violations detected.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
