<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Beneficiaries Report — DOLE Bukidnon</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0284c7; padding-bottom: 10px; }
        .header h1 { font-size: 16px; margin: 0; color: #0f172a; }
        .header p { margin: 2px 0; color: #64748b; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f1f5f9; color: #334155; text-align: left; padding: 6px; font-weight: bold; border-bottom: 1px solid #cbd5e1; }
        td { padding: 6px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: bold; background: #e0f2fe; color: #0369a1; }
        .footer { margin-top: 20px; text-align: right; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <h1>DEPARTMENT OF LABOR AND EMPLOYMENT — BUKIDNON</h1>
        <p>Beneficiary Duplicate Detection System — Official Masterlist Report</p>
        <p>Generated on {{ date('F d, Y h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Date of Birth</th>
                <th>Age</th>
                <th>Sex</th>
                <th>Municipality</th>
                <th>Barangay</th>
                <th>Gov ID Number</th>
                <th>Contact Number</th>
                <th>Program(s)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($beneficiaries as $index => $b)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $b->full_name }}</strong></td>
                    <td>{{ $b->date_of_birth ? $b->date_of_birth->format('M d, Y') : '—' }}</td>
                    <td>{{ $b->age }}</td>
                    <td>{{ $b->sex }}</td>
                    <td>{{ $b->municipality }}</td>
                    <td>{{ $b->barangay }}</td>
                    <td>{{ $b->government_id_number ?? '—' }}</td>
                    <td>{{ $b->contact_number ?? '—' }}</td>
                    <td>
                        @foreach($b->beneficiaryPrograms as $bp)
                            <span class="badge">{{ $bp->program?->code }}</span>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Page 1 | DOLE Bukidnon Duplicate Detection System
    </div>
</body>
</html>
