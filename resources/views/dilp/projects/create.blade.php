@extends('layouts.app')
@section('title', 'Create DILP Project')
@section('page-title', 'Create DILP Project')

@section('content')
<div class="mx-auto max-w-lg pt-4">
    <div class="rounded-2xl border border-gray-200/60 bg-white p-6 shadow-sm">
        <form action="{{ route('dilp.projects.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700">Project Name *</label>
                <input type="text" name="project_name" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700">Associated Group (Optional)</label>
                <select name="dilp_group_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                    <option value="">Individual / None</option>
                    @foreach($groups as $grp)
                        <option value="{{ $grp->id }}">{{ $grp->group_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">Start Date</label>
                    <input type="date" name="start_date" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">End Date</label>
                    <input type="date" name="end_date" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700">Liquidation Status *</label>
                <select name="liquidation_status" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                    <option value="pending">Pending</option>
                    <option value="partial">Partial Liquidation</option>
                    <option value="liquidated">Fully Liquidated</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('dilp.projects.index') }}" class="rounded-lg border px-4 py-2 text-sm">Cancel</a>
                <button type="submit" class="rounded-lg bg-dole-blue px-5 py-2 text-sm font-semibold text-white">Save Project</button>
            </div>
        </form>
    </div>
</div>
@endsection
