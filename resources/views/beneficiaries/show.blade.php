@extends('layouts.app')
@section('title', $beneficiary->full_name)
@section('page-title', 'Beneficiary Profile')
@section('page-subtitle', 'Detailed view of profile, program history, and duplicate logs')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <a href="{{ route('beneficiaries.index') }}" class="rounded-lg border border-gray-300 bg-white p-2 text-gray-600 hover:bg-gray-50">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h2 class="text-xl font-bold text-gray-900">{{ $beneficiary->full_name }}</h2>
    </div>

    @hasanyrole('Admin|Encoder')
    <div class="flex items-center gap-2">
        <a href="{{ route('beneficiaries.edit', $beneficiary) }}" class="rounded-xl bg-blue-800 px-4 py-2 text-sm font-extrabold text-white shadow-2xs hover:bg-blue-900 transition">
            Edit Beneficiary
        </a>
        <form action="{{ route('beneficiaries.destroy', $beneficiary) }}" method="POST" data-confirm="Are you sure you want to delete beneficiary {{ addslashes($beneficiary->full_name) }}?" data-confirm-title="Delete Beneficiary" data-confirm-btn="Yes, Delete Record">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-extrabold text-white shadow-2xs hover:bg-red-700 transition cursor-pointer">
                Delete Beneficiary
            </button>
        </form>
    </div>
    @endhasanyrole
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Personal Info Card --}}
    <div class="rounded-2xl border border-gray-200/60 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-gray-400">Personal Details</h3>
        <div class="space-y-3 text-sm">
            <div><span class="text-gray-400">Date of Birth:</span> <span class="font-medium text-gray-800">{{ $beneficiary->date_of_birth?->format('M d, Y') }} ({{ $beneficiary->age }} yrs old)</span></div>
            <div><span class="text-gray-400">Sex:</span> <span class="font-medium text-gray-800">{{ $beneficiary->sex }}</span></div>
            <div><span class="text-gray-400">Civil Status:</span> <span class="font-medium text-gray-800">{{ $beneficiary->civil_status ?? '—' }}</span></div>
            <div><span class="text-gray-400">Government ID:</span> <span class="font-medium text-gray-800">{{ $beneficiary->government_id_type }} {{ $beneficiary->government_id_number ?? '—' }}</span></div>
            <div><span class="text-gray-400">Contact:</span> <span class="font-medium text-gray-800">{{ $beneficiary->contact_number ?? '—' }}</span></div>
            <div><span class="text-gray-400">Location:</span> <span class="font-medium text-gray-800">Brgy. {{ $beneficiary->barangay }}, {{ $beneficiary->municipality }}</span></div>
        </div>

        <div class="mt-6 flex flex-wrap gap-1.5 border-t pt-4">
            @if($beneficiary->is_senior_citizen) <span class="rounded bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Senior Citizen</span> @endif
            @if($beneficiary->is_pwd) <span class="rounded bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-800">PWD</span> @endif
            @if($beneficiary->is_student) <span class="rounded bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">Student</span> @endif
        </div>
    </div>

    {{-- Program History --}}
    <div class="rounded-2xl border border-gray-200/60 bg-white p-6 shadow-sm lg:col-span-2">
        <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-gray-400">Program Availment History</h3>
        <div class="space-y-4">
            @forelse($beneficiary->beneficiaryPrograms as $bp)
                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <span class="rounded-lg bg-dole-blue px-3 py-1 text-sm font-bold text-white">{{ $bp->program?->code }}</span>
                        <span class="text-xs text-gray-400">Year: {{ $bp->availment_year }}</span>
                    </div>
                    <p class="mt-2 text-xs text-gray-600">{{ $bp->program?->name }}</p>

                    @if($bp->tupadProfile)
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-500 bg-gray-50 p-3 rounded-lg">
                            <div>Account: <span class="font-medium text-gray-700">{{ $bp->tupadProfile->epayment_account_no ?? '—' }}</span></div>
                            <div>Sector: <span class="font-medium text-gray-700">{{ $bp->tupadProfile->beneficiary_type ?? '—' }}</span></div>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-400">No program history found.</p>
            @endforelse
        </div>

        {{-- Duplicate Flags History --}}
        @if($beneficiary->duplicateFlags->isNotEmpty())
            <h3 class="mb-4 mt-8 text-sm font-bold uppercase tracking-wider text-amber-600">Duplicate Alerts History</h3>
            <div class="space-y-3">
                @foreach($beneficiary->duplicateFlags as $flag)
                    <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-amber-800">Matched with {{ $flag->matchedBeneficiary?->full_name }}</span>
                            <span class="rounded-full bg-amber-600 px-2.5 py-0.5 text-xs font-bold text-white">{{ $flag->match_score }}% Match</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-600">Status: <strong class="capitalize">{{ str_replace('_', ' ', $flag->status) }}</strong></p>
                        @if($flag->remarks)
                            <p class="mt-1 text-xs text-gray-500 italic">Remarks: {{ $flag->remarks }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
