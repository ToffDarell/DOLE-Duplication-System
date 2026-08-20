@extends('layouts.app')
@section('title', 'Analytics & Reporting Dashboard')
@section('page-title', 'System Analytics & Intelligence Dashboard')
@section('page-subtitle', 'Real-time performance metrics, duplicate detection analytics, program allocations, and geographic insights')

@section('content')
{{-- DOLE Official Header Banner --}}
<div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between rounded-2xl border border-blue-900/60 bg-gradient-to-r from-blue-900 via-blue-850 to-slate-900 p-6 text-white shadow-lg relative overflow-hidden">
    <div class="absolute -right-10 -bottom-10 h-44 w-44 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>
    <div class="flex items-center gap-4 relative z-10">
        <div class="rounded-2xl bg-white p-2 shadow-md border border-white/20 shrink-0">
            <img src="{{ asset('dole-logo.png') }}" alt="DOLE Official Logo" class="h-12 w-12 object-contain bg-white">
        </div>
        <div>
            <div class="flex items-center gap-2">
                <span class="rounded-full bg-blue-700/80 border border-blue-400/30 px-2.5 py-0.5 text-[10px] font-extrabold text-blue-100 uppercase tracking-wider">DOLE Bukidnon Field Office</span>
                <span class="rounded-full bg-amber-500/20 border border-amber-400/30 px-2.5 py-0.5 text-[10px] font-extrabold text-amber-300 uppercase tracking-wider">CY {{ date('Y') }} Intelligence</span>
            </div>
            <h2 class="mt-1 text-xl font-black tracking-tight text-white">Centralized Analytics & Duplicate Engine Report</h2>
        </div>
    </div>
    <div class="mt-4 sm:mt-0 text-left sm:text-right relative z-10">
        <p class="text-[11px] font-extrabold text-slate-300 uppercase tracking-wider">Last Sync Time</p>
        <p class="text-sm font-bold text-white tracking-tight">{{ now()->format('F d, Y - h:i A') }}</p>
    </div>
</div>

{{-- Top Summary Cards (KPI Metrics) --}}
<div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    {{-- Card 1: Total Beneficiaries Registered --}}
    <div class="gov-card gov-card-hover p-5 border-l-4 border-l-blue-700">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-700 text-white shadow-xs">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </div>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-[11px] font-extrabold text-blue-900 border border-blue-200">System Total</span>
        </div>
        <p class="text-3xl font-black text-slate-900 tracking-tight">{{ number_format($totalBeneficiaries) }}</p>
        <p class="mt-1 text-xs font-bold text-slate-600">Total Beneficiaries Registered</p>
    </div>

    {{-- Card 2: Total Duplicates Flagged --}}
    <div class="gov-card gov-card-hover p-5 border-l-4 border-l-amber-500">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-xs">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </div>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-extrabold text-amber-900 border border-amber-200">Engine Flagged</span>
        </div>
        <p class="text-3xl font-black text-slate-900 tracking-tight">{{ number_format($totalDuplicates) }}</p>
        <p class="mt-1 text-xs font-bold text-slate-600">Total Duplicates Flagged</p>
    </div>

    {{-- Card 3: Pending Validation Queue Count --}}
    <div class="gov-card gov-card-hover p-5 border-l-4 border-l-rose-600">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-600 text-white shadow-xs">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="rounded-full bg-rose-100 px-3 py-1 text-[11px] font-extrabold text-rose-900 border border-rose-200">Action Required</span>
        </div>
        <p class="text-3xl font-black text-slate-900 tracking-tight">{{ number_format($pendingDuplicates) }}</p>
        <p class="mt-1 text-xs font-bold text-slate-600">Pending Validation Queue Count</p>
    </div>

    {{-- Card 4: Total Overridden / Approved Cases --}}
    <div class="gov-card gov-card-hover p-5 border-l-4 border-l-emerald-600">
        <div class="mb-3 flex items-center justify-between">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-xs">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-extrabold text-emerald-900 border border-emerald-200">Audit Verified</span>
        </div>
        <p class="text-3xl font-black text-slate-900 tracking-tight">{{ number_format($overriddenCount) }}</p>
        <p class="mt-1 text-xs font-bold text-slate-600">Total Overridden / Approved Cases</p>
    </div>
</div>

{{-- Row 1: Charts -- Duplicate Status Breakdown & Program Distribution --}}
<div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
    {{-- Chart 1: Duplicate Status Breakdown (Pie / Donut Chart) --}}
    <div class="gov-card p-6 flex flex-col justify-between">
        <div>
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Duplicate Status Breakdown</h3>
                    <p class="text-[11px] font-medium text-slate-500">Real-time status of clean vs flagged vs validated records</p>
                </div>
                <span class="rounded-md bg-slate-100 px-2.5 py-1 text-[10px] font-extrabold text-slate-700 uppercase">Donut Analysis</span>
            </div>
            <div class="relative flex items-center justify-center py-2 h-64">
                <canvas id="chartDuplicateStatus"></canvas>
            </div>
        </div>
        <div class="mt-4 border-t border-slate-100 pt-3 text-center">
            <p class="text-[11px] font-semibold text-slate-500">
                Gives DOLE Validators an immediate view of pending duplicate flags requiring action.
            </p>
        </div>
    </div>

    {{-- Chart 2: Program Distribution (Pie / Donut Chart) --}}
    <div class="gov-card p-6 flex flex-col justify-between">
        <div>
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Program Distribution</h3>
                    <p class="text-[11px] font-medium text-slate-500">Proportion of beneficiaries across TUPAD, SPES, DILP, & GIP</p>
                </div>
                <span class="rounded-md bg-blue-50 px-2.5 py-1 text-[10px] font-extrabold text-blue-800 uppercase">4 Core Programs</span>
            </div>
            <div class="relative flex items-center justify-center py-2 h-64">
                <canvas id="chartProgramDistribution"></canvas>
            </div>
        </div>
        <div class="mt-4 border-t border-slate-100 pt-3 text-center">
            <p class="text-[11px] font-semibold text-slate-500">
                Displays the overall allocation of registered beneficiaries across DOLE programs for CY {{ date('Y') }}.
            </p>
        </div>
    </div>
</div>

{{-- Row 2: Modernized Charts -- Registration & Duplicate Trends + Geographic Distribution --}}
<div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
    {{-- Chart 3: Registration & Duplicate Trends (Smooth Spline Area / Bar Combination Chart) --}}
    <div class="gov-card p-6 flex flex-col justify-between">
        <div>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3.5">
                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Registration & Duplicate Trends</h3>
                    <p class="text-[11px] font-medium text-slate-500">Monthly new registrations vs policy violations flagged (CY {{ date('Y') }})</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-semibold">
                    <span class="inline-flex items-center gap-1.5 text-indigo-600">
                        <span class="h-2.5 w-2.5 rounded-xs bg-indigo-600 shadow-2xs"></span> Registrations
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-rose-500">
                        <span class="h-2.5 w-2.5 rounded-full bg-rose-500 shadow-2xs"></span> Duplicates
                    </span>
                </div>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="chartRegistrationTrends"></canvas>
            </div>
        </div>
        <div class="mt-4 border-t border-slate-100 pt-3 text-center">
            <p class="text-[11px] font-semibold text-slate-500">
                Identifies seasonal registration surges and spike patterns in potential duplicate entries.
            </p>
        </div>
    </div>

    {{-- Chart 4: Geographic Distribution (Horizontal Bar Chart with Pill Ends & Labels) --}}
    <div class="gov-card p-6 flex flex-col justify-between">
        <div>
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3.5">
                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Geographic Distribution</h3>
                    <p class="text-[11px] font-medium text-slate-500">Beneficiary concentration across Bukidnon Municipalities</p>
                </div>
                <span class="bg-emerald-50 text-emerald-700 text-xs px-2.5 py-1 rounded-full font-medium border border-emerald-200/60 flex items-center gap-1 shrink-0">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Top Density Areas
                </span>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="chartGeographicDistribution"></canvas>
            </div>
        </div>
        <div class="mt-4 border-t border-slate-100 pt-3 text-center">
            <p class="text-[11px] font-semibold text-slate-500">
                Visualizes high-density areas requiring closer monitoring for localized duplicate submissions.
            </p>
        </div>
    </div>
</div>

{{-- Row 3: Pending Action Console & Recent Registry --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Quick Action Duplicate Queue (1 Col) --}}
    <div class="gov-card p-6 flex flex-col justify-between lg:col-span-1">
        <div>
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Pending Flags Queue</h3>
                @hasanyrole('Admin|Validator')
                <a href="{{ route('duplicates.index') }}" class="text-xs font-extrabold text-blue-700 hover:underline">View Console →</a>
                @endhasanyrole
            </div>

            @if($recentDuplicates->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 mb-3 shadow-xs">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-sm font-black text-slate-900">No Pending Duplicates</p>
                    <p class="text-xs text-slate-500 font-medium">All flagged beneficiary records are currently resolved.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($recentDuplicates as $flag)
                        <div class="flex items-center gap-3.5 rounded-xl border {{ $flag->household_match_flag ? 'border-blue-200 bg-blue-50/50' : 'border-amber-200 bg-amber-50/60' }} p-3 transition hover:shadow-2xs">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $flag->household_match_flag ? 'bg-blue-700' : 'bg-amber-500' }} font-black text-white text-xs shadow-2xs">
                                {{ $flag->match_score }}%
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-extrabold text-slate-900">{{ $flag->beneficiary?->full_name }}</p>
                                <p class="truncate text-[11px] font-semibold text-slate-600">
                                    {{ $flag->household_match_flag ? 'household with' : 'matches' }} {{ $flag->matchedBeneficiary?->full_name }}
                                </p>
                            </div>
                            @hasanyrole('Admin|Validator')
                            <a href="{{ route('duplicates.index') }}" class="rounded-lg bg-white border border-slate-300 px-2.5 py-1 text-[11px] font-extrabold text-slate-800 shadow-2xs hover:bg-slate-50 transition shrink-0">
                                Review
                            </a>
                            @endhasanyrole
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Recent Beneficiaries Master Table (2 Cols) --}}
    <div class="gov-card overflow-hidden lg:col-span-2">
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50/80 px-6 py-4">
            <div>
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Recently Registered Beneficiaries</h3>
                <p class="text-[11px] font-medium text-slate-500">Latest additions to the Bukidnon regional beneficiary database</p>
            </div>
            <a href="{{ route('beneficiaries.index') }}" class="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-extrabold text-slate-800 shadow-2xs transition hover:bg-slate-100 hover:text-blue-800">
                View Full Registry →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-100/70 text-[11px] font-extrabold uppercase tracking-wider text-slate-700">
                    <tr>
                        <th class="px-6 py-3.5">Beneficiary Name</th>
                        <th class="px-6 py-3.5">Municipality</th>
                        <th class="px-6 py-3.5">Date of Birth</th>
                        <th class="px-6 py-3.5">Encoded By</th>
                        <th class="px-6 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-xs font-medium bg-white">
                    @forelse($recentBeneficiaries as $b)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-6 py-3.5 font-extrabold text-slate-900">
                                <a href="{{ route('beneficiaries.show', $b) }}" class="hover:text-blue-800 hover:underline">{{ $b->full_name }}</a>
                            </td>
                            <td class="px-6 py-3.5 text-slate-700 font-semibold">{{ $b->municipality }}</td>
                            <td class="px-6 py-3.5 text-slate-700 font-semibold">{{ $b->date_of_birth?->format('M d, Y') }}</td>
                            <td class="px-6 py-3.5 text-slate-700 font-semibold">{{ $b->creator?->name ?? '—' }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <a href="{{ route('beneficiaries.show', $b) }}" class="rounded-lg bg-slate-100 border border-slate-200 px-2.5 py-1 text-[11px] font-bold text-slate-800 hover:bg-blue-700 hover:text-white transition">
                                    View Profile
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500 font-semibold">No beneficiaries registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Global Chart.js Defaults
    Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#64748B';

    // Floating Tooltip Configuration
    const modernTooltipConfig = {
        enabled: true,
        backgroundColor: '#0F172A',
        titleColor: '#FFFFFF',
        bodyColor: '#F8FAFC',
        titleFont: { size: 12, weight: 'bold' },
        bodyFont: { size: 12, weight: '500' },
        padding: 12,
        cornerRadius: 8,
        displayColors: true,
        boxWidth: 8,
        boxHeight: 8,
        boxPadding: 4,
        shadowOffsetX: 0,
        shadowOffsetY: 4,
        shadowBlur: 12,
        shadowColor: 'rgba(15, 23, 42, 0.25)'
    };

    // 1. Duplicate Status Breakdown (Donut Chart)
    const duplicateStatusData = @json($duplicateStatusBreakdown);
    const ctxDuplicate = document.getElementById('chartDuplicateStatus').getContext('2d');
    new Chart(ctxDuplicate, {
        type: 'doughnut',
        data: {
            labels: Object.keys(duplicateStatusData),
            datasets: [{
                data: Object.values(duplicateStatusData),
                backgroundColor: [
                    '#10B981', // Clean: Emerald
                    '#F43F5E', // High Confidence: Rose
                    '#F59E0B', // Medium Confidence: Amber
                    '#4F46E5'  // Resolved: Deep Indigo
                ],
                borderWidth: 2,
                borderColor: '#FFFFFF',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1000, easing: 'easeInOutQuart' },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 10, padding: 14, font: { weight: '600', size: 11 } }
                },
                tooltip: modernTooltipConfig
            },
            cutout: '68%'
        }
    });

    // 2. Program Distribution (Pie / Donut Chart)
    const programData = @json($programDistribution);
    const ctxProgram = document.getElementById('chartProgramDistribution').getContext('2d');
    new Chart(ctxProgram, {
        type: 'doughnut',
        data: {
            labels: Object.keys(programData),
            datasets: [{
                data: Object.values(programData),
                backgroundColor: [
                    '#4F46E5', // TUPAD: Deep Indigo
                    '#10B981', // SPES: Emerald
                    '#F59E0B', // DILP: Amber
                    '#8B5CF6'  // GIP: Purple
                ],
                borderWidth: 2,
                borderColor: '#FFFFFF',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1000, easing: 'easeInOutQuart' },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 10, padding: 14, font: { weight: '600', size: 11 } }
                },
                tooltip: modernTooltipConfig
            },
            cutout: '68%'
        }
    });

    // 3. Registration & Duplicate Trends (Smooth Spline Area / Bar Combination Chart)
    const trendMonths = @json($trendMonths);
    const trendRegistrations = @json($trendRegistrations);
    const trendDuplicates = @json($trendDuplicates);

    const ctxTrends = document.getElementById('chartRegistrationTrends').getContext('2d');

    // Registrations Gradient (Deep Indigo to Light Indigo)
    const barGradient = ctxTrends.createLinearGradient(0, 0, 0, 300);
    barGradient.addColorStop(0, '#4F46E5');
    barGradient.addColorStop(1, '#818CF8');

    // Duplicates Soft Area Fill Gradient (Rose/Coral to Transparent)
    const lineAreaGradient = ctxTrends.createLinearGradient(0, 0, 0, 300);
    lineAreaGradient.addColorStop(0, 'rgba(244, 63, 94, 0.25)');
    lineAreaGradient.addColorStop(1, 'rgba(244, 63, 94, 0.00)');

    new Chart(ctxTrends, {
        type: 'bar',
        data: {
            labels: trendMonths,
            datasets: [
                {
                    type: 'bar',
                    label: 'New Registrations',
                    data: trendRegistrations,
                    backgroundColor: barGradient,
                    hoverBackgroundColor: '#4338CA',
                    borderRadius: { topLeft: 8, topRight: 8 },
                    borderSkipped: 'bottom',
                    barPercentage: 0.55
                },
                {
                    type: 'line',
                    label: 'Duplicates Flagged',
                    data: trendDuplicates,
                    borderColor: '#F43F5E',
                    backgroundColor: lineAreaGradient,
                    borderWidth: 3,
                    tension: 0.4, // Smooth curved spline
                    fill: true,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#F43F5E',
                    pointHoverBorderColor: '#FFFFFF',
                    pointHoverBorderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1000, easing: 'easeInOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: modernTooltipConfig
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748B', font: { size: 12, weight: '500' } }
                },
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: '#64748B', font: { size: 12, weight: '500' } },
                    grid: {
                        color: '#E2E8F0',
                        borderDash: [5, 5],
                        drawBorder: false
                    }
                }
            }
        }
    });

    // 4. Geographic Distribution (Horizontal Bar Chart with Pill Ends & Inline Numeric Labels)
    const geoStats = @json($geographicStats);
    const ctxGeo = document.getElementById('chartGeographicDistribution').getContext('2d');

    // Gradient Emerald to Teal (#10B981 to #14B8A6)
    const geoGradient = ctxGeo.createLinearGradient(0, 0, 400, 0);
    geoGradient.addColorStop(0, '#10B981');
    geoGradient.addColorStop(1, '#14B8A6');

    // Inline Numeric Count Label Plugin
    const floatingValueLabelsPlugin = {
        id: 'floatingValueLabels',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            chart.data.datasets.forEach((dataset, datasetIndex) => {
                const meta = chart.getDatasetMeta(datasetIndex);
                meta.data.forEach((bar, index) => {
                    const val = dataset.data[index];
                    if (val !== undefined && val !== null) {
                        ctx.save();
                        ctx.font = '600 11px Inter, sans-serif';
                        ctx.fillStyle = '#64748B';
                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(val, bar.x + 8, bar.y);
                        ctx.restore();
                    }
                });
            });
        }
    };

    new Chart(ctxGeo, {
        type: 'bar',
        data: {
            labels: Object.keys(geoStats),
            datasets: [{
                label: 'Beneficiaries',
                data: Object.values(geoStats),
                backgroundColor: geoGradient,
                hoverBackgroundColor: '#059669',
                borderRadius: 20, // Pill-shaped rounded ends
                barThickness: 16
            }]
        },
        plugins: [floatingValueLabelsPlugin],
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1000, easing: 'easeInOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: modernTooltipConfig
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: '#64748B', font: { size: 12, weight: '500' } },
                    grid: {
                        color: '#E2E8F0',
                        borderDash: [5, 5],
                        drawBorder: false
                    }
                },
                y: {
                    grid: { display: false },
                    ticks: { color: '#64748B', font: { size: 12, weight: '500' } }
                }
            }
        }
    });
});
</script>
@endpush
