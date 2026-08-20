@extends('layouts.app')
@section('title', 'Duplicate Flags')
@section('page-title', 'Duplicate Resolution Console')
@section('page-subtitle', 'Review and resolve flagged duplicate beneficiary records')

@section('content')
{{-- Filter Tabs --}}
<div class="mb-6 flex flex-wrap gap-2 border-b border-gray-200 pb-3" x-data="{ resolveModal: false, activeFlag: null, statusInput: '', remarksInput: '' }">
    <a href="{{ route('duplicates.index') }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ !request('status') ? 'bg-dole-blue text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
        All Flags
    </a>
    <a href="{{ route('duplicates.index', ['status' => 'pending']) }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ request('status') == 'pending' ? 'bg-amber-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
        Pending Review
    </a>
    <a href="{{ route('duplicates.index', ['status' => 'overridden']) }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ request('status') == 'overridden' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
        Overridden / Approved
    </a>
</div>

{{-- Duplicate Flags List --}}
<div class="space-y-4" x-data="{ showResolveModal(flag) { this.activeFlag = flag; this.resolveModal = true; } }">
    @forelse($flags as $flag)
        <div class="rounded-2xl border border-gray-200/60 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 font-bold text-amber-800">
                        {{ $flag->match_score }}%
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-gray-900">{{ $flag->beneficiary?->full_name }}</h4>
                            <span class="text-xs text-gray-400">matches</span>
                            <h4 class="font-bold text-gray-900">{{ $flag->matchedBeneficiary?->full_name }}</h4>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Flag ID #{{ $flag->id }} | Type: <span class="uppercase font-semibold text-amber-700">{{ $flag->match_type }}</span> |
                            Status: <span class="font-semibold text-gray-800 capitalize">{{ str_replace('_', ' ', $flag->status) }}</span>
                        </p>
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

            {{-- Matched Fields Breakdown --}}
            @if(!empty($flag->matched_fields))
                <div class="mt-4 rounded-xl bg-gray-50 p-3 text-xs text-gray-600 space-y-1">
                    <p class="font-semibold text-gray-700">Scoring Breakdown:</p>
                    @foreach($flag->matched_fields as $key => $detail)
                        <p class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            {{ $detail }}
                        </p>
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
            <h3 class="mb-2 text-lg font-bold text-gray-900">Resolve Duplicate Flag</h3>
            <p class="mb-4 text-xs text-gray-500" x-text="'Resolving flag for ' + (activeFlag?.beneficiary?.full_name ?? '')"></p>

            <form :action="'{{ url('duplicates') }}/' + activeFlag?.id + '/resolve'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">Decision *</label>
                    <select name="status" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                        <option value="overridden">Approve & Keep Record (Override Warning)</option>
                        <option value="resolved_duplicate">Confirm True Duplicate (Reject New Record)</option>
                        <option value="resolved_not_duplicate">Not a Duplicate (False Alarm)</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">Remarks / Reason *</label>
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
