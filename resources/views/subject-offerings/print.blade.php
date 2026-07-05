<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class List{{ $academicTerm ? ' — ' . $academicTerm->display_name : '' }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #1a1a1a;
            margin: 0;
            padding: 24px;
        }

        .no-print {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 20px;
        }

        .no-print button {
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            border: 1px solid #2563eb;
            background: #2563eb;
            color: #fff;
            cursor: pointer;
        }

        .no-print button.secondary {
            background: #fff;
            color: #2563eb;
        }

        header.doc-header {
            text-align: center;
            margin-bottom: 24px;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 12px;
        }

        header.doc-header h1 {
            margin: 0 0 4px;
            font-size: 20px;
            letter-spacing: 0.5px;
        }

        header.doc-header .subtitle {
            margin: 0;
            font-size: 13px;
            color: #444;
        }

        header.doc-header .term {
            margin-top: 6px;
            font-size: 15px;
            font-weight: 600;
        }

        .section-block {
            margin-bottom: 22px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .section-title {
            display: inline-block;
            font-size: 16px;
            font-weight: 700;
            background: #1a1a1a;
            color: #fff;
            padding: 4px 12px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th, td {
            border: 1px solid #999;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.3px;
        }

        td.center, th.center {
            text-align: center;
        }

        .empty-note {
            font-size: 13px;
            color: #666;
            text-align: center;
            padding: 30px 0;
        }

        footer.doc-footer {
            margin-top: 30px;
            font-size: 11px;
            color: #666;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            @page { margin: 1.2cm; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="secondary" onclick="window.close()">Close</button>
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>

    <header class="doc-header">
        <h1>Class List — Subject Offerings</h1>
        <p class="subtitle">Partial list for posting before enrollment. Faculty, Room, and Schedule are not yet assigned.</p>
        @if ($academicTerm)
            <p class="term">{{ $academicTerm->display_name }}</p>
        @endif
    </header>

    @forelse ($sections as $section)
        <div class="section-block">
            <div class="section-title">{{ $section['section_code'] }}</div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 14%;">EDP Code</th>
                        <th style="width: 14%;">Subject Code</th>
                        <th>Descriptive Title</th>
                        <th class="center" style="width: 8%;">Units</th>
                        <th class="center" style="width: 8%;">Hours</th>
                        <th style="width: 14%;">Classification</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($section['offerings'] as $offering)
                        <tr>
                            <td>{{ $offering->edp_code }}</td>
                            <td>{{ $offering->subject?->subject_code }}</td>
                            <td>{{ $offering->subject?->descriptive_title }}</td>
                            <td class="center">{{ $offering->units ?? '—' }}</td>
                            <td class="center">{{ $offering->hours ?? '—' }}</td>
                            <td>{{ $offering->classification ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p class="empty-note">No Subject Offerings match the current filters.</p>
    @endforelse

    <footer class="doc-footer">
        <span>Generated {{ $generatedAt->format('F j, Y g:i A') }}</span>
        <span>Professional Academy of the Philippines — Classly</span>
    </footer>

</body>
</html>