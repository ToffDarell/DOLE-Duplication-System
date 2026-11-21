@extends('layouts.app')
@section('title', 'Create DILP Project')
@section('page-title', 'Create DILP Livelihood Project')
@section('page-subtitle', 'Register a Group (Association) or Individual DILP grant with Amount Grant and DA / ACP implementation mode')

@section('content')
<div class="mx-auto max-w-2xl pt-2" x-data="{
    projectType: '{{ old('project_type', $defaultType ?? 'group') }}',
    mode: '{{ old('implementation_mode', 'DA') }}'
}">
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
        <form action="{{ route('dilp.projects.store') }}" method="POST" class="space-y-4">
            @csrf

            {{-- Category Selector: Group vs Individual --}}
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-700">DILP Category *</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-xs font-extrabold transition"
                           :class="projectType === 'group' ? 'border-blue-700 bg-blue-50 text-blue-900 ring-1 ring-blue-700/20' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'">
                        <input type="radio" name="project_type" value="group" x-model="projectType" class="sr-only">
                        <span>Group (Association)</span>
                    </label>
                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-xs font-extrabold transition"
                           :class="projectType === 'individual' ? 'border-emerald-700 bg-emerald-50 text-emerald-900 ring-1 ring-emerald-700/20' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'">
                        <input type="radio" name="project_type" value="individual" x-model="projectType" class="sr-only">
                        <span>Individual</span>
                    </label>
                </div>
            </div>

            {{-- Group Association Selector --}}
            <div x-show="projectType === 'group'">
                <label class="mb-1 block text-xs font-bold text-slate-700">Association / Group *</label>
                <select name="dilp_group_id" class="h-10 w-full rounded-xl border @error('dilp_group_id') border-rose-500 @else border-slate-300 @enderror bg-white px-3 text-sm font-medium text-slate-900 focus:border-blue-700 focus:outline-none">
                    <option value="">Select association / cooperative...</option>
                    @foreach($groups as $grp)
                        <option value="{{ $grp->id }}" {{ old('dilp_group_id') == $grp->id ? 'selected' : '' }}>{{ $grp->group_name }}</option>
                    @endforeach
                </select>
                @error('dilp_group_id')
                    <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Individual Grantee Name --}}
            <div x-show="projectType === 'individual'" style="display: none;" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Individual Name *</label>
                    <input type="text" name="beneficiary_name" value="{{ old('beneficiary_name') }}" maxlength="200"
                           placeholder="e.g. Dela Cruz, Juan"
                           class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm font-medium text-slate-900 focus:border-blue-700 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Link Existing Beneficiary (Optional)</label>
                    <select name="beneficiary_id" class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-xs font-medium text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="">None / Manual Name</option>
                        @foreach($beneficiaries as $b)
                            <option value="{{ $b->id }}" {{ old('beneficiary_id') == $b->id ? 'selected' : '' }}>{{ $b->full_name }} ({{ $b->municipality }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Project Name --}}
            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">Project Name *</label>
                <input type="text" name="project_name" value="{{ old('project_name') }}" required maxlength="200"
                       placeholder="e.g. Rice Retailing Livelihood Starter Kit"
                       class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm font-medium text-slate-900 focus:border-blue-700 focus:outline-none">
                @error('project_name')
                    <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Amount Grant + Implementation Mode (DA / ACP) --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Amount Grant (&#8369;)</label>
                    <input type="number" name="grant_amount" value="{{ old('grant_amount') }}" step="0.01" min="0"
                           placeholder="e.g. 500000.00"
                           class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm font-bold text-slate-900 tabular-nums focus:border-blue-700 focus:outline-none">
                    @error('grant_amount')
                        <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Implementation Mode (DA / ACP)</label>
                    <select name="implementation_mode" x-model="mode"
                            class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                        <option value="DA">Direct Admin (DA)</option>
                        <option value="ACP">Accredited Co-Partner (ACP)</option>
                    </select>
                </div>
            </div>

            {{-- Optional ACP Name when ACP mode selected --}}
            <div x-show="mode === 'ACP'" style="display: none;">
                <label class="mb-1 block text-xs font-bold text-slate-700">Accredited Co-Partner (ACP) Name</label>
                <input type="text" name="acp_name" value="{{ old('acp_name') }}" maxlength="200"
                       placeholder="e.g. LGU Valencia City / Bukidnon Cooperative"
                       class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm font-medium text-slate-900 focus:border-blue-700 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-blue-700 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">End Date</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm focus:border-blue-700 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold text-slate-700">Liquidation Status *</label>
                <select name="liquidation_status" required class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium focus:border-blue-700 focus:outline-none">
                    <option value="pending" {{ old('liquidation_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="partial" {{ old('liquidation_status') == 'partial' ? 'selected' : '' }}>Partial Liquidation</option>
                    <option value="liquidated" {{ old('liquidation_status') == 'liquidated' ? 'selected' : '' }}>Fully Liquidated</option>
                </select>
                @error('liquidation_status')
                    <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                <a href="{{ route('dilp.projects.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">Cancel</a>
                <button type="submit" class="rounded-xl bg-blue-800 px-5 py-2 text-xs font-extrabold text-white shadow-xs hover:bg-blue-900 transition cursor-pointer">Save DILP Project</button>
            </div>
        </form>
    </div>
</div>
@endsection
