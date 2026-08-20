@extends('layouts.app')
@section('title', 'Import Data')
@section('page-title', 'Import Beneficiary Masterlists')
@section('page-subtitle', 'Upload Excel or CSV files with support for DOLE Annex D format')

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Upload Form --}}
    <div class="gov-card p-6">
        <h3 class="mb-4 text-base font-extrabold text-slate-900 border-b border-slate-200 pb-2">Upload Masterlist File</h3>

        <form action="{{ route('import.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Target Program *</label>
                <select name="program_code" id="import-program" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                    <option value="TUPAD">TUPAD (Annex D Template)</option>
                    <option value="SPES">SPES</option>
                    <option value="DILP">DILP</option>
                    <option value="GIP">GIP</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Data Starting Row *</label>
                <input type="number" name="start_row" value="16" min="1" required
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                <p class="mt-1 text-[11px] font-medium text-slate-500">DOLE Annex D templates start data at row 16. Standard CSV/Excel starts at row 2.</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Excel / CSV File *</label>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                       class="w-full rounded-xl border border-slate-300 p-2 text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-700 file:px-4 file:py-1.5 file:text-xs file:font-bold file:text-white hover:file:bg-blue-800">
            </div>

            <div class="rounded-xl border border-blue-200 bg-blue-50 p-3.5 text-xs text-blue-900 shadow-2xs">
                <p class="font-bold mb-1 flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                    Import Processing Features:
                </p>
                <ul class="list-disc list-inside space-y-0.5 text-slate-700 font-medium pl-1">
                    <li>Normalizes literal "N/A" strings to null</li>
                    <li>Trims all whitespace automatically</li>
                    <li>Computes age dynamically from DOB</li>
                    <li>Runs instant duplicate engine on every row</li>
                </ul>
            </div>

            <button type="submit" id="btn-submit-import"
                    class="w-full rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-extrabold px-5 py-3 text-sm shadow-md transition-all duration-200 cursor-pointer flex items-center justify-center gap-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                Start Import Process
            </button>
        </form>
    </div>

    {{-- Import History Logs --}}
    <div class="gov-card p-6 lg:col-span-2">
        <h3 class="mb-4 text-base font-extrabold text-slate-900 border-b border-slate-200 pb-2">Import Logs & History</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-700">
                    <tr>
                        <th class="px-4 py-3 font-bold">Filename</th>
                        <th class="px-4 py-3 font-bold">Uploaded By</th>
                        <th class="px-4 py-3 font-bold">Imported / Failed</th>
                        <th class="px-4 py-3 font-bold">Status</th>
                        <th class="px-4 py-3 text-right font-bold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs font-medium bg-white">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $log->filename }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="px-4 py-3 font-semibold">
                                <span class="font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">{{ $log->imported_rows }}</span> /
                                <span class="font-extrabold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">{{ $log->failed_rows }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-extrabold uppercase
                                    {{ $log->status === 'completed' ? 'bg-emerald-100 text-emerald-900 border border-emerald-200' : 'bg-amber-100 text-amber-900 border border-amber-200' }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('import.show', $log) }}" class="font-extrabold text-blue-800 hover:text-blue-950 hover:underline">
                                        View Details
                                    </a>
                                    <form action="{{ route('import.destroy', $log) }}" method="POST" data-confirm="Are you sure you want to delete this import history log?" data-confirm-title="Delete Import Log" data-confirm-btn="Yes, Delete Log" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-extrabold text-red-600 hover:text-red-800 hover:underline cursor-pointer">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500 font-bold">No import logs recorded yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
