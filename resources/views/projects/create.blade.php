@extends('layouts.app')
@section('title', 'Create New Project')
@section('page-title', 'Create New Project')
@section('page-subtitle', 'Establish a new DOLE program project to organize and track beneficiary batches')

@section('content')
<div class="mb-5">
    <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-blue-800 hover:text-blue-900 transition">
        &larr; Back to Projects Directory
    </a>
</div>

<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route('projects.store') }}" class="gov-card p-6 sm:p-8" x-data="{
        selectedProgId: '{{ old('program_id', $selectedProgramId ?? $programs->first()?->id) }}',
        year: '{{ old('year', date('Y')) }}'
    }">
        @csrf

        {{-- Form Header --}}
        <div class="mb-6 flex items-center gap-3 border-b border-slate-200 pb-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-800 text-white shadow-xs font-black text-base">
                +
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900 tracking-tight">Project Identification & Setup</h3>
                <p class="text-xs font-medium text-slate-500">All fields marked with an asterisk (*) are required by DOLE record guidelines</p>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-800 flex items-start gap-3">
                <svg class="h-5 w-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="font-bold">Please correct the following errors:</p>
                    <ul class="list-disc pl-4 mt-1 space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Program Selection Radio Cards --}}
        <div class="mb-6">
            <label class="mb-2 block text-xs font-extrabold uppercase tracking-wider text-slate-700">
                1. Target Program *
            </label>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach($programs as $prog)
                    @php
                        $activeClasses = match($prog->code) {
                            'TUPAD' => 'border-blue-700 bg-blue-50/90 text-blue-900 ring-2 ring-blue-500/20 shadow-xs',
                            'SPES' => 'border-emerald-600 bg-emerald-50/90 text-emerald-900 ring-2 ring-emerald-500/20 shadow-xs',
                            'DILP' => 'border-amber-600 bg-amber-50/90 text-amber-900 ring-2 ring-amber-500/20 shadow-xs',
                            'GIP' => 'border-purple-700 bg-purple-50/90 text-purple-900 ring-2 ring-purple-500/20 shadow-xs',
                            default => 'border-blue-700 bg-blue-50 text-blue-900',
                        };
                    @endphp
                    <label class="relative flex cursor-pointer flex-col rounded-xl border-2 p-3.5 transition-all text-center select-none"
                           :class="selectedProgId == '{{ $prog->id }}' ? '{{ $activeClasses }}' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50 text-slate-700'">
                        <input type="radio" name="program_id" value="{{ $prog->id }}" x-model="selectedProgId" required class="sr-only">
                        <span class="text-base font-black tracking-tight block">{{ $prog->code }}</span>
                        <span class="text-[10px] font-semibold text-slate-500 line-clamp-1 mt-0.5">{{ $prog->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Project Name & Type of Implementation --}}
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-bold text-slate-700">
                    2. Project / Implementation Name *
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="200"
                       placeholder="e.g. TUPAD Valencia Batch 1"
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 placeholder-slate-400 focus:border-blue-800 focus:outline-none focus:ring-1 focus:ring-blue-800">
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-700">
                    Type of Implementation
                </label>
                <input type="text" name="implementation_type" value="{{ old('implementation_type') }}" maxlength="150"
                       list="implementation-types-list"
                       placeholder="e.g. Direct Implementation"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-900 placeholder-slate-400 focus:border-blue-800 focus:outline-none focus:ring-1 focus:ring-blue-800">
                <datalist id="implementation-types-list">
                    @foreach($implementationTypes as $type)
                        <option value="{{ $type }}"></option>
                    @endforeach
                </datalist>
            </div>
        </div>

        {{-- Municipality, Target Beneficiaries & Year Grid --}}
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-700">
                    Location / Municipality
                </label>
                <select name="municipality"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:border-blue-800 focus:outline-none focus:ring-1 focus:ring-blue-800">
                    <option value="">&mdash; Select Municipality &mdash;</option>
                    @foreach($municipalities as $mun)
                        <option value="{{ $mun }}" @selected(old('municipality') === $mun)>{{ $mun }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-700">
                    Target Beneficiaries
                </label>
                <input type="number" name="target_beneficiaries" value="{{ old('target_beneficiaries') }}" min="1" max="100000"
                       placeholder="e.g. 500"
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-900 tabular-nums placeholder-slate-400 focus:border-blue-800 focus:outline-none focus:ring-1 focus:ring-blue-800">
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-700">
                    Implementation Year (CY) *
                </label>
                <input type="number" name="year" x-model="year" min="2000" max="2100" required
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-900 tabular-nums focus:border-blue-800 focus:outline-none focus:ring-1 focus:ring-blue-800">
            </div>
        </div>

        {{-- Dates Grid --}}
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-700">
                    Date of Implementation (Start)
                </label>
                <input type="date" name="start_date" value="{{ old('start_date') }}"
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-medium text-slate-900 focus:border-blue-800 focus:outline-none focus:ring-1 focus:ring-blue-800">
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-700">
                    End Date <span class="text-slate-400 font-normal">(optional)</span>
                </label>
                <input type="date" name="end_date" value="{{ old('end_date') }}"
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-medium text-slate-900 focus:border-blue-800 focus:outline-none focus:ring-1 focus:ring-blue-800">
            </div>
        </div>

        {{-- Project Description / Notes --}}
        <div class="mb-6">
            <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-wider text-slate-700">
                Project Scope & Details
            </label>
            <textarea name="description" rows="3"
                      placeholder="Brief description of work scope, project objectives, or budget allocation notes..."
                      class="w-full rounded-xl border border-slate-300 bg-slate-50/50 p-3.5 text-xs font-medium text-slate-900 placeholder-slate-400 focus:border-blue-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-800">{{ old('description') }}</textarea>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
            <a href="{{ route('projects.index') }}"
               class="rounded-xl border border-slate-300 bg-white px-4.5 py-2.5 text-xs font-extrabold text-slate-700 hover:bg-slate-100 transition">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-xl bg-blue-800 px-6 py-2.5 text-xs font-extrabold text-white shadow-md hover:bg-blue-900 focus:ring-2 focus:ring-blue-800/20 transition">
                Save &amp; Create Project
            </button>
        </div>
    </form>
</div>
@endsection
