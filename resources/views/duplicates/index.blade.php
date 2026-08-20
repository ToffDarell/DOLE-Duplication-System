@extends('layouts.app')
@section('title', 'Duplicate Flags')
@section('page-title', 'Duplicate Resolution Console')
@section('page-subtitle', 'Review and resolve flagged duplicate beneficiary records')

@section('content')
{{-- Filter Tabs & Auto Search Bar --}}
<form method="GET" action="{{ route('duplicates.index') }}" class="mb-6 flex flex-wrap items-center justify-between gap-4">
    @if(request('status'))
        <input type="hidden" name="status" value="{{ request('status') }}">
    @endif
    @if(request('household'))
        <input type="hidden" name="household" value="{{ request('household') }}">
    @endif

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('duplicates.index') }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ !request('status') && !request('household') ? 'bg-slate-900 text-white shadow-2xs' : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50' }}">
            All Flags
        </a>
        <a href="{{ route('duplicates.index', ['status' => 'pending']) }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ request('status') == 'pending' ? 'bg-amber-600 text-white shadow-2xs' : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50' }}">
            Pending Review
        </a>
        <a href="{{ route('duplicates.index', ['household' => '1']) }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ request('household') == '1' ? 'bg-blue-700 text-white shadow-2xs' : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50' }}">
            Household Flags
        </a>
        <a href="{{ route('duplicates.index', ['status' => 'resolved']) }}" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ in_array(request('status'), ['resolved', 'overridden']) ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50' }}">
            Resolved & Approved
        </a>
    </div>

    <div class="relative w-full sm:w-72">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Live search beneficiary..."
               class="w-full rounded-xl border border-slate-300 bg-white pl-9 pr-4 py-2 text-xs font-medium focus:border-blue-700 focus:outline-none shadow-2xs">
        <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
        </svg>
    </div>
</form>

{{-- Duplicate Flags List --}}
<div class="space-y-4" x-data="{ showResolveModal(flag) { this.activeFlag = flag; this.resolveModal = true; } }">
    @forelse($flags as $flag)
        <div class="rounded-2xl border {{ $flag->household_match_flag ? 'border-blue-200 bg-blue-50/30' : 'border-gray-200/60 bg-white' }} p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $flag->household_match_flag ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }} font-bold">
                        {{ $flag->match_score }}%
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-bold text-gray-900">{{ $flag->beneficiary?->full_name }}</h4>
                            <span class="text-xs text-gray-400">{{ $flag->household_match_flag ? 'household with' : 'matches' }}</span>
                            <h4 class="font-bold text-gray-900">{{ $flag->matchedBeneficiary?->full_name }}</h4>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            @if($flag->household_match_flag)
                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 border border-blue-200 px-2.5 py-0.5 text-[10px] font-extrabold text-blue-900">
                                    Household Verification
                                </span>
                            @endif
                            <span class="text-xs text-gray-500">
                                Flag #{{ $flag->id }} | Type: <span class="uppercase font-semibold text-amber-700">{{ $flag->match_type }}</span> |
                                Status: <span class="font-semibold text-gray-800 capitalize">{{ str_replace('_', ' ', $flag->status) }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('beneficiaries.show', $flag->beneficiary) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                        View Profile 1
                    </a>
                    <a href="{{ route('beneficiaries.show', $flag->matchedBeneficiary) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                        View Profile 2
                    </a>
                    @if($flag->status === 'pending')
                        <button @click="showResolveModal({{ json_encode($flag) }})" class="rounded-lg bg-dole-blue px-4 py-1.5 text-xs font-semibold text-white hover:bg-dole-blue-dark">
                            Resolve Flag
                        </button>
                    @endif
                </div>
            </div>

            {{-- Side-by-Side Comparison for Household Flags --}}
            @if($flag->household_match_flag)
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-blue-200 bg-white p-3.5">
                        <p class="mb-2 text-[10px] font-extrabold uppercase tracking-wider text-blue-800">Individual 1 (New Record)</p>
                        <p class="text-sm font-bold text-gray-900">{{ $flag->beneficiary?->full_name }}</p>
                        <p class="text-xs text-gray-600">DOB: {{ $flag->beneficiary?->date_of_birth?->format('M d, Y') ?? '—' }}</p>
                        <p class="text-xs text-gray-600">Address: {{ $flag->beneficiary?->address ?? '(not specified)' }}</p>
                        <p class="text-xs text-gray-600">Barangay: {{ $flag->beneficiary?->barangay }}</p>
                        <p class="text-xs text-gray-600">Gov ID: {{ $flag->beneficiary?->government_id_number ?? '—' }}</p>
                    </div>
                    <div class="rounded-xl border border-blue-200 bg-white p-3.5">
                        <p class="mb-2 text-[10px] font-extrabold uppercase tracking-wider text-blue-800">Individual 2 (Existing Record)</p>
                        <p class="text-sm font-bold text-gray-900">{{ $flag->matchedBeneficiary?->full_name }}</p>
                        <p class="text-xs text-gray-600">DOB: {{ $flag->matchedBeneficiary?->date_of_birth?->format('M d, Y') ?? '—' }}</p>
                        <p class="text-xs text-gray-600">Address: {{ $flag->matchedBeneficiary?->address ?? '(not specified)' }}</p>
                        <p class="text-xs text-gray-600">Barangay: {{ $flag->matchedBeneficiary?->barangay }}</p>
                        <p class="text-xs text-gray-600">Gov ID: {{ $flag->matchedBeneficiary?->government_id_number ?? '—' }}</p>
                    </div>
                </div>
            @endif

            {{-- Matched Fields Breakdown --}}
            @if(!empty($flag->matched_fields))
                <div class="mt-4 rounded-xl bg-gray-50 p-3 text-xs text-gray-600 space-y-1.5">
                    <p class="font-semibold text-gray-700">{{ $flag->household_match_flag ? 'Household Detection Details:' : 'Scoring Breakdown:' }}</p>
                    @foreach($flag->matched_fields as $key => $detail)
                        @if($key === 'cross_program')
                            @foreach(explode(' | ', $detail) as $conflict)
                                <p class="flex items-start gap-2 rounded-lg bg-red-50 border border-red-200 p-2 text-red-900 font-bold">
                                    <svg class="h-4 w-4 shrink-0 text-red-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                    {{ $conflict }}
                                </p>
                            @endforeach
                        @elseif($key === 'same_program')
                            <p class="flex items-start gap-2 rounded-lg bg-amber-50 border border-amber-200 p-2 text-amber-900 font-bold">
                                <svg class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $detail }}
                            </p>
                        @elseif(str_starts_with($key, 'household_'))
                            <p class="flex items-start gap-2 rounded-lg bg-blue-50 border border-blue-200 p-2 text-blue-900 font-bold">
                                <svg class="h-4 w-4 shrink-0 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205l3 1m1.5.5l-1.5-.5M6.75 7.364V3h-3v18m3-13.636l10.5-3.819"/></svg>
                                {{ $key === 'household_address' ? $flag->getHouseholdAddressDetail() : $detail }}
                            </p>
                        @else
                            <p class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                {{ $detail }}
                            </p>
                        @endif
                    @endforeach
                </div>
            @endif

            @if($flag->remarks)
                <p class="mt-3 text-xs text-gray-500 italic">
                    Resolution Remarks (by {{ $flag->reviewer?->name ?? 'System' }}): {{ $flag->remarks }}
                </p>
            @endif
        </div>
    @empty
        <div class="rounded-2xl border border-gray-200/60 bg-white p-12 text-center text-gray-400">
            No duplicate flags found.
        </div>
    @endforelse

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $flags->links() }}
    </div>

    {{-- Resolve Modal --}}
    <div x-show="resolveModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm" style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl" @click.away="resolveModal = false">
            <h3 class="mb-2 text-lg font-bold text-gray-900" x-text="activeFlag?.household_match_flag ? 'Resolve Household Flag' : 'Resolve Duplicate Flag'"></h3>
            <p class="mb-4 text-xs text-gray-500" x-text="'Resolving flag for ' + (activeFlag?.beneficiary?.full_name ?? '')"></p>

            <template x-if="activeFlag?.household_match_flag">
                <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 p-3 text-xs text-blue-900 font-semibold">
                    This is a <strong>Household Verification</strong> flag. If confirmed as separate households (different Sitio/House/Street),
                    select "Approve" and provide proof details in Remarks.
                </div>
            </template>

            <form :action="'{{ url('duplicates') }}/' + activeFlag?.id + '/resolve'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">Decision *</label>
                    <select name="status" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                        <option value="overridden">Approve & Keep Record (Override Warning)</option>
                        <option value="resolved_duplicate">Confirm True Duplicate / Same Household (Reject)</option>
                        <option value="resolved_not_duplicate">Not a Duplicate / Separate Household (Verified)</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">
                        Remarks / Justification * <span class="text-gray-400" x-show="activeFlag?.household_match_flag">(e.g., "Verified separate households - Sitio A vs Sitio B")</span>
                    </label>
                    <textarea name="remarks" required rows="3" placeholder="State justification for resolution..."
                              class="w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-dole-blue focus:outline-none"></textarea>
                </div>

                <div class="flex justify-end gap-2 border-t pt-4">
                    <button type="button" @click="resolveModal = false" class="rounded-lg border px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-100">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-lg bg-dole-blue px-5 py-2 text-xs font-semibold text-white hover:bg-dole-blue-dark">
                        Submit Decision
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
