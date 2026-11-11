@extends('layouts.app')
@section('title', 'Import Data')
@section('page-title', 'Import Beneficiary Masterlists')
@section('page-subtitle', 'Upload Excel or CSV files with support for DOLE Program Masterlists')

@section('content')
<div class="space-y-6">
    {{-- Primary Focal Point: Upload & Project Assignment Workbench --}}
    <div class="gov-card p-6" x-data="importPreviewHandler({
        projects: @js($projectOptions),
        initialChosen: '{{ $preselectedProject?->id }}',
        initialProgram: '{{ $preselectedProject?->program?->code ?? 'TUPAD' }}'
    })">
        <div class="mb-5 flex flex-col gap-1 border-b border-slate-200/80 pb-3.5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Upload Masterlist & Assign Project</h3>
                <p class="text-xs font-medium text-slate-500">Select your program and Excel/CSV file, then assign it to a new or existing implementation.</p>
            </div>
            <span class="inline-flex items-center gap-1.5 self-start rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-semibold text-slate-600 sm:self-auto">
                <svg class="h-3.5 w-3.5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                </svg>
                Instant Row & Filename Auto-Detection
            </span>
        </div>

        <form action="{{ route('import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 items-start">
                {{-- Left Column (5 cols): Step 1 — Program, File & Row Verification --}}
                <div class="lg:col-span-5 space-y-4">
                    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-3">
                        <div class="sm:col-span-1 flex flex-col justify-end">
                            <label for="import-program" class="mb-1.5 block text-xs font-bold text-slate-700 truncate">Target Program *</label>
                            <select name="program_code" id="import-program" x-model="programCode" @change="onProgramOrFileChange()" required 
                                    class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                                <option value="TUPAD">TUPAD</option>
                                <option value="SPES">SPES</option>
                                <option value="DILP">DILP</option>
                                <option value="GIP">GIP</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2 flex flex-col justify-end">
                            <label for="import-file" class="mb-1.5 block text-xs font-bold text-slate-700 truncate">Excel / CSV Masterlist File *</label>
                            <input type="file" name="file" id="import-file" accept=".xlsx,.xls,.csv" required @change="onFileSelected($event)"
                                   class="h-10 w-full rounded-xl border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-700 file:px-3 file:py-1 file:text-xs file:font-bold file:text-white hover:file:bg-blue-800 cursor-pointer">
                        </div>
                    </div>

                    {{-- Scanning Loading State --}}
                    <div x-show="isScanning" x-transition class="rounded-xl border border-blue-200 bg-blue-50/90 p-3.5 text-xs text-blue-900 flex items-center gap-3">
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
                    <div x-show="previewData !== null" x-transition class="rounded-xl border border-emerald-300 bg-emerald-50/80 p-4 space-y-3">
                        <div class="flex items-center justify-between border-b border-emerald-200/80 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-white">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                </span>
                                <span class="font-extrabold text-emerald-950 text-xs">Auto-Detection Verified</span>
                            </div>
                            <span class="rounded-full bg-emerald-100 border border-emerald-300 px-2.5 py-0.5 text-[10px] font-extrabold text-emerald-900 tabular-nums"
                                  x-text="(previewData?.total_rows_found > 0) ? (previewData.total_rows_found + ' Beneficiaries Found') : 'Rows Auto-Mapped'">
                            </span>
                        </div>

                        <div class="rounded-lg bg-white p-2.5 border border-emerald-200/80 text-xs">
                            <p class="font-bold text-slate-900 flex items-center gap-1.5 tabular-nums">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span>Rows <span class="text-emerald-700 font-extrabold" x-text="startRow || '1'"></span> to <span class="text-emerald-700 font-extrabold" x-text="endRow || '(End of Sheet)'"></span></span>
                            </p>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5 truncate" x-text="'File: ' + (previewData?.filename || '') + (previewData?.header_row && previewData.header_row !== '—' ? ' (Header row: ' + previewData.header_row + ')' : '')"></p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex flex-col justify-end">
                                <label class="mb-1 block text-[11px] font-bold text-slate-700 truncate">Start Row *</label>
                                <input type="number" name="start_row" x-model="startRow" min="1" required
                                       class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs font-bold text-slate-900 tabular-nums focus:border-emerald-600 focus:outline-none">
                            </div>
                            <div class="flex flex-col justify-end">
                                <label class="mb-1 block text-[11px] font-bold text-slate-700 truncate">End Row <span class="text-slate-400 font-normal">(optional)</span></label>
                                <input type="number" name="end_row" x-model="endRow" min="1" placeholder="Auto (End of Sheet)"
                                       class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs font-bold text-slate-900 tabular-nums focus:border-emerald-600 focus:outline-none">
                            </div>
                        </div>

                        @if($bulkThreshold > 0)
                        <div x-show="(previewData?.total_rows_found || 0) >= {{ $bulkThreshold }}"
                             class="rounded-lg border border-amber-300 bg-amber-50 p-2.5 text-[11px] font-semibold text-amber-900 flex items-start gap-2">
                            <svg class="h-4 w-4 shrink-0 text-amber-600 mt-px" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>Large file (≥ {{ number_format($bulkThreshold) }} rows): <span class="font-extrabold">fast bulk mode</span> inserts rows in batch and runs duplicate detection at the end.</span>
                        </div>
                        @endif
                    </div>

                    {{-- Idle Helper (when no file selected yet) --}}
                    <div x-show="!isScanning && previewData === null" class="rounded-xl border border-dashed border-slate-300 bg-slate-50/60 p-4 text-xs text-slate-600">
                        <p class="font-bold text-slate-800">Ready to Scan Masterlist</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                            Once you choose an Excel or CSV file above, the system automatically scans the header structure, counts the beneficiaries, and fills in the project name from the filename.
                        </p>
                    </div>
                </div>

                {{-- Right Column (7 cols): Step 2 — Project / Implementation Details --}}
                <div class="lg:col-span-7 rounded-xl border border-slate-200/90 bg-slate-50/70 p-4 space-y-3.5">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-slate-700">Project / Implementation Assignment</label>
                        <select name="project_id" x-model="chosen"
                                class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                            <option value="">+ Create a new project from this upload...</option>
                            <template x-for="p in filteredProjects" :key="p.id">
                                <option :value="p.id" x-text="p.name + (p.year ? ' (' + p.year + ')' : '')" :selected="String(p.id) === String(chosen)"></option>
                            </template>
                        </select>
                    </div>

                    {{-- New Project Metadata Fields --}}
                    <div x-show="!chosen" class="space-y-3.5 pt-0.5">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="flex flex-col justify-end">
                                <div class="mb-1.5 flex items-center justify-between gap-2">
                                    <label for="new-project-name" class="block text-xs font-bold text-slate-700 truncate">Project / Implementation Name</label>
                                    <span x-show="autoFilledFromFilename" class="shrink-0 rounded border border-blue-200 bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-700">Auto-filled</span>
                                </div>
                                <input type="text" name="new_project_name" id="new-project-name" maxlength="200"
                                       x-model="newProjectName"
                                       @input="userEditedProjectName = true; autoFilledFromFilename = false"
                                       placeholder="e.g. TUPAD Valencia Batch 1"
                                       class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-xs font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                            </div>

                            <div class="flex flex-col justify-end">
                                <label class="mb-1.5 block text-xs font-bold text-slate-700 truncate">Type of Implementation</label>
                                <input type="text" name="implementation_type" maxlength="150"
                                       list="import-implementation-types"
                                       placeholder="e.g. Direct Implementation, ACP / Co-Partner"
                                       class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-xs font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                                <datalist id="import-implementation-types">
                                    @foreach($implementationTypes ?? [] as $type)
                                        <option value="{{ $type }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="flex flex-col justify-end min-w-0">
                                <label class="mb-1.5 block text-xs font-bold text-slate-700 truncate">Municipality</label>
                                <select name="municipality"
                                        class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-900 focus:border-blue-700 focus:outline-none">
                                    <option value="">Select town...</option>
                                    @foreach($municipalities ?? [] as $mun)
                                        <option value="{{ $mun }}">{{ $mun }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex flex-col justify-end min-w-0">
                                <label class="mb-1.5 block text-xs font-bold text-slate-700 truncate">Target Beneficiaries</label>
                                <input type="number" name="target_beneficiaries" x-model="targetBeneficiaries" min="1" max="100000"
                                       placeholder="e.g. 500"
                                       class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-xs font-bold text-slate-900 tabular-nums focus:border-blue-700 focus:outline-none">
                            </div>

                            <div class="flex flex-col justify-end min-w-0">
                                <label class="mb-1.5 block text-xs font-bold text-slate-700 truncate">Start Date</label>
                                <input type="date" name="start_date"
                                       class="h-10 w-full rounded-xl border border-slate-300 bg-white px-2.5 text-xs font-medium text-slate-900 tabular-nums focus:border-blue-700 focus:outline-none">
                            </div>

                            <div class="flex flex-col justify-end min-w-0">
                                <label class="mb-1.5 block text-xs font-bold text-slate-700 truncate">End Date</label>
                                <input type="date" name="end_date"
                                       class="h-10 w-full rounded-xl border border-slate-300 bg-white px-2.5 text-xs font-medium text-slate-900 tabular-nums focus:border-blue-700 focus:outline-none">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Workbench Footer Bar --}}
            <div class="mt-5 flex flex-col gap-3 border-t border-slate-200/80 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs font-medium text-slate-500">
                    Automatically trims whitespace, normalizes <span class="font-semibold text-slate-700">"N/A"</span> values, computes age from birthdate, and runs the duplicate detection engine.
                </p>
                <button type="submit" id="btn-submit-import"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-700 px-6 py-2.5 text-sm font-extrabold text-white shadow-xs transition hover:bg-blue-800 cursor-pointer">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                    Start Import Process
                </button>
            </div>
        </form>
    </div>

    {{-- Secondary Section: Full-Width Import History Logs (No vertical stretch / empty space) --}}
    <div class="gov-card p-6">
        <div class="mb-4 flex items-center justify-between border-b border-slate-200/80 pb-3">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Import Logs & History</h3>
                <p class="text-xs font-medium text-slate-500">Audit trail of uploaded Excel and CSV masterlist batches</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-bold">Filename</th>
                        <th class="px-4 py-3 font-bold">Uploaded By</th>
                        <th class="px-4 py-3 font-bold">Imported / Failed</th>
                        <th class="px-4 py-3 font-bold">Status</th>
                        <th class="px-4 py-3 text-right font-bold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 text-xs font-medium bg-white">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $log->filename }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="px-4 py-3 font-semibold tabular-nums">
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
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500 font-semibold">No import logs recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    function importPreviewHandler(config = {}) {
        return {
            programCode: config.initialProgram || 'TUPAD',
            projects: config.projects || [],
            chosen: config.initialChosen || '',
            newProjectName: '',
            userEditedProjectName: false,
            autoFilledFromFilename: false,
            targetBeneficiaries: '',
            isScanning: false,
            previewData: null,
            startRow: '',
            endRow: '',
            selectedFile: null,

            get filteredProjects() {
                return this.projects.filter(p => p.program === this.programCode);
            },

            extractProjectNameFromFilename(filename) {
                if (!filename) return '';
                const withoutExt = filename.replace(/\.(xlsx|xls|csv)$/i, '');
                return withoutExt.replace(/[_]+/g, ' ').replace(/\s+/g, ' ').trim();
            },

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

                // Auto-populate project name from filename if user hasn't manually typed a custom name
                if (!this.chosen && (!this.userEditedProjectName || !this.newProjectName.trim())) {
                    const derivedName = this.extractProjectNameFromFilename(this.selectedFile.name);
                    if (derivedName) {
                        this.newProjectName = derivedName;
                        this.autoFilledFromFilename = true;
                    }
                }

                this.triggerScan();
            },

            onProgramOrFileChange() {
                if (this.chosen) {
                    const stillMatches = this.filteredProjects.some(p => String(p.id) === String(this.chosen));
                    if (!stillMatches) {
                        this.chosen = '';
                    }
                }
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
                        if (!this.targetBeneficiaries && data.total_rows_found > 0) {
                            this.targetBeneficiaries = data.total_rows_found;
                        }
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
