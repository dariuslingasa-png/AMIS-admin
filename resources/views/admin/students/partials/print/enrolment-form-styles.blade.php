<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Merriweather', Georgia, serif;
        background-color: #f1f5f9;
        color: #0f172a;
        line-height: 1.3;
        padding: 20px 0;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .paper-container {
        background: #ffffff;
        width: 210mm;
        min-height: 297mm;
        margin: 0 auto 20px auto;
        padding: 10mm 12mm 10mm 12mm;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        box-sizing: border-box;
        position: relative;
        overflow: hidden;
    }

    /* School Header Main Table */
    .header-main-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2px;
    }
    .header-main-table td {
        vertical-align: middle;
        padding: 0;
    }

    .logo-box {
        width: 72px;
        height: 72px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .logo-box img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .header-title {
        font-family: 'Merriweather', serif;
        font-size: 1.28rem;
        font-weight: 900;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.15;
    }

    .arabic-header-title {
        font-family: 'Noto Naskh Arabic', 'Amiri', 'Traditional Arabic', serif;
        font-size: 1.35rem;
        font-weight: 700;
        color: #047857;
        direction: rtl;
        line-height: 1.25;
        margin-bottom: 2px;
    }

    .tagline {
        font-family: 'Inter', sans-serif;
        font-size: 0.78rem;
        font-weight: 600;
        color: #475569;
        margin-top: 1px;
    }

    .no-refund-box {
        border: 2px solid #ef4444;
        border-radius: 6px;
        padding: 4px 6px;
        text-align: center;
        color: #dc2626;
        font-family: 'Inter', sans-serif;
        font-weight: 800;
        font-size: 0.70rem;
        line-height: 1.15;
        text-transform: uppercase;
        width: 125px;
    }

    .form-title-badge {
        font-family: 'Inter', sans-serif;
        font-size: 1.22rem;
        font-weight: 900;
        letter-spacing: 0.2px;
        color: #0f172a;
        text-transform: uppercase;
        line-height: 1.1;
    }

    .sy-badge {
        font-family: 'Inter', sans-serif;
        font-size: 0.90rem;
        font-weight: 700;
        color: #0f172a;
        margin-top: 3px;
    }

    .old-new-box {
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.4;
    }

    .photo-2x2-frame {
        width: 38mm;
        height: 38mm;
        border: 1px solid #94a3b8;
        background: #f8fafc;
        box-sizing: border-box;
        overflow: hidden;
        position: relative;
    }

    /* Modality & Shift Checkboxes Bar */
    .modality-bar {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-top: 6px;
        margin-bottom: 2px;
        font-family: 'Inter', sans-serif;
    }

    .modality-title {
        font-family: 'Inter', sans-serif;
        font-size: 0.85rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #0f172a;
    }

    .modality-bar .checkbox-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.84rem;
        font-weight: 800;
        color: #0f172a;
        cursor: pointer;
        user-select: none;
    }

    .modality-bar .custom-checkbox {
        width: 18px;
        height: 18px;
        font-size: 13px;
    }

    /* Section Header Divider */
    .section-header-row {
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #0f172a;
        margin-top: 18px;
        margin-bottom: 10px;
        white-space: nowrap;
    }

    /* Fillable Text Lines & Input Fields with Multiline Wrap & Auto-Expand */
    .field-container {
        margin-bottom: 12px;
        width: 100%;
    }

    .input-line {
        border: none;
        border-bottom: 1.5px solid #0f172a;
        font-family: 'Inter', sans-serif;
        font-size: 0.92rem;
        font-weight: 700;
        color: #0f172a;
        outline: none;
        padding: 2px 2px 1px 2px;
        line-height: 1.25;
        min-height: 20px;
        height: auto;
        width: 100%;
        background: transparent;
        white-space: normal;
        overflow-wrap: anywhere;
        word-wrap: break-word;
        word-break: break-word;
        display: block;
        box-sizing: border-box;
        text-transform: uppercase;
    }

    .label-text {
        font-family: 'Inter', sans-serif;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #475569;
        margin-top: 3px;
        display: block;
    }

    .lrn-input {
        display: inline-block;
        border: none;
        border-bottom: 1.5px solid #0f172a;
        font-family: 'Inter', sans-serif;
        font-size: 0.88rem;
        font-weight: 700;
        color: #0f172a;
        padding: 0 4px;
        line-height: 1.2;
        min-width: 85px;
        min-height: 16px;
        height: auto;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .grid-5-col {
        display: grid;
        grid-template-columns: 2.8fr 2.8fr 2.2fr 0.7fr 1.5fr;
        gap: 10px;
        align-items: end;
    }

    .grid-4-col-birth {
        display: grid;
        grid-template-columns: 1.2fr 2.5fr 3.5fr 2fr;
        gap: 15px;
        align-items: end;
    }

    .grid-2-col-school {
        display: grid;
        grid-template-columns: 5fr 2.5fr;
        gap: 15px;
        align-items: end;
    }

    .grid-parent-row {
        display: grid;
        grid-template-columns: 3.8fr 2.1fr 2.9fr;
        gap: 15px;
        align-items: end;
    }

    .grid-children-row {
        display: grid;
        grid-template-columns: 4.5fr 1.5fr 2.5fr;
        gap: 15px;
        margin-bottom: 8px;
        align-items: end;
    }

    /* Bottom Section: Applicant Lives With */
    .lives-with-row {
        margin-top: 20px;
        display: flex;
        align-items: center;
        gap: 25px;
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        font-weight: 600;
    }

    .radio-option {
        display: flex;
        align-items: baseline;
        gap: 6px;
    }

    .radio-line {
        display: inline-block;
        width: 40px;
        border-bottom: 1.5px solid #0f172a;
        text-align: center;
        font-weight: 800;
        height: 18px;
        line-height: 18px;
    }

    /* PAGE 2 STYLES */
    .p2-question-row {
        margin-top: 16px;
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        line-height: 1.45;
        color: #0f172a;
    }

    .p2-inline-line {
        display: inline-block;
        border-bottom: 1.5px solid #0f172a;
        width: 70px;
        height: 18px;
        vertical-align: bottom;
        text-align: center;
        font-weight: 800;
        font-family: 'Inter', sans-serif;
    }

    .p2-explain-block {
        margin-top: 8px;
        margin-bottom: 14px;
    }

    .p2-explain-label {
        font-family: 'Inter', sans-serif;
        font-size: 0.9rem;
        font-weight: 600;
        color: #334155;
        display: inline-block;
        margin-bottom: 4px;
    }

    .p2-full-line {
        border: none;
        border-bottom: 1.5px solid #0f172a;
        width: 100%;
        font-family: 'Inter', sans-serif;
        font-size: 0.90rem;
        font-weight: 700;
        color: #0f172a;
        outline: none;
        padding: 2px 2px 1px 2px;
        line-height: 1.25;
        min-height: 20px;
        height: auto;
        margin-bottom: 6px;
        background: transparent;
        white-space: normal;
        overflow-wrap: anywhere;
        word-wrap: break-word;
        word-break: break-word;
        display: block;
        box-sizing: border-box;
        text-transform: uppercase;
    }

    .grid-physician-row {
        display: grid;
        grid-template-columns: 4fr 3fr;
        gap: 20px;
        margin-top: 6px;
        margin-bottom: 6px;
        align-items: end;
    }

    .p2-emergency-grid {
        display: grid;
        grid-template-columns: 4.5fr 3.5fr 3fr;
        gap: 15px;
        margin-top: 6px;
        margin-bottom: 8px;
        align-items: end;
    }

    .p2-policy-text {
        font-family: 'Merriweather', serif;
        font-size: 0.88rem;
        line-height: 1.4;
        margin-top: 8px;
        text-align: justify;
        color: #1e293b;
    }

    .signature-grid {
        display: grid;
        grid-template-columns: 5fr 2.5fr;
        gap: 30px;
        margin-top: 18px;
        margin-bottom: 6px;
        align-items: end;
    }

    .signature-disclaimer {
        font-family: 'Inter', sans-serif;
        font-size: 0.80rem;
        font-style: italic;
        color: #64748b;
        margin-bottom: 12px;
    }

    .office-perforated-line {
        border: none;
        border-top: 1.5px dashed #64748b;
        margin: 12px 0 8px 0;
    }

    .office-use-box {
        border: 1px solid #94a3b8;
        padding: 8px 12px;
        margin-top: 4px;
        background: #ffffff;
        border-radius: 4px;
    }

    .office-use-title {
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 8px;
        text-transform: uppercase;
        border-bottom: 1px solid #cbd5e1;
        padding-bottom: 4px;
        letter-spacing: 0.5px;
    }

    .grid-office-row {
        display: grid;
        grid-template-columns: 3.5fr 2.5fr 2.5fr;
        gap: 15px;
        margin-bottom: 12px;
        font-family: 'Inter', sans-serif;
        font-size: 0.90rem;
        font-weight: 700;
        color: #1e293b;
        align-items: end;
    }

    .office-label {
        font-family: 'Inter', sans-serif;
        font-size: 0.90rem;
        font-weight: 700;
        color: #1e293b;
    }

    .date-slash-inputs {
        display: inline-flex;
        align-items: baseline;
        gap: 4px;
    }

    .date-slash-input {
        border: none;
        border-bottom: 1.5px solid #0f172a;
        width: 36px;
        text-align: center;
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        outline: none;
        padding: 1px 2px;
    }

    .doc-chk-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 14px;
        height: 14px;
        border: 1.5px solid #334155;
        border-radius: 2px;
        background: #ffffff;
        text-align: center;
        font-size: 11px;
        font-weight: 900;
        color: #0f172a;
        vertical-align: middle;
        box-sizing: border-box;
        line-height: 1;
    }

    .doc-checklist-table th,
    .doc-checklist-table td {
        font-family: 'Inter', sans-serif;
    }

    .doc-verification-row {
        font-family: 'Inter', sans-serif;
    }

    .page-number-badge {
        display: none !important;
    }

    .student-enrollment-document {
        page-break-after: always;
        break-after: page;
    }

    /* Print Media Styles for Perfect PDF Save */
    @media print {
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        body {
            background: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .action-bar, .action-bar-container, .toolbar, .page-number-badge, #print-skeleton-overlay, .loading-overlay {
            display: none !important;
        }

        .paper-container {
            box-shadow: none !important;
            padding: 0 !important;
            width: 100% !important;
            min-height: auto !important;
            margin: 0 !important;
            border: none !important;
        }

        .student-enrollment-document {
            page-break-after: always !important;
            break-after: page !important;
        }

        .paper-page-break {
            page-break-after: always !important;
            break-after: page !important;
        }

        .form-section-block {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .input-line, .p2-full-line, .lrn-input {
            border-bottom: 1.2px solid #000 !important;
        }

        img {
            max-width: 100% !important;
            display: block !important;
            visibility: visible !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: 210mm 297mm;
            margin: 6mm 10mm;
        }
    }
</style>
