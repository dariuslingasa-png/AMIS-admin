<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS Halaqah Registrations List</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { margin: 0; background: #fff; color: #0f172a; font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.35; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff; padding: 10mm 5mm; }
        .header { display: table; width: 100%; border-bottom: 2px solid #059669; padding-bottom: 14px; margin-bottom: 18px; }
        .brand-mark, .brand-text, .status { display: table-cell; vertical-align: middle; }
        .brand-mark { width: 54px; }
        .brand-logo { display: block; width: 48px; height: 48px; object-fit: contain; }
        h1 { margin: 0; font-size: 15px; font-weight: 900; letter-spacing: .02em; }
        .subtitle { margin-top: 2px; color: #059669; font-size: 9px; font-weight: 800; text-transform: uppercase; }
        table { border-collapse: collapse !important; width: 100% !important; font-family: Arial, sans-serif !important; border: none !important; margin-bottom: 2rem !important; }
        table th { background: #f8fafc !important; color: #1e293b !important; font-family: Arial, sans-serif !important; font-weight: bold !important; font-size: 10px !important; padding: 8px 10px !important; text-transform: uppercase !important; text-align: left; border-bottom: 1px solid #cbd5e1 !important; }
        table td { border-bottom: 1px solid #e2e8f0 !important; padding: 8px 10px !important; font-family: Arial, sans-serif !important; font-size: 9px !important; color: #334155 !important; background: transparent !important; }
        tr { page-break-inside: avoid !important; }
        .page-break-after { page-break-after: always !important; break-after: page !important; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .mb-6 { margin-bottom: 1.5rem; }
        .toolbar { position: sticky; top: 0; display: flex; justify-content: flex-end; gap: 8px; padding: 10px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .toolbar button { border: 0; border-radius: 8px; background: #059669; color: #fff; cursor: pointer; font-weight: 800; padding: 9px 14px; }
        @media print {
            .toolbar { display: none; }
            .page { width: auto; min-height: auto; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>

    <main class="page">
        <!-- Print Header -->
        <header class="header" style="border-bottom: 2px solid #059669; padding-bottom: 10px; margin-bottom: 20px;">
            <div style="display: table; width: 100%; border-collapse: collapse;">
                <div style="display: table-row;">
                    <!-- Left: English Name -->
                    <div style="display: table-cell; vertical-align: middle; width: 40%; text-align: left;">
                        <h1 style="font-family: Arial, sans-serif; font-weight: 900; font-size: 14px; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; color: #0f172a;">
                            AL MUNAWWARA ISLAMIC SCHOOL
                        </h1>
                        <div style="margin-top: 2px; color: #64748b; font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                            Official School Portal
                        </div>
                    </div>
                    <!-- Center: Logo -->
                    <div style="display: table-cell; vertical-align: middle; width: 20%; text-align: center;">
                        <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" style="height: 54px; width: auto; display: inline-block;">
                    </div>
                    <!-- Right: Arabic Name -->
                    <div style="display: table-cell; vertical-align: middle; width: 40%; text-align: right; direction: rtl;">
                        <h1 style="font-family: 'Times New Roman', serif; font-weight: 900; font-size: 18px; margin: 0; color: #059669; letter-spacing: 0.03em;">
                            المدرسة المنورة الإسلامية
                        </h1>
                    </div>
                </div>
            </div>
            <!-- Address Centered Below Logo -->
            <div style="text-align: center; font-size: 9px; color: #475569; font-weight: 700; margin-top: 8px; font-family: Arial, sans-serif; text-transform: uppercase; letter-spacing: 0.03em;">
                Don Julian Rodriguez Avenue, Ma-a, Davao City, Philippines, 8000
            </div>
        </header>

        <div class="mb-6 text-center">
            <h1 class="uppercase tracking-tight text-slate-900 font-bold" style="font-family: Arial, sans-serif; font-size: 13px; margin: 0; letter-spacing: 0.05em;">
                HALAQAH ONLINE REGISTRATIONS LIST
            </h1>
            <div class="text-slate-500 font-normal" style="font-family: Arial, sans-serif; font-size: 9px; margin-top: 4px;">
                Generated on: {{ now()->timezone('Asia/Manila')->format('M d, Y h:i A') }} | Total Records: {{ $registrations->count() }} Submissions
            </div>
        </div>

        <table class="w-full text-left text-sm print-table">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">#</th>
                    <th style="width: 15%">Date</th>
                    <th style="width: 30%">Applicant Details</th>
                    <th style="width: 25%">Program Details</th>
                    <th style="width: 25%">Message / Goals</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($registrations as $reg)
                    @php
                        $lines = explode("\n", $reg->message);
                        $details = [];
                        foreach ($lines as $line) {
                            if (str_contains($line, ':')) {
                                [$k, $v] = explode(':', $line, 2);
                                $details[trim($k)] = trim($v);
                            }
                        }
                        $address = $details['Address'] ?? '';
                        $msTeams = $details['MS Teams Account'] ?? '';
                        $level = $details['Learning Level'] ?? '';
                        $gradeLevel = $details['Grade Level'] ?? '';
                        
                        $msgParts = explode('--- Halaqah Registration Details ---', $reg->message);
                        $actualMessage = trim($msgParts[0]);
                    @endphp
                    <tr>
                        <td style="text-align: center; font-weight: bold; color: #64748b;">{{ $loop->iteration }}</td>
                        <td style="color: #64748b;">
                            {{ date('M d, Y', strtotime($reg->created_at)) }}
                            <div style="font-size: 8px; color: #94a3b8;">{{ date('h:i A', strtotime($reg->created_at)) }}</div>
                        </td>
                        <td>
                            <strong style="color: #0f172a; text-transform: uppercase;">{{ $reg->name }}</strong>
                            <div style="margin-top: 2px; color: #475569;">Email: {{ $reg->email }}</div>
                            <div style="color: #475569;">Phone: {{ $reg->phone }}</div>
                            @if($address)
                                <div style="font-size: 8px; color: #64748b; text-transform: uppercase;">Loc: {{ $address }}</div>
                            @endif
                        </td>
                        <td>
                            @if($level)
                                <div style="font-weight: bold; color: #0369a1; text-transform: uppercase;">{{ $level }}</div>
                            @endif
                            @if($gradeLevel)
                                <div style="margin-top: 2px; font-size: 8.5px; color: #334155; font-weight: 800; text-transform: uppercase;">Grade: {{ $gradeLevel }}</div>
                            @endif
                            @if($msTeams)
                                <div style="margin-top: 2px; font-size: 8px; color: #475569;">Teams UPN: <code style="background: #f1f5f9; padding: 2px 4px; border-radius: 4px; font-family: monospace;">{{ $msTeams }}</code></div>
                            @endif
                        </td>
                        <td style="color: #475569;">
                            {{ $actualMessage ?: '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>

    <script>
        let hasPrinted = false;
        function doPrint() {
            if (hasPrinted) return;
            hasPrinted = true;
            window.print();
        }
        window.addEventListener('load', () => {
            setTimeout(doPrint, 1000);
        });
        window.addEventListener('focus', () => {
            setTimeout(doPrint, 200);
        });
    </script>
</body>
</html>
