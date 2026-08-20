@extends('layouts.app')
@section('title', 'System Settings')
@section('page-title', 'System Settings & Engine Configuration')
@section('page-subtitle', 'Configure system identity, duplicate detection parameters, import defaults, and program rules')

@section('content')
<div class="max-w-4xl mx-auto">
    <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
        @csrf

        {{-- 1. Agency & System Identity --}}
        <div class="gov-card p-6">
            <div class="flex items-center gap-3 border-b border-slate-200 pb-3 mb-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-800 font-bold shadow-2xs">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0V5"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Agency & Portal Identity</h3>
                    <p class="text-xs text-slate-500 font-semibold">Official organizational branding and title displayed across the system</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Agency / Office Title *</label>
                    <input type="text" name="agency_name" value="{{ old('agency_name', $settings['agency_name']) }}" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-800 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">System Brand Title *</label>
                    <input type="text" name="system_title" value="{{ old('system_title', $settings['system_title']) }}" required
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-800 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- 2. Duplicate Detection Engine Config --}}
        <div class="gov-card p-6">
            <div class="flex items-center gap-3 border-b border-slate-200 pb-3 mb-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-800 font-bold shadow-2xs">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Duplicate Engine Parameters</h3>
                    <p class="text-xs text-slate-500 font-semibold">Fine-tune matching sensitivity score and cross-check validation rules</p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-700">Minimum Duplicate Sensitivity Score (%) *</label>
                        <span class="text-xs font-extrabold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded" id="threshold-val">{{ $settings['duplicate_threshold'] }}% Match</span>
                    </div>
                    <input type="range" name="duplicate_threshold" min="50" max="100" step="5" value="{{ old('duplicate_threshold', $settings['duplicate_threshold']) }}"
                           oninput="document.getElementById('threshold-val').textContent = this.value + '% Match'"
                           class="w-full accent-blue-800 cursor-pointer">
                    <p class="mt-1 text-[11px] font-semibold text-slate-500">Flags any potential duplicate whose similarity calculation equals or exceeds this score percentage.</p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 pt-2">
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3.5 transition hover:bg-slate-50 cursor-pointer">
                        <input type="checkbox" name="enable_exact_dob_check" value="1" {{ $settings['enable_exact_dob_check'] == '1' ? 'checked' : '' }}
                               class="rounded border-slate-300 text-blue-800 focus:ring-blue-500/20 cursor-pointer">
                        <div>
                            <span class="block text-xs font-extrabold text-slate-900">Cross-Check Name + Exact DOB</span>
                            <span class="block text-[11px] font-semibold text-slate-500">Auto-flag matching full names sharing identical Date of Birth</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3.5 transition hover:bg-slate-50 cursor-pointer">
                        <input type="checkbox" name="enable_gov_id_check" value="1" {{ $settings['enable_gov_id_check'] == '1' ? 'checked' : '' }}
                               class="rounded border-slate-300 text-blue-800 focus:ring-blue-500/20 cursor-pointer">
                        <div>
                            <span class="block text-xs font-extrabold text-slate-900">Strict Government ID Check</span>
                            <span class="block text-[11px] font-semibold text-slate-500">Auto-flag matching Government ID numbers across programs</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- 3. Import & Program Rules --}}
        <div class="gov-card p-6">
            <div class="flex items-center gap-3 border-b border-slate-200 pb-3 mb-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 font-bold shadow-2xs">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Program Rules & Import Defaults</h3>
                    <p class="text-xs text-slate-500 font-semibold">Default Excel masterlist starting row and implementation constraints</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Active Fiscal Year *</label>
                    <input type="number" name="current_fiscal_year" value="{{ old('current_fiscal_year', $settings['current_fiscal_year']) }}" required min="2020" max="2035"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-800 focus:outline-none">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Default Excel Start Row *</label>
                    <input type="number" name="default_import_start_row" value="{{ old('default_import_start_row', $settings['default_import_start_row']) }}" required min="1"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-800 focus:outline-none">
                    <p class="mt-1 text-[11px] font-semibold text-slate-500">Row 16 for DOLE Annex D templates</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">TUPAD Max Work Days *</label>
                    <input type="number" name="tupad_max_days" value="{{ old('tupad_max_days', $settings['tupad_max_days']) }}" required min="1" max="30"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-800 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- 4. Audit & System Maintenance --}}
        <div class="gov-card p-6">
            <div class="flex items-center gap-3 border-b border-slate-200 pb-3 mb-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-800 font-bold shadow-2xs">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Audit Trail & Data Maintenance</h3>
                    <p class="text-xs text-slate-500 font-semibold">Logging policy and data retention duration</p>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Audit Log Retention (Days) *</label>
                <input type="number" name="audit_log_retention_days" value="{{ old('audit_log_retention_days', $settings['audit_log_retention_days']) }}" required min="30" max="3650"
                       class="w-full max-w-xs rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-800 focus:outline-none">
                <p class="mt-1 text-[11px] font-semibold text-slate-500">System automatically archives system activity logs older than specified days.</p>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="rounded-xl bg-blue-800 hover:bg-blue-900 px-6 py-3 text-sm font-extrabold text-white shadow-md transition-all duration-200 cursor-pointer flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                Save System Settings
            </button>
        </div>
    </form>
</div>
@endsection
