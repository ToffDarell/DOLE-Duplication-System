@extends('layouts.app')
@section('title', 'Edit DILP Project')
@section('page-title', 'Edit DILP Project')

@section('content')
<div class="mx-auto max-w-lg pt-4">
    <div class="rounded-2xl border border-gray-200/60 bg-white p-6 shadow-sm">
        <form action="{{ route('dilp.projects.update', $project) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="mb-1 block text-xs font-bold text-gray-700">Project Name *</label>
                <input type="text" name="project_name" value="{{ old('project_name', $project->project_name) }}" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                @error('project_name')
                    <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-gray-700">Associated Group (Optional)</label>
                <select name="dilp_group_id" class="w-full rounded-xl border @error('dilp_group_id') border-red-500 @else border-gray-300 @enderror px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                    <option value="">Individual / None</option>
                    @foreach($groups as $grp)
                        <option value="{{ $grp->id }}" {{ old('dilp_group_id', $project->dilp_group_id) == $grp->id ? 'selected' : '' }}>{{ $grp->group_name }}</option>
                    @endforeach
                </select>
                @error('dilp_group_id')
                    <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-700">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-700">End Date</label>
                    <input type="date" name="end_date" value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-gray-700">Liquidation Status *</label>
                <select name="liquidation_status" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                    <option value="pending" {{ old('liquidation_status', $project->liquidation_status) === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="partial" {{ old('liquidation_status', $project->liquidation_status) === 'partial' ? 'selected' : '' }}>Partial Liquidation</option>
                    <option value="liquidated" {{ old('liquidation_status', $project->liquidation_status) === 'liquidated' ? 'selected' : '' }}>Fully Liquidated</option>
                </select>
                @error('liquidation_status')
                    <p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('dilp.projects.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">Cancel</a>
                <button type="submit" class="rounded-xl bg-dole-blue px-5 py-2 text-sm font-bold text-white shadow-sm hover:bg-dole-blue-dark transition cursor-pointer">Update Project</button>
            </div>
        </form>
    </div>
</div>
@endsection
