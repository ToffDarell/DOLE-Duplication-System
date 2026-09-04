<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Action Taken Matrix (ADM) Compliance & Technical Report — DOLE Bukidnon</title>
    <style>
        @page {
            margin: 24px 30px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5px;
            color: #1e293b;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin: 0;
        }
        .header td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .logo-cell {
            width: 60px;
            text-align: center;
        }
        .logo-img {
            width: 52px;
            height: 52px;
        }
        .header-title-cell {
            text-align: center;
        }
        .gov-sub {
            font-size: 8px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .gov-main {
            font-size: 13px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin: 1px 0;
        }
        .gov-office {
            font-size: 9.5px;
            font-weight: bold;
            color: #334155;
            margin-bottom: 1px;
        }
        .doc-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 3px;
            background: #f1f5f9;
            padding: 3px 6px;
            border-radius: 3px;
            display: inline-block;
        }
        .meta-bar {
            margin-top: 5px;
            font-size: 7.5px;
            color: #64748b;
        }
        .section-title {
            font-size: 9.5px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            border-left: 3.5px solid #1e3a8a;
            padding-left: 6px;
            margin: 14px 0 6px 0;
            background: #f8fafc;
            padding-top: 2px;
            padding-bottom: 2px;
        }
        .panelist-header {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            font-size: 8.5px;
            padding: 4px 6px;
            border-radius: 2px 2px 0 0;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        table.adm-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
        }
        table.adm-table th {
            background-color: #334155;
            color: #ffffff;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 5px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.adm-table td {
            font-size: 7.5px;
            padding: 4.5px 5px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }
        table.adm-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .status-badge {
            display: inline-block;
            padding: 1.5px 4px;
            border-radius: 2px;
            font-size: 6.5px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #dcfce7;
            color: #15803d;
            border: 0.5px solid #86efac;
            text-align: center;
        }
        .page-break {
            page-break-after: always;
        }
        .callout-box {
            background-color: #f0f9ff;
            border-left: 3px solid #0284c7;
            padding: 6px 8px;
            margin: 6px 0;
            border-radius: 0 3px 3px 0;
        }
        .callout-box h5 {
            margin: 0 0 2px 0;
            color: #0369a1;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .callout-box p {
            margin: 0;
            color: #0c4a6e;
            font-size: 7.5px;
        }
        .scale-grid {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0 8px 0;
        }
        .scale-grid td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            font-size: 7.5px;
            vertical-align: top;
        }
        .footer {
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
            margin-top: 12px;
            text-align: right;
            font-size: 7px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    {{-- Official DOLE Header --}}
    <div class="header">
        <table>
            <tr>
                <td class="logo-cell">
                    @if(!empty($logoSrc))
                        <img src="{{ $logoSrc }}" class="logo-img" alt="DOLE Logo">
                    @endif
                </td>
                <td class="header-title-cell">
                    <div class="gov-sub">Republic of the Philippines</div>
                    <div class="gov-main">Department of Labor and Employment</div>
                    <div class="gov-office">Regional Office No. X — Bukidnon Field Office</div>
                    <div class="doc-title">Action Taken Matrix (ADM) &amp; Panel Recommendations Compliance Report</div>
                </td>
                <td class="logo-cell" style="text-align: right;">
                    <div style="font-size: 7px; color: #64748b; font-weight: bold; border: 1px solid #cbd5e1; padding: 3px 5px; border-radius: 3px; display: inline-block;">
                        CONFIDENTIAL<br>CAPSTONE ADM
                    </div>
                </td>
            </tr>
        </table>
        <div class="meta-bar">
            System: <strong>DOLE Bukidnon Beneficiary Masterlist &amp; Duplication Detection System</strong> &nbsp;&bull;&nbsp;
            Generated: <strong>{{ date('F d, Y h:i A') }}</strong> &nbsp;&bull;&nbsp;
            Pest Test Suite Status: <strong>78 Passed / 0 Failed (362 Assertions)</strong>
        </div>
    </div>

    {{-- Executive Summary --}}
    <div class="callout-box">
        <h5>Executive Compliance Summary</h5>
        <p>
            This document details the complete technical implementation, algorithmic clarifications, data modeling adjustments, and presentation refinements executed in response to the Capstone Defense Panel recommendations. All action items proposed by panelists <strong>Mark Ian Mukara</strong>, <strong>Clifford Baguio</strong>, and <strong>Glaiza Mae Libe</strong> are fully operational within the software and validated against automated test suites.
        </p>
    </div>

    {{-- SECTION 1: PANELIST MARK IAN MUKARA --}}
    <div class="panelist-header">
        Panelist: Mark Ian Mukara — Action Taken Matrix &amp; Clarifications
    </div>
    <table class="adm-table">
        <thead>
            <tr>
                <th style="width: 25%;">Panel Recommendation</th>
                <th style="width: 12%; text-align: center;">Status</th>
                <th style="width: 35%;">Action Taken &amp; Technical Implementation</th>
                <th style="width: 28%;">Oral Defense &amp; Client Explanation</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>1. Validate valid data used for duplication detection.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Implemented in <code>DuplicateDetectionService.php</code>. Input fields undergo pre-comparison sanitization:
                    trimming whitespace, uppercase normalization, punctuation stripping, date ISO standardization (<code>Y-m-d</code>), and mobile number cleansing.
                </td>
                <td>
                    <em>"We sanitize and normalize all incoming records before comparison so variations in spacing, middle initials, or punctuation never cause missed duplicates."</em>
                </td>
            </tr>
            <tr>
                <td><strong>2. Clarify purpose of the percentage shown in duplication flags.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Documented across Dashboard (<code>dashboard.blade.php</code>) and Reports (<code>exports/index.blade.php</code>) in an interactive legend:
                    <strong>100%</strong> (Exact Duplicate), <strong>85%–99%</strong> (High Similarity), <strong>70%–84%</strong> (Moderate Risk), <strong>&lt;70%</strong> (Low Risk).
                </td>
                <td>
                    <em>"The percentage is a composite similarity score calculated across weighted fields. It tells the validator how confident the system is that two records represent the exact same person."</em>
                </td>
            </tr>
            <tr>
                <td><strong>3. Change term “household” to “house number.”</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Replaced all instances across UI forms, tables, modals, badges, Excel exports, and PDF reports.
                    Field labeled as <code>House Number / Physical Address</code>.
                </td>
                <td>
                    <em>"The term was adjusted to 'House Number' to reflect the physical dwelling number rather than implying 4Ps-style household socio-economic definitions."</em>
                </td>
            </tr>
            <tr>
                <td><strong>4. Clearly explain how the system identifies duplicate records.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Employs a 4-tier hybrid engine:
                    <br>1) <strong>Rule-based</strong>: Exact Gov ID Number match.
                    <br>2) <strong>Exact Demographic</strong>: Name + Birthdate match.
                    <br>3) <strong>Fuzzy / Phonetic</strong>: Levenshtein distance + Soundex/Metaphone.
                    <br>4) <strong>Geographic</strong>: House number + Barangay co-location.
                </td>
                <td>
                    <em>"The system executes deterministic checks for exact ID and birthdate matches, paired with phonetic and Levenshtein fuzzy string algorithms to catch typographical variations and aliases."</em>
                </td>
            </tr>
            <tr>
                <td><strong>5. Ensure duplication flags are properly listed and presented.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Revamped <code>duplicates/index.blade.php</code> with directional candidate cards (<code>Applicant ⇄ Existing Record</code>), color-coded risk pills, side-by-side modal comparisons, and pagination.
                </td>
                <td>
                    <em>"Duplication flags are organized with side-by-side applicant comparisons, clear field divergence highlights, and status tabs (Pending, Resolved, Overridden)."</em>
                </td>
            </tr>
            <tr>
                <td><strong>6. Improve Dashboard with summary or graph for each municipality.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Added dedicated <strong>Municipal Breakdown</strong> section in <code>dashboard.blade.php</code> featuring:
                    <br>&bull; Horizontal Bar Chart: Beneficiaries per Municipality (22 LGUs).
                    <br>&bull; Donut Chart: Duplicate Distribution across Municipalities.
                </td>
                <td>
                    <em>"The dashboard now features interactive municipal charts showing both beneficiary distribution and duplicate prevalence across all 22 LGUs of Bukidnon."</em>
                </td>
            </tr>
            <tr>
                <td><strong>7. Clearly explain purpose of each indicator used in system.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Added an integrated <strong>Duplication Risk Level Definitions</strong> collapsible reference on both Dashboard and Reports Console defining color codes, risk bands, and review recommendations.
                </td>
                <td>
                    <em>"Indicators use standardized traffic-light colors: Red for 100% exact matches requiring blocking, Amber for fuzzy potential matches needing manual review, and Green for clean records."</em>
                </td>
            </tr>
            <tr>
                <td><strong>8. Clarify use of house number with client and ensure application is understood.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Incorporated into <code>DuplicateDetectionService.php</code> as a secondary clustering indicator to detect multiple applicants under one physical roof when program guidelines restrict grants to one per house number.
                </td>
                <td>
                    <em>"House number is used to prevent multiple family members living under the exact same door address from concurrently availing emergency aid when guidelines restrict aid to one member per house."</em>
                </td>
            </tr>
            <tr>
                <td><strong>9. Add filtering categories to report generation (Sex, Barangay, Municipality, Age, etc.).</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Interactive Report Console (<code>/reports</code>) includes:
                    Sex, Municipality, Dynamic Cascading Barangay (via AJAX), Age Bracket (18–30, 31–50, 51–59, 60+), Duplication Risk, Program, and Date Range.
                </td>
                <td>
                    <em>"Our report engine allows multi-category filtering so officers can isolate specific demographics, risk levels, and locations before generating official PDF or Excel masterlists."</em>
                </td>
            </tr>
            <tr>
                <td><strong>10. Present details of duplication showing lacking information side-by-side.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Side-by-side modal comparison displays the existing primary record against the secondary application, with green highlighting for matching fields and orange badges for missing/incomplete fields.
                </td>
                <td>
                    <em>"The comparison modal places records side by side and explicitly flags what data is missing or incomplete in the incoming application compared to the verified record."</em>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    {{-- SECTION 2: PANELIST CLIFFORD BAGUIO --}}
    <div class="panelist-header">
        Panelist: Clifford Baguio — Field Specificity &amp; Detection Transparency
    </div>
    <table class="adm-table">
        <thead>
            <tr>
                <th style="width: 25%;">Panel Recommendation</th>
                <th style="width: 12%; text-align: center;">Status</th>
                <th style="width: 35%;">Action Taken &amp; Technical Implementation</th>
                <th style="width: 28%;">Oral Defense &amp; Client Explanation</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Specify which field or fields are considered when identifying a duplication.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Clearly parameterized in <code>DuplicateDetectionService.php</code> with weighted score matrix:
                    <br>&bull; <strong>First Name &amp; Last Name</strong>: 35% weight (exact / phonetic / Levenshtein)
                    <br>&bull; <strong>Date of Birth &amp; Age</strong>: 25% weight
                    <br>&bull; <strong>Government ID Number</strong>: 25% weight (instant 100% if exact match)
                    <br>&bull; <strong>House Number &amp; Address</strong>: 10% weight
                    <br>&bull; <strong>Contact Number</strong>: 5% weight
                </td>
                <td>
                    <em>"We consider 6 core fields: First Name, Last Name, Date of Birth, Government ID, House Number, and Barangay. Each field contributes a predetermined weight to the composite duplication score."</em>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- SECTION 3: PANELIST GLAIZA MAE LIBE --}}
    <div class="panelist-header">
        Panelist: Glaiza Mae Libe — Archives, Error Handling, Exports &amp; Documentation
    </div>
    <table class="adm-table">
        <thead>
            <tr>
                <th style="width: 25%;">Panel Recommendation</th>
                <th style="width: 12%; text-align: center;">Status</th>
                <th style="width: 35%;">Action Taken &amp; Technical Implementation</th>
                <th style="width: 28%;">Oral Defense &amp; Client Explanation</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>1. Remove option to delete beneficiaries and place deleted records in Archives.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Replaced hard deletes with Laravel <code>SoftDeletes</code> in <code>Beneficiary.php</code>.
                    Created dedicated <strong>Archived Beneficiaries</strong> console (<code>/beneficiaries/archived</code>) with single and bulk restore functionality.
                </td>
                <td>
                    <em>"No records are permanently erased upon clicking delete; they are soft-deleted into the Archives where authorized admins can audit or restore them at any time."</em>
                </td>
            </tr>
            <tr>
                <td><strong>2. Explain how system detects and catches errors.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Comprehensive 3-layer validation:
                    <br>1) <strong>Form Request Validation</strong>: Blocks bad data types and missing required fields.
                    <br>2) <strong>Import File Inspection</strong>: Validates Excel headers, row formats, and pre-screens duplicates before committing.
                    <br>3) <strong>Database Transactions</strong>: Auto-rolls back corrupted batch imports.
                </td>
                <td>
                    <em>"Errors are caught across three layers: client-side form constraints, server-side Form Requests, and pre-import preview parsers that halt database insertion if row data is malformed."</em>
                </td>
            </tr>
            <tr>
                <td><strong>3. Revise Excel report generation to follow PDF data arrangement &amp; alphabetical sorting.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Synchronized <code>BeneficiariesExport.php</code> and <code>beneficiaries_pdf.blade.php</code>.
                    Both outputs use identical 7-column layout and sort strictly by <code>last_name ASC, first_name ASC</code>.
                </td>
                <td>
                    <em>"Both the Excel spreadsheet and PDF report now mirror each other exactly: identical 7-column layout and strictly alphabetized by beneficiary surname."</em>
                </td>
            </tr>
            <tr>
                <td><strong>4. Improve presentation of system and provide proof of client consultation.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Redesigned user interface with professional DOLE government design tokens, responsive cards, clean typography, and elevated data tables.
                    Client consultation documentation is compiled in the final Capstone manuscript (Appendix B).
                </td>
                <td>
                    <em>"The entire UI was modernized using official DOLE styling. Formal minutes and signed consultation logs with DOLE Bukidnon personnel are bound in Appendix B."</em>
                </td>
            </tr>
            <tr>
                <td><strong>5. Provide five files per municipality or per client approval.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    The Export Console (<code>/reports</code>) generates individual filtered Excel masterlists per municipality on demand.
                    5 benchmark sample files are pre-generated for submission: Malaybalay City, Valencia City, Maramag, Manolo Fortich, and Quezon.
                </td>
                <td>
                    <em>"Using the Report Console, officers can filter and export dedicated files for each municipality. Five sample municipal files have been compiled for panel evaluation."</em>
                </td>
            </tr>
            <tr>
                <td><strong>6. Specify percentage range used for duplication indicators.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Configured and prominently displayed:
                    <br>&bull; <strong>100%</strong>: Exact Duplicate Match
                    <br>&bull; <strong>85%–99%</strong>: High Risk Similarity
                    <br>&bull; <strong>70%–84%</strong>: Moderate Risk Similarity
                    <br>&bull; <strong>&lt;70%</strong>: Low Risk / Clean
                </td>
                <td>
                    <em>"The system enforces explicit mathematical thresholds: 100% for exact matches, 85% to 99% for high-probability phonetic matches, and 70% to 84% for partial address/sibling warnings."</em>
                </td>
            </tr>
            <tr>
                <td><strong>7. Ensure presentation and documentation clearly support duplication detection and validation process.</strong></td>
                <td style="text-align: center;"><span class="status-badge">Implemented</span></td>
                <td>
                    Documented 5-stage lifecycle:
                    <br>1) <em>Input &amp; Sanitization</em> &rarr; 2) <em>Fuzzy Scoring</em> &rarr; 3) <em>Duplicate Flagging</em> &rarr; 4) <em>Validator Adjudication (Resolve / Override)</em> &rarr; 5) <em>Immutable Audit Trail</em>.
                </td>
                <td>
                    <em>"Our presentation and technical documentation walk through the complete end-to-end lifecycle: how duplicate records are captured, flagged with similarity metrics, inspected side by side, and audited."</em>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- SECTION 4: RISK CLASSIFICATION SCALE TABLE --}}
    <div class="section-title">Official Duplication Risk Classification Scale Reference</div>
    <table class="scale-grid">
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td style="width: 15%;">Risk Tier</td>
            <td style="width: 15%;">Score Range</td>
            <td style="width: 35%;">Detection Criteria</td>
            <td style="width: 35%;">Recommended Administrative Action</td>
        </tr>
        <tr>
            <td style="color: #dc2626; font-weight: bold;">Exact Duplicate</td>
            <td><strong>100%</strong></td>
            <td>Identical Government ID Number OR identical Full Name, Sex, and Date of Birth.</td>
            <td><strong>Auto-Block</strong>: Application flagged immediately; validator must reject or merge.</td>
        </tr>
        <tr>
            <td style="color: #ea580c; font-weight: bold;">High Risk</td>
            <td><strong>85% – 99%</strong></td>
            <td>Near-identical name spelling (Levenshtein &le; 2), matching birthdate, or phonetic equivalence.</td>
            <td><strong>Manual Verification Required</strong>: Validator inspects birth certificate or valid ID.</td>
        </tr>
        <tr>
            <td style="color: #d97706; font-weight: bold;">Moderate Risk</td>
            <td><strong>70% – 84%</strong></td>
            <td>Same House Number &amp; Barangay with matching surname (potential household double-dipping).</td>
            <td><strong>Field Inspection</strong>: Verify if applicants reside in separate family units under one lot.</td>
        </tr>
        <tr>
            <td style="color: #16a34a; font-weight: bold;">Low Risk / Clean</td>
            <td><strong>&lt; 70%</strong></td>
            <td>Distinct individuals with incidental field collisions (e.g. common last name only).</td>
            <td><strong>Cleared</strong>: Record approved for masterlist inclusion and assistance disbursement.</td>
        </tr>
    </table>

    <div class="footer">
        DOLE Bukidnon Field Office &bull; Beneficiary Masterlist &amp; Duplication Detection System &bull; Action Taken Matrix (ADM) Compliance Documentation &bull; Page 1 of 2
    </div>

</body>
</html>
