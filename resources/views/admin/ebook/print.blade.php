<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS eBook Upload Tracking Report</title>
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
        table th { background: #f8fafc !important; color: #1e293b !important; font-family: Arial, sans-serif !important; font-weight: bold !important; font-size: 10px !important; padding: 8px 10px !important; text-transform: uppercase !important; text-align: left; }
        table td { border: none !important; padding: 8px 10px !important; font-family: Arial, sans-serif !important; font-size: 9px !important; color: #334155 !important; background: transparent !important; border-bottom: 1px solid #f1f5f9 !important; }
        tr { page-break-inside: avoid !important; }
        .page-break-after { page-break-after: always !important; break-after: page !important; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mb-10 { margin-bottom: 2.5rem; }
        .text-slate-500 { color: #64748b; }
        .text-slate-800 { color: #1e293b; }
        .text-slate-900 { color: #0f172a; }
        .pb-1\.5 { padding-bottom: 0.375rem; }
        .uppercase { text-transform: uppercase; }
        .tracking-wider { letter-spacing: 0.05em; }
        .mt-1 { margin-top: 0.25rem; }
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
                OFFICIAL EBOOK UPLOAD TRACKING REPORT
            </h1>
            <h2 class="uppercase tracking-wide text-slate-700 font-bold mt-1" style="font-family: Arial, sans-serif; font-size: 10px; margin: 4px 0 0 0; letter-spacing: 0.02em;">
                School Year 2026–2027
            </h2>
            @if(request('search'))
                <div class="text-slate-600 font-semibold" style="font-family: Arial, sans-serif; font-size: 9px; margin-top: 4px;">
                    Search Filter: "{{ request('search') }}"
                </div>
            @endif
            <div class="text-slate-500 font-normal" style="font-family: Arial, sans-serif; font-size: 9px; margin-top: 4px;">
                Report Generated on {{ now()->format('F d, Y h:i A') }}
            </div>
        </div>

        <!-- Grouped by Grade Level -->
        @foreach ($gradeGroups as $gradeName => $gradeBooks)
            <div class="grade-print-section mb-6">
                <h2 class="text-[11px] font-bold text-slate-800 mb-2 pb-1 uppercase tracking-wider" style="font-family: Arial, sans-serif; border-bottom: 1.5px solid #059669;">
                    {{ $gradeName }} <span class="text-slate-500 font-normal">({{ $gradeBooks->count() }} {{ Str::plural('eBook', $gradeBooks->count()) }})</span>
                </h2>
                @if($gradeBooks->isNotEmpty())
                    <table class="w-full text-left text-sm print-table mb-4">
                        <thead>
                            <tr>
                                <th style="width: 5%; text-align: center;">#</th>
                                <th style="width: 35%">eBook Title</th>
                                <th style="width: 25%">Author / Teacher</th>
                                <th style="width: 15%">Date Uploaded</th>
                                <th style="width: 20%">Uploaded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($gradeBooks as $book)
                                <tr>
                                    <td style="text-align: center; font-weight: bold; color: #64748b;">{{ $loop->iteration }}</td>
                                    <td class="font-bold text-slate-900">{{ $book->title }}</td>
                                    <td class="font-semibold text-emerald-800">by {{ $book->author ?? $book->creator?->name ?? 'Unknown' }}</td>
                                    <td>{{ $book->created_at?->format('M d, Y') }}</td>
                                    <td>{{ $book->creator?->name ?? 'Unknown' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-slate-400 font-bold mb-4 italic" style="padding-left: 10px; font-size: 9px;">No eBooks uploaded for this grade level yet.</p>
                @endif
            </div>
        @endforeach
    </main>

    <script>
        window.addEventListener('load', () => {
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
