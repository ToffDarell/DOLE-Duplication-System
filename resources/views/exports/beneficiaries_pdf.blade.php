<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Beneficiaries Masterlist Report — DOLE Bukidnon</title>
    <style>
        @page { margin: 20px 25px; }
        body { font-family: sans-serif; font-size: 8px; color: #0f172a; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 10px; border-bottom: 2px solid #1e3a8a; padding-bottom: 6px; }
        .header h1 { font-size: 13px; margin: 0; color: #1e3a8a; font-weight: bold; text-transform: uppercase; }
        .header h2 { font-size: 10px; margin: 2px 0; color: #334155; font-weight: bold; }
        .header p { margin: 1px 0; color: #64748b; font-size: 7.5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th { background-color: #1e3a8a; color: #ffffff; text-align: left; padding: 4px; font-weight: bold; font-size: 7.5px; border: 1px solid #1e3a8a; text-transform: uppercase; word-wrap: break-word; }
        td { padding: 3.5px 4px; border: 1px solid #cbd5e1; font-size: 7px; vertical-align: middle; word-wrap: break-word; overflow: hidden; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .badge { display: inline-block; padding: 1px 3px; border-radius: 2px; font-size: 6.5px; font-weight: bold; background: #e0f2fe; color: #0369a1; }
        .risk-exact { color: #dc2626; font-weight: bold; }
        .risk-high { color: #ea580c; font-weight: bold; }
        .risk-mod { color: #d97706; font-weight: bold; }
        .risk-clean { color: #16a34a; }
        .footer { margin-top: 10px; text-align: right; font-size: 7px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 4px; }
    </style>
</head>
<body>
    {{-- DOLE Centered Header Banner with Official Seal Logo --}}
    <div style="text-align: center; margin-bottom: 6px; border-bottom: 2px solid #1e3a8a; padding-bottom: 6px;">
        <table align="center" style="margin-left: auto; margin-right: auto; width: auto; border: none; border-collapse: collapse;">
            <tr>
                @if(!empty($logoSrc))
                <td style="border: none; vertical-align: middle; padding: 0 12px 0 0; text-align: center;">
                    <img src="{{ $logoSrc }}" style="height: 52px; width: 52px;" alt="DOLE Logo">
                </td>
                @endif
                <td style="border: none; vertical-align: middle; text-align: center; padding: 0;">
                    <div style="font-size: 8.5px; font-weight: bold; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Republic of the Philippines</div>
                    <div style="font-size: 13.5px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.5px; margin: 1.5px 0;">DEPARTMENT OF LABOR AND EMPLOYMENT</div>
                    <div style="font-size: 10px; font-weight: bold; color: #334155; margin-bottom: 1.5px;">Regional Office No. X — Bukidnon Field Office</div>
                    <div style="font-size: 7.5px; color: #64748b;">Beneficiary Masterlist & Duplication Detection Official Report (Alphabetical Order)</div>
                </td>
            </tr>
        </table>
        <div style="margin-top: 4px; font-size: 7px; color: #64748b;">
            Generated on: <strong>{{ date('F d, Y h:i A') }}</strong> &nbsp;&bull;&nbsp; 
            Total Beneficiaries: <strong>{{ $beneficiaries->count() }}</strong> &nbsp;&bull;&nbsp; 
            <span>Official DOLE Bukidnon Field Office Masterlist</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 3%; text-align: center;">#</th>
                <th style="width: 15%;">Full Name</th>
                <th style="width: 8%; text-align: center;">Date of Birth</th>
                <th style="width: 4%; text-align: center;">Age</th>
                <th style="width: 5%; text-align: center;">Sex</th>
                <th style="width: 10%;">Municipality</th>
                <th style="width: 10%;">Barangay</th>
                <th style="width: 14%;">House Number / Address</th>
                <th style="width: 9%;">Gov ID Number</th>
                <th style="width: 8%;">Contact Number</th>
                <th style="width: 7%;">Program(s)</th>
                <th style="width: 7%; text-align: center;">Risk Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($beneficiaries as $index => $b)
                @php
                    $maxScore = $b->duplicateFlags->max('match_score') ?? 0;
                    if ($maxScore >= 100) {
                        $riskLabel = 'Exact (100%)';
                        $riskClass = 'risk-exact';
                    } elseif ($maxScore >= 85) {
                        $riskLabel = "High ({$maxScore}%)";
                        $riskClass = 'risk-high';
                    } elseif ($maxScore >= 70) {
                        $riskLabel = "Mod ({$maxScore}%)";
                        $riskClass = 'risk-mod';
                    } elseif ($maxScore > 0) {
                        $riskLabel = "Low ({$maxScore}%)";
                        $riskClass = 'risk-clean';
                    } else {
                        $riskLabel = 'Clean (0%)';
                        $riskClass = 'risk-clean';
                    }
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td><strong>{{ $b->full_name }}</strong></td>
                    <td style="text-align: center;">{{ $b->date_of_birth ? $b->date_of_birth->format('M d, Y') : '—' }}</td>
                    <td style="text-align: center;">{{ $b->age }}</td>
                    <td style="text-align: center;">{{ $b->sex }}</td>
                    <td>{{ $b->municipality }}</td>
                    <td>{{ $b->barangay }}</td>
                    <td>{{ $b->address ?: '—' }}</td>
                    <td>{{ $b->government_id_number ?: '—' }}</td>
                    <td>{{ $b->contact_number ?: '—' }}</td>
                    <td>
                        @forelse($b->beneficiaryPrograms as $bp)
                            <span class="badge">{{ $bp->program?->code }} ({{ $bp->availment_year }})</span>
                        @empty
                            —
                        @endforelse
                    </td>
                    <td style="text-align: center;" class="{{ $riskClass }}">{{ $riskLabel }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        DOLE Bukidnon Field Office — Beneficiary Duplication Detection & Monitoring System | Page 1
    </div>
</body>
</html>
