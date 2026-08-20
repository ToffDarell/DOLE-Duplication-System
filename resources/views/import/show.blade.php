@extends('layouts.app')
@section('title', 'Import Details')
@section('page-title', 'Import Log Details')
@section('page-subtitle', 'Summary and row-level error breakdown for file import')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('import.index') }}" class="inline-flex items-center gap-2 text-sm font-extrabold text-blue-800 hover:text-blue-950 transition">
        ← Back to Import Dashboard
    </a>
    <form action="{{ route('import.destroy', $importLog) }}" method="POST" data-confirm="Are you sure you want to delete this import history log?" data-confirm-title="Delete Import Log" data-confirm-btn="Yes, Delete Log">
        @csrf
        @method('DELETE')
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-extrabold px-4 py-2 shadow-2xs transition cursor-pointer">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
            Delete Import Log
        </button>
    </form>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Summary Card --}}
    <div class="gov-card p-6">
        <h3 class="mb-4 text-xs font-extrabold uppercase tracking-wider text-slate-700 border-b border-slate-200 pb-2">Import Summary</h3>
        <div class="space-y-3 text-xs font-medium">
            <div><span class="text-slate-500">File Name:</span> <span class="font-bold text-slate-900 block truncate">{{ $importLog->filename }}</span></div>
            <div><span class="text-slate-500">Uploaded By:</span> <span class="font-semibold text-slate-800">{{ $importLog->user?->name ?? 'System' }}</span></div>
            <div><span class="text-slate-500">Date:</span> <span class="font-semibold text-slate-800">{{ $importLog->created_at?->format('M d, Y h:i A') }}</span></div>
            <div><span class="text-slate-500">Total Processed Rows:</span> <span class="font-extrabold text-slate-900">{{ $importLog->total_rows }}</span></div>
            <div><span class="text-slate-500">Successfully Imported:</span> <span class="font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">{{ $importLog->imported_rows }}</span></div>
            <div><span class="text-slate-500">Failed / Errors:</span> <span class="font-extrabold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">{{ $importLog->failed_rows }}</span></div>
            <div>
                <span class="text-slate-500">Status:</span>
                <span class="inline-block mt-1 rounded-full px-2.5 py-0.5 text-[10px] font-extrabold uppercase
                    {{ $importLog->status === 'completed' ? 'bg-emerald-100 text-emerald-900 border border-emerald-200' : 'bg-amber-100 text-amber-900 border border-amber-200' }}">
                    {{ $importLog->status }}
                </span>
            </div>
        </div>
    </div>

    {{-- Error Log / Info Details --}}
    <div class="gov-card p-6 lg:col-span-2">
        <h3 class="mb-4 text-xs font-extrabold uppercase tracking-wider text-slate-700 border-b border-slate-200 pb-2">Log Breakdown & Diagnostics</h3>
        @if(empty($importLog->error_log))
            <p class="py-8 text-center text-xs font-bold text-slate-500">No row errors recorded during this import!</p>
        @else
            <div class="space-y-3">
                @foreach($importLog->error_log as $err)
                    @if(isset($err['info']))
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-xs text-blue-900 shadow-2xs">
                            <p class="font-bold flex items-center gap-1.5 mb-1.5">
                                <svg class="h-4 w-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                                Resolved Header Column Mapping:
                            </p>
                            <p class="font-mono text-[11px] text-blue-800 bg-blue-100/70 p-2 rounded border border-blue-200 overflow-x-auto">{{ $err['info'] }}</p>
                            @if(isset($err['skipped_footer_rows']) && $err['skipped_footer_rows'] > 0)
                                <p class="mt-2 font-bold text-blue-800 flex items-center gap-1">
                                    <span>✓ Skipped {{ $err['skipped_footer_rows'] }} legend/footer note block(s) at bottom of document.</span>
                                </p>
                            @endif
                        </div>
                    @elseif(isset($err['error']))
                        <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs text-rose-900 shadow-2xs">
                            <span class="rounded-lg bg-rose-200/90 px-2.5 py-1 font-extrabold text-rose-900 shrink-0">Row {{ $err['row'] ?? '?' }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="font-extrabold text-slate-900">{{ $err['name'] ?? 'Record' }}</p>
                                <p class="mt-0.5 text-rose-700 font-semibold">{{ $err['error'] }}</p>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
