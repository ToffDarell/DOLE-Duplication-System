@extends('layouts.app')
@section('title', 'Import Data')
@section('page-title', 'Import Beneficiary Masterlists')
@section('page-subtitle', 'Upload Excel or CSV files with support for DOLE Program Masterlists')

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Upload Form --}}
    <div class="gov-card p-6" x-data="importPreviewHandler()">
        <h3 class="mb-4 text-base font-extrabold text-slate-900 border-b border-slate-200 pb-2">Upload Masterlist File</h3>

        <form action="{{ route('import.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Target Program *</label>
                <select name="program_code" id="import-program" x-model="programCode" @change="onProgramOrFileChange()" required 
                        class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                    <option value="TUPAD">TUPAD</option>
                    <option value="SPES">SPES</option>
                    <option value="DILP">DILP</option>
                    <option value="GIP">GIP</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Excel / CSV File *</label>
                <input type="file" name="file" id="import-file" accept=".xlsx,.xls,.csv" required @change="onFileSelected($event)"
                       class="w-full rounded-xl border border-slate-300 p-2 text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-700 file:px-4 file:py-1.5 file:text-xs file:font-bold file:text-white hover:file:bg-blue-800 cursor-pointer">
            </div>

            {{-- Scanning Loading State --}}
            <div x-show="isScanning" x-transition class="rounded-xl border border-blue-200 bg-blue-50/90 p-3.5 text-xs text-blue-900 shadow-2xs flex items-center gap-3">
                <svg class="h-5 w-5 animate-spin text-blue-700 shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <div>
                    <p class="font-bold text-blue-950">Scanning Excel Header Structure & Bounds...</p>
                    <p class="text-[11px] text-blue-700 font-medium">Detecting header row, data start line, and footer boundary in memory.</p>
                </div>
            </div>

            {{-- Auto-Detection Verification Box --}}
            <div x-show="previewData !== null" x-transition class="rounded-2xl border-2 border-emerald-500/80 bg-emerald-50/95 p-4 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-emerald-200 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-white shadow-2xs">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                        </span>
                        <span class="font-extrabold text-emerald-950 text-xs tracking-tight">Auto-Detection Verified</span>
                    </div>
                    <span class="rounded-full bg-emerald-200/90 border border-emerald-400/60 px-2.5 py-0.5 text-[10px] font-black text-emerald-900 uppercase tracking-wider"
                          x-text="(previewData?.total_rows_found > 0) ? (previewData.total_rows_found + ' Beneficiaries Found') : 'Rows Auto-Mapped'">
                    </span>
                </div>

                <div class="rounded-xl bg-white p-3 border border-emerald-200 text-xs shadow-2xs">
                    <p class="font-extrabold text-slate-900 flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span>Auto-Detected: Row <span class="text-emerald-700 font-black" x-text="startRow || '1'"></span> to Row <span class="text-emerald-700 font-black" x-text="endRow || '(End of Sheet)'"></span></span>
                    </p>
                    <p class="text-[11px] text-slate-500 font-medium mt-0.5" x-text="'File: ' + (previewData?.filename || '') + (previewData?.header_row && previewData.header_row !== '—' ? ' (Header row: ' + previewData.header_row + ')' : '')"></p>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-800">Start Row *</label>
                        <input type="number" name="start_row" x-model="startRow" min="1" required
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-black text-slate-900 focus:border-emerald-600 focus:outline-none shadow-2xs">
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-800">End Row <span class="text-slate-400 font-medium lowercase">(optional)</span></label>
                        <input type="number" name="end_row" x-model="endRow" min="1" placeholder="Auto (End of Sheet)"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-black text-slate-900 focus:border-emerald-600 focus:outline-none shadow-2xs">
                    </div>
                </div>
                <p class="text-[10px] text-emerald-800 font-medium italic">You can double-check or adjust these row boundaries before starting the import.</p>

                @if($bulkThreshold > 0)
                <div x-show="(previewData?.total_rows_found || 0) >= {{ $bulkThreshold }}"
                     class="rounded-xl border border-amber-300 bg-amber-50 p-2.5 text-[11px] font-semibold text-amber-900 flex items-start gap-2">
                    <svg class="h-4 w-4 shrink-0 text-amber-600 mt-px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span>Large file (≥ {{ number_format($bulkThreshold) }} rows): <span class="font-extrabold">fast bulk mode</span> inserts all rows in one batch, then runs the duplicate engine once at the end. Flagged rows still go to the Duplicate Resolution Console.</span>
                </div>
                @endif
            </div>

            {{-- Initial Info Box (when no file loaded yet) --}}
            <div x-show="!isScanning && previewData === null" class="rounded-xl border border-slate-200 bg-slate-50/80 p-3 text-xs text-slate-700 shadow-2xs">
                <div class="flex items-center gap-1.5 font-extrabold text-slate-800">
                    <svg class="h-4 w-4 shrink-0 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                    </svg>
                    <span>Instant Auto-Detection Enabled</span>
                </div>
                <p class="mt-1 text-[11px] text-slate-600 leading-relaxed font-medium">
                    Select any Excel or CSV file to immediately scan and verify header, start row, and footer boundaries across all programs.
                </p>
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
                    <li>Runs the duplicate engine on every row (batched after insert for large files)</li>
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

@push('scripts')
<script>
    function importPreviewHandler() {
        return {
            programCode: 'TUPAD',
            isScanning: false,
            previewData: null,
            startRow: '',
            endRow: '',
            selectedFile: null,

            onFileSelected(event) {
                const files = event.target.files;
                if (!files || files.length === 0) {
                    this.previewData = null;
                    this.startRow = '';
                    this.endRow = '';
                    this.selectedFile = null;
                    return;
                }
                this.selectedFile = files[0];
                this.triggerScan();
            },

            onProgramOrFileChange() {
                if (this.selectedFile) {
                    this.triggerScan();
                }
            },

            async triggerScan() {
                if (!this.selectedFile) return;

                this.isScanning = true;

                const formData = new FormData();
                formData.append('file', this.selectedFile);
                formData.append('program_code', this.programCode);
                formData.append('_token', '{{ csrf_token() }}');

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                    || document.querySelector('input[name="_token"]')?.value 
                    || '{{ csrf_token() }}';

                try {
                    const response = await fetch('{{ route("import.preview-rows") }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: formData
                    });

                    let data = null;
                    try {
                        const text = await response.text();
                        if (text) {
                            data = JSON.parse(text);
                        }
                    } catch (parseErr) {
                        console.warn('Preview response parse note:', parseErr);
                    }

                    if (data && data.success) {
                        this.previewData = data;
                        this.startRow = data.detected_start_row || 3;
                        this.endRow = data.detected_end_row || '';
                    } else {
                        // Scan couldn't read file (e.g. locked by Excel or format issue)
                        this.previewData = {
                            success: false,
                            total_rows_found: data?.total_rows_found || 0,
                            filename: this.selectedFile.name,
                            header_row: data?.header_row || '—',
                            message: data?.message || 'File locked by Excel or unreadable. Please close Excel.',
                        };
                        this.startRow = data?.detected_start_row || 3;
                        this.endRow = data?.detected_end_row || '';
                    }
                } catch (err) {
                    console.error('Scan preview error:', err);
                    this.previewData = {
                        success: false,
                        total_rows_found: 0,
                        filename: this.selectedFile.name,
                        header_row: '—',
                        message: 'Connection error during preview scan.',
                    };
                    this.startRow = 3;
                    this.endRow = '';
                } finally {
                    this.isScanning = false;
                }
            }
        };
    }
</script>
@endpush
@endsection
