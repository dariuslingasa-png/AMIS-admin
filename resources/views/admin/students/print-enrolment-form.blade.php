<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $isPdf = $isPdf ?? false;
        $app = $applicant ?? $student?->applicant;
        
        // Auto Filename format: Grade_Lastname_FirstName_SY
        $rawGrade = trim($student->grade_level ?: ($app?->grade_level ?: 'Grade'));
        $rawLast = trim($student->last_name ?: ($app?->last_name ?: ($student->full_name ?: 'Student')));
        $rawFirst = trim($student->first_name ?: ($app?->first_name ?: ''));
        $rawMiddle = trim($student->middle_name ?: ($app?->middle_name ?: ''));
        $rawSy = trim($student->school_year ?: ($app?->school_year ?: (config('services.school.year') ?: '2026-2027')));

        $cleanGrade = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $rawGrade), '_');
        $cleanLast = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $rawLast), '_');
        $cleanFirst = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $rawFirst), '_');
        $cleanSy = trim(preg_replace('/[^A-Za-z0-9\-]+/', '_', $rawSy), '_');

        $autoFileName = implode('_', array_filter([$cleanGrade, $cleanLast, $cleanFirst, $cleanSy]));

        $middleInitial = !empty($rawMiddle) ? ' ' . mb_substr($rawMiddle, 0, 1) . '.' : '';
        $formalFormattedName = (!empty($rawLast) && !empty($rawFirst))
            ? mb_strtoupper("{$rawLast}, {$rawFirst}{$middleInitial}")
            : mb_strtoupper($student->full_name ?: ($app?->full_name ?: 'STUDENT'));
    @endphp
    <title>{{ $autoFileName }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
@if(!($isPdf ?? false))
    <!-- Premium Google Fonts: Merriweather for Headers, Inter for Data & Noto Naskh Arabic for Arabic -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Noto+Naskh+Arabic:wght@700&family=Inter:wght@400;500;600;700;800&family=Merriweather:wght@400;700;900&display=swap" rel="stylesheet">
    
    <!-- Cropper.js for 2x2 Photo ID Adjustments -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
@endif

@if(!($isPdf ?? false))
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

        /* Top Action Bar & Form Navigation Header (Screen Only) */
        .action-bar-container {
            max-width: 960px;
            margin: 0 auto 20px auto;
            background: #ffffff;
            padding: 16px 20px;
            border-radius: 14px;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
            border: 1px solid #e2e8f0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            position: sticky;
            top: 12px;
            z-index: 1000;
        }

        .header-main-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f1f5f9;
        }

        .header-identity-group {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .header-icon-box {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #059669;
            flex-shrink: 0;
        }

        .header-title-block {
            min-width: 0;
        }

        .header-title-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .header-page-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            line-height: 1.25;
            letter-spacing: -0.2px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 9999px;
            font-size: 0.70rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1;
            white-space: nowrap;
        }

        .status-approved {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .status-pending {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .header-student-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.80rem;
            color: #64748b;
            margin-top: 3px;
            flex-wrap: wrap;
        }

        .meta-item {
            white-space: nowrap;
        }

        .meta-name {
            color: #0f172a;
            font-weight: 700;
        }

        .meta-id {
            color: #059669;
            font-weight: 700;
        }

        .meta-divider {
            color: #cbd5e1;
            font-weight: bold;
        }

        .header-actions-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .header-btn {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 0.80rem;
            font-weight: 600;
            height: 36px;
            padding: 0 14px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
            box-sizing: border-box;
        }

        .btn-primary {
            background: #059669;
            color: #ffffff;
            border: 1px solid #059669;
            box-shadow: 0 1px 3px rgba(5, 150, 105, 0.25);
            font-weight: 700;
        }

        .btn-primary:hover {
            background: #047857;
            border-color: #047857;
            box-shadow: 0 2px 5px rgba(5, 150, 105, 0.35);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .btn-secondary:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
            transform: translateY(-1px);
        }

        .btn-tertiary {
            background: transparent;
            color: #64748b;
            border: 1px solid transparent;
        }

        .btn-tertiary:hover {
            background: #f1f5f9;
            color: #334155;
            border-color: #e2e8f0;
        }

        .header-tabs-nav {
            margin-top: 12px;
        }

        .tabs-scroll-track {
            display: flex;
            align-items: center;
            gap: 6px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding: 2px 0;
        }

        .tabs-scroll-track::-webkit-scrollbar {
            display: none;
        }

        .nav-tab-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.74rem;
            font-weight: 600;
            text-transform: uppercase;
            text-decoration: none;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
            letter-spacing: 0.3px;
        }

        .nav-tab-item:hover {
            background: #ffffff;
            color: #0f172a;
            border-color: #cbd5e1;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .nav-tab-item.active {
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
            font-weight: 700;
            box-shadow: 0 1px 3px rgba(5, 150, 105, 0.08);
        }

        @media (max-width: 860px) {
            .header-main-row {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            .header-actions-group {
                justify-content: flex-start;
                flex-wrap: wrap;
            }
            .header-btn {
                flex: 1 1 auto;
                min-width: 120px;
            }
        }

        /* Paper Document Layout (A4 Scale) */
        .paper-container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 30px auto;
            background: #ffffff;
            padding: 10mm 14mm;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: relative;
            border-radius: 2px;
        }

        .paper-page-break {
            page-break-after: always;
            break-after: page;
        }

        /* PAGE 1: Header Layout */
        .top-header-row {
            width: 100%;
            margin-bottom: 8px;
        }

        .header-main-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .header-logo-amis {
            width: 76px;
            height: 76px;
            object-fit: contain;
            display: block;
        }

        .header-school-text {
            text-align: center;
            padding: 0 6px;
        }

        .school-arabic-name {
            font-family: 'Amiri', 'Noto Naskh Arabic', 'Traditional Arabic', serif;
            font-size: 1.65rem;
            font-weight: 700;
            color: #047857;
            text-align: center;
            direction: rtl;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .header-arabic-wordmark {
            height: 36px;
            max-width: 360px;
            object-fit: contain;
            display: inline-block;
            margin-bottom: 2px;
        }

        .school-name {
            font-family: 'Merriweather', Georgia, serif;
            font-size: 1.15rem;
            font-weight: 900;
            letter-spacing: 0.3px;
            color: #0f172a;
            text-transform: uppercase;
            white-space: nowrap;
            text-align: center;
            line-height: 1.15;
        }

        .school-address {
            font-family: 'Merriweather', Georgia, serif;
            font-size: 0.82rem;
            font-weight: 600;
            margin-top: 2px;
            color: #334155;
            white-space: nowrap;
            text-align: center;
        }

        .header-logo-deped {
            width: 76px;
            height: 76px;
            object-fit: contain;
            display: inline-block;
        }

        /* NO REFUND OF ENROLLMENT FEE Box styling */
        .refund-notice-box {
            border: 2px solid #dc2626;
            padding: 4px 6px;
            text-align: center;
            font-family: 'Inter', sans-serif;
            font-weight: 800;
            font-size: 0.78rem;
            line-height: 1.15;
            color: #dc2626;
            text-transform: uppercase;
            white-space: nowrap;
            border-radius: 4px;
            margin: 0;
            display: inline-block;
        }

        /* Middle Header Row: Form Title, Checkboxes, 2x2 Photo Box */
        .form-middle-grid {
            display: grid;
            grid-template-columns: 1fr auto 112px;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 12px;
        }

        .form-title-area {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .form-title {
            font-family: 'Merriweather', serif;
            font-size: 1.35rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            color: #0f172a;
        }

        .sy-title {
            font-family: 'Merriweather', serif;
            font-size: 1.15rem;
            font-weight: 700;
            margin-top: 3px;
            margin-bottom: 14px;
            color: #1e293b;
        }

        /* Student Info Header & LRN */
        .student-info-bar {
            display: flex;
            align-items: baseline;
            gap: 15px;
            margin-top: 4px;
        }

        .section-title {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        .lrn-container {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            display: flex;
            align-items: baseline;
            gap: 6px;
            white-space: nowrap;
        }

        .lrn-input {
            border: none;
            border-bottom: 1.5px solid #0f172a;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            width: 200px;
            outline: none;
            padding: 0 4px;
            text-transform: uppercase;
        }

        /* OLD / NEW Checkboxes Vertically Stacked */
        .checkbox-stack {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 25px;
            align-items: flex-start;
            font-family: 'Inter', sans-serif;
            padding-right: 5px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 800;
            color: #0f172a;
            cursor: pointer;
        }

        .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #0f172a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 900;
            background: #fff;
            line-height: 1;
            flex-shrink: 0;
            border-radius: 3px;
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

        /* 2x2 Photo Square Box */
        .photo-box {
            width: 112px;
            height: 112px;
            border: 1px solid #94a3b8;
            background: #f8fafc;
            justify-self: end;
            align-self: flex-start;
            margin-top: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 3px;
            box-sizing: border-box;
            position: relative;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        .photo-edit-overlay-btn {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(15, 23, 42, 0.88);
            color: #ffffff;
            border: none;
            padding: 5px 6px;
            font-size: 0.68rem;
            font-weight: 700;
            font-family: 'Inter', system-ui, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s ease, background 0.15s ease;
            backdrop-filter: blur(4px);
            z-index: 10;
        }

        .photo-box:hover .photo-edit-overlay-btn {
            opacity: 1;
        }

        .photo-edit-overlay-btn:hover {
            background: #059669;
        }

        /* 2x2 Photo Adjuster Modal */
        .photo-cropper-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.70);
            backdrop-filter: blur(6px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            animation: cropperFadeIn 0.2s ease-out forwards;
        }
        @keyframes cropperFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .photo-cropper-card {
            width: 100%;
            max-width: 500px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            border: 1px solid #e2e8f0;
            animation: cropperSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes cropperSlideUp {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .photo-cropper-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            background: #ffffff;
        }
        .cropper-close-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 6px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }
        .cropper-close-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .photo-cropper-body {
            padding: 16px 20px;
            background: #ffffff;
        }
        .cropper-controls-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 12px;
            padding: 8px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }
        .cropper-tool-btn {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }
        .cropper-tool-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }
        .photo-cropper-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 20px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        .cropper-btn-cancel {
            padding: 8px 16px;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 0.82rem;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .cropper-btn-cancel:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .cropper-btn-save {
            padding: 8px 18px;
            border-radius: 8px;
            background: #059669;
            border: none;
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 0.82rem;
            font-weight: 700;
            color: #ffffff;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(5, 150, 105, 0.3);
            transition: all 0.15s ease;
        }
        .cropper-btn-save:hover {
            background: #047857;
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.4);
        }
        .cropper-btn-save:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        @include('admin.students.partials.print.enrolment-form-styles')
    </style>
@endif
</head>
<body>

    @if(!($isPdf ?? false))
    <!-- Page Skeleton Loading Overlay (Fades out when fully loaded) -->
    <div id="print-skeleton-overlay" style="position: fixed; inset: 0; background: #f8fafc; z-index: 99999; overflow: hidden; padding: 20px; transition: opacity 0.25s ease;">
        <div style="max-width: 1000px; margin: 0 auto;">
            <!-- Action Bar Skeleton -->
            <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center;" class="animate-pulse">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #059669;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                    </div>
                    <div style="width: 180px; height: 16px; border-radius: 6px; background: #cbd5e1;"></div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <div style="width: 80px; height: 32px; border-radius: 8px; background: #e2e8f0;"></div>
                    <div style="width: 100px; height: 32px; border-radius: 8px; background: #ecfdf5;"></div>
                </div>
            </div>

            <!-- Main Document Container Skeleton -->
            <div style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 32px; margin-top: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04);" class="animate-pulse">
                <!-- Header Logo & Title Skeleton -->
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #e2e8f0; margin-bottom: 12px;" class="skeleton-shimmer"></div>
                    <div style="width: 320px; height: 20px; border-radius: 6px; background: #e2e8f0; margin-bottom: 8px;" class="skeleton-shimmer"></div>
                    <div style="width: 220px; height: 14px; border-radius: 6px; background: #f1f5f9;" class="skeleton-shimmer"></div>
                </div>

                <!-- Info Grid Skeleton -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 28px;">
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9;">
                        <div style="width: 80px; height: 10px; background: #cbd5e1; border-radius: 4px; margin-bottom: 8px;"></div>
                        <div style="width: 160px; height: 16px; background: #e2e8f0; border-radius: 6px;"></div>
                    </div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9;">
                        <div style="width: 80px; height: 10px; background: #cbd5e1; border-radius: 4px; margin-bottom: 8px;"></div>
                        <div style="width: 160px; height: 16px; background: #e2e8f0; border-radius: 6px;"></div>
                    </div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9;">
                        <div style="width: 80px; height: 10px; background: #cbd5e1; border-radius: 4px; margin-bottom: 8px;"></div>
                        <div style="width: 160px; height: 16px; background: #e2e8f0; border-radius: 6px;"></div>
                    </div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #f1f5f9;">
                        <div style="width: 80px; height: 10px; background: #cbd5e1; border-radius: 4px; margin-bottom: 8px;"></div>
                        <div style="width: 160px; height: 16px; background: #e2e8f0; border-radius: 6px;"></div>
                    </div>
                </div>

                <!-- Table Rows Skeleton -->
                <div style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                    <div style="background: #f8fafc; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between;">
                        <div style="width: 120px; height: 12px; background: #cbd5e1; border-radius: 4px;"></div>
                        <div style="width: 80px; height: 12px; background: #cbd5e1; border-radius: 4px;"></div>
                    </div>
                    <div style="padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                        <div style="height: 14px; background: #f1f5f9; border-radius: 4px; width: 90%;" class="skeleton-shimmer"></div>
                        <div style="height: 14px; background: #f1f5f9; border-radius: 4px; width: 75%;" class="skeleton-shimmer"></div>
                        <div style="height: 14px; background: #f1f5f9; border-radius: 4px; width: 85%;" class="skeleton-shimmer"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .animate-spin { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .skeleton-shimmer {
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: shimmerAnimation 1.5s infinite;
        }
        @keyframes shimmerAnimation {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        @media print {
            #print-skeleton-overlay { display: none !important; }
        }
    </style>

    <script>
        (function() {
            function hideSkeleton() {
                const el = document.getElementById('print-skeleton-overlay');
                if (el) {
                    el.style.opacity = '0';
                    setTimeout(() => {
                        if (el && el.parentNode) el.parentNode.removeChild(el);
                    }, 250);
                }
            }
            if (document.readyState === 'interactive' || document.readyState === 'complete') {
                setTimeout(hideSkeleton, 50);
            } else {
                document.addEventListener('DOMContentLoaded', () => setTimeout(hideSkeleton, 50));
            }
            window.addEventListener('load', hideSkeleton);
            window.addEventListener('pageshow', hideSkeleton);
            setTimeout(hideSkeleton, 500);
        })();
    </script>

    <!-- Top Action Bar & Student Form Switcher Navigation Bar -->
    <header class="action-bar-container">
        <!-- Row 1: Document & Student Profile Info + Actions -->
        <div class="header-main-row">
            <!-- Left: Document Title, Status Badge & Student Meta -->
            <div class="header-identity-group">
                <div class="header-icon-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                </div>
                <div class="header-title-block">
                    <div class="header-title-row">
                        <h1 class="header-page-title">Enrollment Application Form</h1>
                        @if($isApproved ?? true)
                            <span class="status-badge status-approved">
                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Official Approved Form
                            </span>
                        @else
                            <span class="status-badge status-pending">
                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                Draft Preview
                            </span>
                        @endif
                    </div>
                    @php
                        $studentDisplayName = $student->full_name ?: ($applicant?->first_name ? trim($applicant->first_name . ' ' . $applicant->last_name) : null);
                    @endphp
                    <div class="header-student-meta">
                        @if($studentDisplayName)
                            <span class="meta-item">Student: <strong class="meta-name">{{ $studentDisplayName }}</strong></span>
                            <span class="meta-divider">•</span>
                        @endif
                        <span class="meta-item">AMIS ID: <strong class="meta-id">#{{ $student->student_number }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Right: Action Buttons Group (Equal Height, Clear Visual Hierarchy) -->
            <div class="header-actions-group">
                <!-- Tertiary: Close Button -->
                <button onclick="window.close()" class="header-btn btn-tertiary" title="Close this window">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    <span>Close</span>
                </button>

                <!-- Secondary: Download Dropdown (DOCX & PDF) -->
                <div class="download-dropdown-wrapper" style="position: relative; display: inline-block;">
                    <button type="button" onclick="toggleDownloadDropdown(event)" id="btn-download-docx" class="header-btn btn-secondary" title="Choose download options (DOCX / PDF)" aria-haspopup="true" aria-expanded="false" style="cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <span>Download</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 2px;"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>

                    <div id="downloadDropdownMenu" class="download-dropdown-menu" style="display: none; position: absolute; right: 0; top: calc(100% + 8px); width: 310px; background: #ffffff; border-radius: 14px; border: 1px solid #cbd5e1; box-shadow: 0 14px 35px rgba(15, 23, 42, 0.12), 0 4px 10px rgba(15, 23, 42, 0.06); padding: 6px; z-index: 9999; font-family: 'Inter', system-ui, sans-serif;">
                        
                        <!-- Section 1 Header: Word Documents (.docx) -->
                        <div style="padding: 6px 10px 4px 10px; font-size: 0.65rem; font-weight: 800; color: #0284c7; text-transform: uppercase; letter-spacing: 0.5px;">
                            Word Documents (.docx)
                        </div>

                        <!-- Option 1: Enrollment Form Only (DOCX) -->
                        <button type="button" onclick="downloadSingleFormDocx(false); closeDownloadDropdown();" class="download-menu-item" style="width: 100%; border: none; background: transparent; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 8px; color: #0f172a; transition: background 0.15s ease;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                            <div style="width: 30px; height: 30px; border-radius: 8px; background: #e0f2fe; border: 1px solid #bae6fd; display: flex; align-items: center; justify-content: center; color: #0284c7; shrink-0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                            <div>
                                <div style="font-size: 0.80rem; font-weight: 800; color: #0f172a; line-height: 1.2;">Enrollment Form Only</div>
                                <div style="font-size: 0.68rem; font-weight: 500; color: #64748b; margin-top: 1px;">Official 2-Page Form (.docx)</div>
                            </div>
                        </button>

                        <!-- Option 2: Enrollment Form with Attachments (DOCX) -->
                        <button type="button" onclick="downloadSingleFormDocx(true); closeDownloadDropdown();" class="download-menu-item" style="width: 100%; border: none; background: transparent; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 8px; color: #0f172a; transition: background 0.15s ease;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                            <div style="width: 30px; height: 30px; border-radius: 8px; background: #e0f2fe; border: 1px solid #bae6fd; display: flex; align-items: center; justify-content: center; color: #0284c7; shrink-0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                            </div>
                            <div>
                                <div style="font-size: 0.80rem; font-weight: 800; color: #0f172a; line-height: 1.2;">Enrollment Form + Attachments</div>
                                <div style="font-size: 0.68rem; font-weight: 500; color: #64748b; margin-top: 1px;">Form + Supporting Documents (.docx)</div>
                            </div>
                        </button>

                        <div style="height: 1px; background: #e2e8f0; margin: 6px 8px;"></div>

                        <!-- Section 2 Header: PDF Documents (.pdf) -->
                        <div style="padding: 4px 10px 4px 10px; font-size: 0.65rem; font-weight: 800; color: #dc2626; text-transform: uppercase; letter-spacing: 0.5px;">
                            PDF Documents (.pdf)
                        </div>

                        <!-- Option 3: Enrollment Form Only (PDF) -->
                        <button type="button" onclick="downloadSingleFormPdf(false); closeDownloadDropdown();" class="download-menu-item" style="width: 100%; border: none; background: transparent; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 8px; color: #0f172a; transition: background 0.15s ease;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                            <div style="width: 30px; height: 30px; border-radius: 8px; background: #fef2f2; border: 1px solid #fecaca; display: flex; align-items: center; justify-content: center; color: #dc2626; shrink-0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M10 12h4"/><path d="M10 16h4"/></svg>
                            </div>
                            <div>
                                <div style="font-size: 0.80rem; font-weight: 800; color: #0f172a; line-height: 1.2;">Enrollment Form Only</div>
                                <div style="font-size: 0.68rem; font-weight: 500; color: #64748b; margin-top: 1px;">Official 2-Page Form (.pdf)</div>
                            </div>
                        </button>

                        <!-- Option 4: Enrollment Form with Attachments (PDF) -->
                        <button type="button" onclick="downloadSingleFormPdf(true); closeDownloadDropdown();" class="download-menu-item" style="width: 100%; border: none; background: transparent; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 8px; color: #0f172a; transition: background 0.15s ease;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                            <div style="width: 30px; height: 30px; border-radius: 8px; background: #fef2f2; border: 1px solid #fecaca; display: flex; align-items: center; justify-content: center; color: #dc2626; shrink-0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                            </div>
                            <div>
                                <div style="font-size: 0.80rem; font-weight: 800; color: #0f172a; line-height: 1.2;">Enrollment Form + Attachments</div>
                                <div style="font-size: 0.68rem; font-weight: 500; color: #64748b; margin-top: 1px;">Form + Supporting Documents (.pdf)</div>
                            </div>
                        </button>

                    </div>
                </div>

                <!-- Primary CTA: Print Form -->
                <button onclick="triggerPrintPDF()" class="header-btn btn-primary" title="Print Enrollment Application Form">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                    <span>Print Form</span>
                </button>
            </div>
        </div>

        <!-- Row 2: Sleek Segmented Tab Switcher Bar (SAME TAB NAVIGATION - target="_self") -->
        <nav class="header-tabs-nav" aria-label="Student Forms Navigation">
            <div class="tabs-scroll-track">
                <!-- ID FORM -->
                <a href="{{ route('admin.students.index', ['search' => $student->student_number, 'print_id' => 1]) }}" target="_self" class="nav-tab-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h3v3H7z"/><path d="M14 7h3"/><path d="M14 11h3"/><path d="M7 14h10"/><path d="M7 17h10"/></svg>
                    <span>ID Form</span>
                </a>

                <!-- MICROSOFT ACCOUNT FORM -->
                <a href="{{ route('admin.students.index', ['search' => $student->student_number, 'print_credentials' => 1]) }}" target="_self" class="nav-tab-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18v3c0 .6.4 1 1 1h4v-3h3v-3h2l1.4-1.4a6.5 6.5 0 1 0-4-4Z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg>
                    <span>Microsoft Account Form</span>
                </a>

                <!-- ENROLLMENT FORM (ACTIVE) -->
                <a href="{{ route('admin.students.print-enrolment-form', $student) }}" target="_self" class="nav-tab-item active">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                    <span>Enrollment Form</span>
                </a>

                <!-- GRADE FORM -->
                <a href="{{ route('admin.students.show', $student) }}" target="_self" class="nav-tab-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    <span>Grade Form</span>
                </a>

                <!-- DOCUMENTS FORM -->
                <a href="{{ route('admin.students.index', ['search' => $student->student_number, 'print_documents' => 1]) }}" target="_self" class="nav-tab-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L8.6 3.3A2 2 0 0 0 6.9 2.5H4a2 2 0 0 0-2 2v13.5a2 2 0 0 0 2 2z"/></svg>
                    <span>Documents Form</span>
                </a>
            </div>
        </nav>
    </header>
    @endif @include('admin.students.partials.print.enrolment-form-body', [
        'student'    => $student,
        'applicant'  => $applicant,
        'siblings'   => $siblings,
        'pageNumber' => 1,
        'totalPages' => 1,
    ])

    @if(!($isPdf ?? false))
        @php
            $app = $applicant ?? $student?->applicant;
            $photoUrl = \App\Support\EnrollmentStorage::url($app?->photo_2x2_url);
            $birthCertUrl = \App\Support\EnrollmentStorage::url($app?->birth_cert_url);
            $reportCardUrl = \App\Support\EnrollmentStorage::url($app?->report_card_url);
            $marriageUrl = \App\Support\EnrollmentStorage::url($app?->marriage_contract_url);
            $medicalUrl = \App\Support\EnrollmentStorage::url($app?->medical_record_url);
            $affidavitUrl = \App\Support\EnrollmentStorage::url($app?->affidavit_url);

            $lastName = trim($student->last_name ?: ($app?->last_name ?: ''));
            $firstName = trim($student->first_name ?: ($app?->first_name ?: ''));
            $middleName = trim($student->middle_name ?: ($app?->middle_name ?: ''));
            $middleInitial = !empty($middleName) ? ' ' . mb_substr($middleName, 0, 1) . '.' : '';
            $formalFormattedName = !empty($lastName) && !empty($firstName)
                ? mb_strtoupper("{$lastName}, {$firstName}{$middleInitial}")
                : mb_strtoupper($student->full_name ?: ($app?->full_name ?: 'STUDENT'));

            $attachedDocumentsList = [
                [
                    'key' => 'photo_2x2',
                    'label' => '2x2 ID Photo',
                    'url' => $photoUrl,
                    'is_photo' => true,
                    'mandatory' => true,
                    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #0284c7;"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>',
                ],
                [
                    'key' => 'birth_cert',
                    'label' => 'Birth Certificate (PSA / NSO)',
                    'url' => $birthCertUrl,
                    'is_photo' => false,
                    'mandatory' => true,
                    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #059669;"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>',
                ],
                [
                    'key' => 'report_card',
                    'label' => 'Report Card (Form 138 / SF9)',
                    'url' => $reportCardUrl,
                    'is_photo' => false,
                    'mandatory' => ($app?->student_type !== 'Old'),
                    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #7c3aed;"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
                ],
                [
                    'key' => 'marriage_contract',
                    'label' => 'Marriage Contract of Parents',
                    'url' => $marriageUrl,
                    'is_photo' => false,
                    'mandatory' => false,
                    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #d97706;"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>',
                ],
                [
                    'key' => 'medical_record',
                    'label' => 'Medical History Records',
                    'url' => $medicalUrl,
                    'is_photo' => false,
                    'mandatory' => false,
                    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #dc2626;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
                ],
            ];
            if (!empty($affidavitUrl)) {
                $attachedDocumentsList[] = [
                    'key' => 'affidavit',
                    'label' => 'Temporary Proof (Affidavit / Form 137)',
                    'url' => $affidavitUrl,
                    'is_photo' => false,
                    'mandatory' => false,
                    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #475569;"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h18"/></svg>',
                ];
            }

            foreach ($attachedDocumentsList as &$docItem) {
                $docItem['data_uri'] = $docItem['url'];
                if (!empty($docItem['url'])) {
                    $cleanPath = ltrim(str_replace(['/storage/', 'storage/'], '', parse_url($docItem['url'], PHP_URL_PATH) ?? ''), '/');
                    $localCandidate = storage_path('app/public/' . $cleanPath);
                    if (file_exists($localCandidate)) {
                        $mime = @mime_content_type($localCandidate) ?: 'image/jpeg';
                        $docItem['data_uri'] = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($localCandidate));
                    }
                }
            }
            unset($docItem);
        @endphp

        <!-- Attached Documents Gallery Section (Web View - Hidden in Print) -->
        <section class="student-attached-documents-section no-print" style="max-width: 820px; margin: 32px auto 40px auto; background: #ffffff; border-radius: 16px; padding: 24px 28px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0,0,0,0.04); font-family: 'Inter', system-ui, -apple-system, sans-serif;">
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 18px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #ecfdf5; border: 1px solid #a7f3d0; display: flex; align-items: center; justify-content: center; color: #059669;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L8.6 3.3A2 2 0 0 0 6.9 2.5H4a2 2 0 0 0-2 2v13.5a2 2 0 0 0 2 2z"/></svg>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #0f172a; line-height: 1.2;">
                            Attached Student Documents
                        </h3>
                        <p style="margin: 2px 0 0 0; font-size: 0.75rem; font-weight: 600; color: #64748b;">
                            Uploaded credentials for verification • Scroll down to inspect
                        </p>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.92rem; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.3px;">
                        {{ $formalFormattedName }}
                    </div>
                    <div style="font-size: 0.70rem; font-weight: 700; color: #059669; margin-top: 1px;">
                        AMIS ID: #{{ $student->student_number }}
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px;">
                @foreach($attachedDocumentsList as $doc)
                    @php
                        $hasFile = !empty($doc['url']);
                        $isDocPdf = $hasFile && strtolower(pathinfo(parse_url($doc['url'], PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) === 'pdf';
                    @endphp

                    <div style="border-radius: 12px; border: 1px solid {{ $hasFile ? '#e2e8f0' : '#fecaca' }}; background: {{ $hasFile ? '#ffffff' : '#fff5f5' }}; padding: 14px; display: flex; flex-direction: column; justify-content: space-between; gap: 10px;">
                        <!-- Header -->
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 28px; height: 28px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; shrink-0;">
                                    {!! $doc['svg'] !!}
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 0.78rem; font-weight: 800; color: #0f172a; line-height: 1.2;">
                                        {{ $doc['label'] }}
                                    </h4>
                                    <span style="font-size: 0.62rem; font-weight: 700; color: {{ $doc['mandatory'] ? '#e11d48' : '#64748b' }}; text-transform: uppercase;">
                                        {{ $doc['mandatory'] ? 'Mandatory' : 'Optional' }}
                                    </span>
                                </div>
                            </div>
                            <span style="background: {{ $hasFile ? '#ecfdf5' : '#ffe4e6' }}; color: {{ $hasFile ? '#047857' : '#be123c' }}; border: 1px solid {{ $hasFile ? '#a7f3d0' : '#fecaca' }}; padding: 2px 6px; border-radius: 4px; font-size: 0.60rem; font-weight: 800; text-transform: uppercase; white-space: nowrap;">
                                {{ $hasFile ? 'ATTACHED' : 'MISSING' }}
                            </span>
                        </div>

                        <!-- Preview Thumbnail -->
                        <div style="height: 120px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            @if($hasFile && !$isDocPdf)
                                <a href="{{ $doc['url'] }}" target="_blank" title="Click to open HD image" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                    <img src="{{ $doc['url'] }}" alt="{{ $doc['label'] }}" style="max-width: 100%; max-height: 100%; object-fit: contain; padding: 4px;">
                                </a>
                            @elseif($hasFile && $isDocPdf)
                                <a href="{{ $doc['url'] }}" target="_blank" title="Click to view PDF" style="display: flex; flex-direction: column; align-items: center; gap: 4px; color: #dc2626; text-decoration: none;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <span style="font-size: 0.65rem; font-weight: 800; color: #991b1b; text-transform: uppercase;">PDF Document</span>
                                </a>
                            @else
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 4px; color: #94a3b8; text-align: center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                    <span style="font-size: 0.65rem; font-weight: 700; color: #94a3b8;">No File Uploaded</span>
                                </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div>
                            @if($hasFile)
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                                    <a href="{{ $doc['url'] }}" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; gap: 4px; padding: 6px 10px; border-radius: 6px; font-size: 0.70rem; font-weight: 700; text-decoration: none; background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1;">
                                        <span>View HD</span>
                                    </a>
                                    <a href="{{ $doc['url'] }}" download target="_blank" style="display: inline-flex; align-items: center; justify-content: center; gap: 4px; padding: 6px 10px; border-radius: 6px; font-size: 0.70rem; font-weight: 700; text-decoration: none; background: #059669; color: #ffffff; border: none;">
                                        <span>Download</span>
                                    </a>
                                </div>
                            @else
                                <span style="display: block; text-align: center; font-size: 0.70rem; font-weight: 700; color: #94a3b8; padding: 5px 0;">
                                    Unavailable
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <script>
        const attachedDocFiles = @json($attachedDocumentsList ?? []);

        function toggleDownloadDropdown(e) {
            e.stopPropagation();
            const menu = document.getElementById('downloadDropdownMenu');
            const btn = document.getElementById('btn-download-docx');
            if (!menu) return;
            const isHidden = menu.style.display === 'none' || menu.style.display === '';
            menu.style.display = isHidden ? 'block' : 'none';
            if (btn) btn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        }

        function closeDownloadDropdown() {
            const menu = document.getElementById('downloadDropdownMenu');
            const btn = document.getElementById('btn-download-docx');
            if (menu) menu.style.display = 'none';
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }

        document.addEventListener('click', function(e) {
            const wrapper = document.querySelector('.download-dropdown-wrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                closeDownloadDropdown();
            }
        });

        function triggerPrintPDF() {
            window.print();
        }

        async function fitAllFormFontSizes() {
            if (document.fonts && document.fonts.ready) {
                try {
                    await document.fonts.ready;
                } catch (e) {
                    console.warn('Font loading check:', e);
                }
            }

            await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));

            const fields = document.querySelectorAll('.input-line, .p2-full-line, .lrn-input');
            const defaultSize = 13.5;
            const minSize = 9.5;

            fields.forEach(el => {
                const text = el.textContent ? el.textContent.trim() : '';
                if (!text || text === '\u00a0' || text === '&nbsp;') {
                    el.style.fontSize = `${defaultSize}px`;
                    return;
                }

                // Reset to default standard font size
                el.style.fontSize = `${defaultSize}px`;
                el.style.whiteSpace = 'nowrap';
                el.style.transform = 'none';

                const clientWidth = el.clientWidth;
                if (clientWidth <= 0) return;

                // If text fits in container, keep default size (Never shrink short text like AAYAN, C., F, KINDER 1)
                if (el.scrollWidth <= clientWidth + 1) {
                    return;
                }

                // If text overflows, progressively reduce in 0.25px steps down to minSize
                let currentSize = defaultSize;
                while (el.scrollWidth > el.clientWidth + 1 && currentSize > minSize) {
                    currentSize -= 0.25;
                    el.style.fontSize = `${currentSize}px`;
                }

                // If it still overflows at minimum size, allow word wrap
                if (el.scrollWidth > el.clientWidth + 1) {
                    el.style.whiteSpace = 'normal';
                    el.style.overflowWrap = 'anywhere';
                    el.style.wordBreak = 'break-word';
                }
            });
        }
        document.addEventListener('DOMContentLoaded', fitAllFormFontSizes);
        window.addEventListener('load', fitAllFormFontSizes);

        async function downloadSingleFormDocx(withAttachments = false) {
            fitAllFormFontSizes();
            const formPages = document.querySelectorAll('.paper-container');
            if (!formPages || formPages.length === 0) return;

            const btn = document.getElementById('btn-download-docx');
            const originalBtnHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<svg class="animate-spin" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg><span>Generating DOCX...</span>';
            }

            try {
                if (typeof html2canvas === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
                    document.head.appendChild(script);
                    await new Promise(res => script.onload = res);
                }
                if (typeof JSZip === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js';
                    document.head.appendChild(script);
                    await new Promise(res => script.onload = res);
                }

                const zip = new JSZip();
                const xmlHeader = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>';

                zip.file('[Content_Types].xml', `${xmlHeader}
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Default Extension="png" ContentType="image/png"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>`);

                zip.file('_rels/.rels', `${xmlHeader}
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>`);

                let docRels = `${xmlHeader}
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">`;
                let docBody = '';
                let totalImageCount = 0;

                // 1. Capture Main Form Pages (Page 1 & Page 2)
                for (let i = 0; i < formPages.length; i++) {
                    totalImageCount++;
                    const canvas = await html2canvas(formPages[i], { scale: 2.2, useCORS: true, logging: false });
                    const imgDataUrl = canvas.toDataURL('image/png');
                    const base64Data = imgDataUrl.replace(/^data:image\/png;base64,/, '');

                    const imgId = `rId${totalImageCount + 1}`;
                    const imgFileName = `image${totalImageCount}.png`;

                    zip.file(`word/media/${imgFileName}`, base64Data, { base64: true });
                    docRels += `\n    <Relationship Id="${imgId}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/${imgFileName}"/>`;

                    if (totalImageCount > 1) {
                        docBody += '<w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/></w:pPr><w:r><w:br w:type="page"/></w:r></w:p>';
                    }

                    docBody += `
<w:p>
    <w:pPr>
        <w:jc w:val="center"/>
        <w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/>
    </w:pPr>
    <w:r>
        <w:drawing>
            <wp:inline distT="0" distB="0" distL="0" distR="0">
                <wp:extent cx="7560000" cy="10440000"/>
                <wp:docPr id="${totalImageCount}" name="Page ${totalImageCount}"/>
                <a:graphic>
                    <a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
                        <pic:pic>
                            <pic:nvPicPr>
                                <pic:cNvPr id="0" name="Picture"/>
                                <pic:cNvPicPr/>
                            </pic:nvPicPr>
                            <pic:blipFill>
                                <a:blip r:embed="${imgId}"/>
                                <a:stretch><a:fillRect/></a:stretch>
                            </pic:blipFill>
                            <pic:spPr>
                                <a:xfrm>
                                    <a:off x="0" y="0"/>
                                    <a:ext cx="7560000" cy="10440000"/>
                                </a:xfrm>
                                <a:prstGeom prst="rect"><a:avLst/></a:prstGeom>
                            </pic:spPr>
                        </pic:pic>
                    </a:graphicData>
                </a:graphic>
            </wp:inline>
        </w:drawing>
    </w:r>
</w:p>`;
                }

                // 2. If withAttachments is selected, capture available uploaded supporting documents
                if (withAttachments && typeof attachedDocFiles !== 'undefined' && Array.isArray(attachedDocFiles)) {
                    for (const doc of attachedDocFiles) {
                        if (!doc.url) continue;

                        const isDocPdf = doc.url.toLowerCase().endsWith('.pdf') || doc.url.toLowerCase().includes('.pdf?');
                        if (isDocPdf) continue;

                        const tempContainer = document.createElement('div');
                        tempContainer.style.width = '794px';
                        tempContainer.style.minHeight = '1123px';
                        tempContainer.style.background = '#ffffff';
                        tempContainer.style.padding = '36px 44px';
                        tempContainer.style.boxSizing = 'border-box';
                        tempContainer.style.position = 'fixed';
                        tempContainer.style.left = '-9999px';
                        tempContainer.style.top = '0';
                        tempContainer.style.zIndex = '-1000';
                        tempContainer.style.fontFamily = "'Inter', system-ui, sans-serif";

                        tempContainer.innerHTML = `
                            <div style="display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2.5px solid #059669; padding-bottom: 8px; margin-bottom: 20px;">
                                <div>
                                    <div style="font-size: 15px; font-weight: 900; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                                        ATTACHMENT: ${doc.label}
                                    </div>
                                    <div style="font-size: 11px; font-weight: 600; color: #64748b; margin-top: 3px;">
                                        AMIS ID: #{{ $student->student_number }} • Grade: {{ $student->grade_level }}
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 14px; font-weight: 900; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                                        {{ addslashes($formalFormattedName) }}
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: center; height: 960px; background: #fafafa; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; padding: 12px;">
                                <img src="${doc.data_uri || doc.url}" crossorigin="anonymous" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            </div>
                        `;

                        document.body.appendChild(tempContainer);

                        await new Promise(r => {
                            const img = tempContainer.querySelector('img');
                            if (!img || img.complete) r();
                            else { img.onload = r; img.onerror = r; }
                        });

                        totalImageCount++;
                        const canvas = await html2canvas(tempContainer, { scale: 2, useCORS: true, logging: false });
                        document.body.removeChild(tempContainer);

                        const imgDataUrl = canvas.toDataURL('image/png');
                        const base64Data = imgDataUrl.replace(/^data:image\/png;base64,/, '');

                        const imgId = `rId${totalImageCount + 1}`;
                        const imgFileName = `image${totalImageCount}.png`;

                        zip.file(`word/media/${imgFileName}`, base64Data, { base64: true });
                        docRels += `\n    <Relationship Id="${imgId}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/${imgFileName}"/>`;

                        docBody += '<w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/></w:pPr><w:r><w:br w:type="page"/></w:r></w:p>';
                        docBody += `
<w:p>
    <w:pPr>
        <w:jc w:val="center"/>
        <w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/>
    </w:pPr>
    <w:r>
        <w:drawing>
            <wp:inline distT="0" distB="0" distL="0" distR="0">
                <wp:extent cx="7560000" cy="10440000"/>
                <wp:docPr id="${totalImageCount}" name="Attachment ${totalImageCount - 2}"/>
                <a:graphic>
                    <a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
                        <pic:pic>
                            <pic:nvPicPr>
                                <pic:cNvPr id="0" name="Picture"/>
                                <pic:cNvPicPr/>
                            </pic:nvPicPr>
                            <pic:blipFill>
                                <a:blip r:embed="${imgId}"/>
                                <a:stretch><a:fillRect/></a:stretch>
                            </pic:blipFill>
                            <pic:spPr>
                                <a:xfrm>
                                    <a:off x="0" y="0"/>
                                    <a:ext cx="7560000" cy="10440000"/>
                                </a:xfrm>
                                <a:prstGeom prst="rect"><a:avLst/></a:prstGeom>
                            </pic:spPr>
                        </pic:pic>
                    </a:graphicData>
                </a:graphic>
            </wp:inline>
        </w:drawing>
    </w:r>
</w:p>`;
                    }
                }

                docRels += '\n</Relationships>';
                zip.file('word/_rels/document.xml.rels', docRels);

                const docXml = `${xmlHeader}
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
            xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
            xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
            xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
    <w:body>
        ${docBody}
        <w:sectPr>
            <w:pgSz w:w="11906" w:h="16838"/>
            <w:pgMar w:top="0" w:right="0" w:bottom="0" w:left="0" w:header="0" w:footer="0" w:gutter="0"/>
        </w:sectPr>
    </w:body>
</w:document>`;

                zip.file('word/document.xml', docXml);

                const content = await zip.generateAsync({ type: 'blob' });
                const url = URL.createObjectURL(content);
                const a = document.createElement('a');
                a.href = url;
                const autoFileName = '{{ $autoFileName }}';
                a.download = withAttachments 
                    ? `${autoFileName}_With_Attachments.docx` 
                    : `${autoFileName}.docx`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch (err) {
                console.error('Error generating DOCX:', err);
                alert('An error occurred while generating the DOCX file. Please try again.');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                }
            }
        }

        async function downloadSingleFormPdf(withAttachments = false) {
            fitAllFormFontSizes();
            const formPages = document.querySelectorAll('.paper-container');
            if (!formPages || formPages.length === 0) return;

            const btn = document.getElementById('btn-download-docx');
            const originalBtnHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<svg class="animate-spin" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg><span>Generating PDF...</span>';
            }

            try {
                if (typeof html2canvas === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
                    document.head.appendChild(script);
                    await new Promise(res => script.onload = res);
                }
                if (typeof window.jspdf === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                    document.head.appendChild(script);
                    await new Promise(res => script.onload = res);
                }

                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4',
                    compress: true
                });

                let pageIndex = 0;

                // 1. Capture Main Form Pages (Page 1 & Page 2)
                for (let i = 0; i < formPages.length; i++) {
                    if (pageIndex > 0) {
                        pdf.addPage('a4', 'portrait');
                    }
                    const canvas = await html2canvas(formPages[i], { scale: 2.2, useCORS: true, logging: false });
                    const imgData = canvas.toDataURL('image/jpeg', 0.95);
                    pdf.addImage(imgData, 'JPEG', 0, 0, 210, 297, undefined, 'FAST');
                    pageIndex++;
                }

                // 2. If withAttachments is selected, capture available uploaded supporting documents
                if (withAttachments && typeof attachedDocFiles !== 'undefined' && Array.isArray(attachedDocFiles)) {
                    for (const doc of attachedDocFiles) {
                        if (!doc.url) continue;

                        const isDocPdf = doc.url.toLowerCase().endsWith('.pdf') || doc.url.toLowerCase().includes('.pdf?');
                        if (isDocPdf) continue;

                        const tempContainer = document.createElement('div');
                        tempContainer.style.width = '794px';
                        tempContainer.style.minHeight = '1123px';
                        tempContainer.style.background = '#ffffff';
                        tempContainer.style.padding = '36px 44px';
                        tempContainer.style.boxSizing = 'border-box';
                        tempContainer.style.position = 'fixed';
                        tempContainer.style.left = '-9999px';
                        tempContainer.style.top = '0';
                        tempContainer.style.zIndex = '-1000';
                        tempContainer.style.fontFamily = "'Inter', system-ui, sans-serif";

                        tempContainer.innerHTML = `
                            <div style="display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2.5px solid #059669; padding-bottom: 8px; margin-bottom: 20px;">
                                <div>
                                    <div style="font-size: 15px; font-weight: 900; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                                        ATTACHMENT: ${doc.label}
                                    </div>
                                    <div style="font-size: 11px; font-weight: 600; color: #64748b; margin-top: 3px;">
                                        AMIS ID: #{{ $student->student_number }} • Grade: {{ $student->grade_level }}
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 14px; font-weight: 900; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                                        {{ addslashes($formalFormattedName) }}
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: center; height: 960px; background: #fafafa; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; padding: 12px;">
                                <img src="${doc.data_uri || doc.url}" crossorigin="anonymous" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            </div>
                        `;

                        document.body.appendChild(tempContainer);

                        await new Promise(r => {
                            const img = tempContainer.querySelector('img');
                            if (!img || img.complete) r();
                            else { img.onload = r; img.onerror = r; }
                        });

                        pdf.addPage('a4', 'portrait');
                        const canvas = await html2canvas(tempContainer, { scale: 2, useCORS: true, logging: false });
                        document.body.removeChild(tempContainer);

                        const imgData = canvas.toDataURL('image/jpeg', 0.95);
                        pdf.addImage(imgData, 'JPEG', 0, 0, 210, 297, undefined, 'FAST');
                        pageIndex++;
                    }
                }

                const autoFileName = '{{ $autoFileName }}';
                pdf.save(withAttachments ? `${autoFileName}_With_Attachments.pdf` : `${autoFileName}.pdf`);
            } catch (err) {
                console.error('Error generating PDF:', err);
                alert('An error occurred while generating the PDF file. Please try again.');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                }
            }
        }
    </script>

    <!-- 2x2 Photo Cropper & Adjuster Modal -->
    <div id="photoCropperModal" class="photo-cropper-backdrop" style="display: none;" onclick="handleBackdropClick(event)">
        <div class="photo-cropper-card" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="photo-cropper-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #ecfdf5; border: 1px solid #a7f3d0; display: flex; align-items: center; justify-content: center; color: #059669; flex-shrink: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    </div>
                    <div>
                        <h3 style="font-family: 'Inter', system-ui, sans-serif; font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">2×2 Photo Adjuster</h3>
                        <p style="font-family: 'Inter', system-ui, sans-serif; font-size: 0.75rem; color: #64748b; margin: 2px 0 0 0;">Drag, zoom, and frame face for official AMIS ID & documents</p>
                    </div>
                </div>
                <button type="button" onclick="closePhotoCropperModal()" class="cropper-close-btn" title="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="photo-cropper-body">
                <!-- Cropper Workspace -->
                <div class="cropper-workspace-wrapper" style="width: 100%; height: 300px; background: #0f172a; border-radius: 12px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    <img id="cropperTargetImage" src="" alt="Adjust Photo" style="max-width: 100%; max-height: 100%; display: block;">
                </div>

                <!-- Controls & Real-Time Preview Strip -->
                <div class="cropper-controls-strip">
                    <!-- Zoom Slider -->
                    <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                        <button type="button" onclick="cropperZoom(-0.1)" class="cropper-tool-btn" title="Zoom Out">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        </button>
                        <input type="range" id="cropperZoomSlider" min="0.1" max="3" step="0.02" value="1" oninput="onZoomSliderChange(this.value)" style="flex: 1; accent-color: #059669; cursor: pointer;">
                        <button type="button" onclick="cropperZoom(0.1)" class="cropper-tool-btn" title="Zoom In">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        </button>
                    </div>

                    <!-- Rotation & Flip Actions -->
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <button type="button" onclick="cropperRotate(-90)" class="cropper-tool-btn" title="Rotate Left 90°">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 2v6h6"/><path d="M2.66 15.57a10 10 0 1 0 .57-8.38L2.5 8"/></svg>
                        </button>
                        <button type="button" onclick="cropperRotate(90)" class="cropper-tool-btn" title="Rotate Right 90°">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6"/><path d="M21.34 15.57a10 10 0 1 1-.57-8.38L21.5 8"/></svg>
                        </button>
                        <button type="button" onclick="cropperReset()" class="cropper-tool-btn" title="Reset View">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Upload New Photo / Replace -->
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin-top: 10px;">
                    <div style="font-family: 'Inter', system-ui, sans-serif; font-size: 0.78rem; color: #475569;">
                        Want to use a different photo file?
                    </div>
                    <label class="cropper-upload-btn" style="cursor: pointer; display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; background: #ffffff; border: 1px solid #cbd5e1; font-family: 'Inter', system-ui, sans-serif; font-size: 0.75rem; font-weight: 700; color: #0f172a; transition: all 0.15s ease;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span>Choose New File</span>
                        <input type="file" id="cropperFileInput" accept="image/jpeg,image/png,image/webp,image/jpg" onchange="handleNewPhotoSelected(event)" style="display: none;">
                    </label>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="photo-cropper-footer">
                <button type="button" onclick="closePhotoCropperModal()" class="cropper-btn-cancel">Cancel</button>
                <button type="button" id="btnSaveCroppedPhoto" onclick="saveCroppedPhoto()" class="cropper-btn-save">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Save Photo</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Cropper.js Script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>

    <script>
        let activeCropper = null;
        let originalPhotoDataUri = '{{ $photoBase64 ?? '' }}';

        function openPhotoCropperModal() {
            const modal = document.getElementById('photoCropperModal');
            if (!modal) return;

            const currentImg = document.getElementById('enrolment-form-photo-img');
            let photoSrc = (currentImg && currentImg.src && !currentImg.src.endsWith('/') && currentImg.style.display !== 'none') 
                ? currentImg.src 
                : originalPhotoDataUri;

            if (!photoSrc || photoSrc.includes('placeholder') || photoSrc === window.location.href) {
                document.getElementById('cropperFileInput').click();
                return;
            }

            modal.style.display = 'flex';
            initCropperInstance(photoSrc);
        }

        function closePhotoCropperModal() {
            const modal = document.getElementById('photoCropperModal');
            if (modal) modal.style.display = 'none';
            if (activeCropper) {
                activeCropper.destroy();
                activeCropper = null;
            }
        }

        function handleBackdropClick(e) {
            if (e.target.id === 'photoCropperModal') {
                closePhotoCropperModal();
            }
        }

        function initCropperInstance(imageSrc) {
            if (activeCropper) {
                activeCropper.destroy();
                activeCropper = null;
            }

            const targetImg = document.getElementById('cropperTargetImage');
            
            const startCropper = () => {
                if (activeCropper) {
                    activeCropper.destroy();
                    activeCropper = null;
                }
                activeCropper = new Cropper(targetImg, {
                    aspectRatio: 1, // Fixed 1:1 square for 2x2 photo
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.95,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: false,
                    cropBoxResizable: false,
                    toggleDragModeOnDblclick: false,
                    ready() {
                        const zoomSlider = document.getElementById('cropperZoomSlider');
                        if (zoomSlider) zoomSlider.value = 1;
                    }
                });
            };

            targetImg.onload = startCropper;
            targetImg.src = imageSrc;
            if (targetImg.complete) {
                startCropper();
            }
        }

        function onZoomSliderChange(val) {
            if (!activeCropper) return;
            activeCropper.zoomTo(parseFloat(val));
        }

        function cropperZoom(ratio) {
            if (!activeCropper) return;
            activeCropper.zoom(ratio);
            const zoomSlider = document.getElementById('cropperZoomSlider');
            if (zoomSlider) {
                const currentData = activeCropper.getImageData();
                zoomSlider.value = (currentData && currentData.scaleX) ? currentData.scaleX : 1;
            }
        }

        function cropperRotate(deg) {
            if (!activeCropper) return;
            activeCropper.rotate(deg);
        }

        function cropperReset() {
            if (!activeCropper) return;
            activeCropper.reset();
            const zoomSlider = document.getElementById('cropperZoomSlider');
            if (zoomSlider) zoomSlider.value = 1;
        }

        function handleNewPhotoSelected(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;

            const modal = document.getElementById('photoCropperModal');
            if (modal) modal.style.display = 'flex';

            const reader = new FileReader();
            reader.onload = (event) => {
                initCropperInstance(event.target.result);
            };
            reader.readAsDataURL(file);
        }

        async function saveCroppedPhoto() {
            if (!activeCropper) return;

            const saveBtn = document.getElementById('btnSaveCroppedPhoto');
            const originalHtml = saveBtn ? saveBtn.innerHTML : '';
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<svg class="animate-spin" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg><span>Saving...</span>';
            }

            try {
                // Generate 600x600 HD 2x2 square crop
                const croppedCanvas = activeCropper.getCroppedCanvas({
                    width: 600,
                    height: 600,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                });

                const base64Data = croppedCanvas.toDataURL('image/jpeg', 0.92);

                const response = await fetch("{{ route('admin.students.update-photo', $student) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        cropped_image: base64Data
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Update the image on Page 1 immediately
                    const photoImg = document.getElementById('enrolment-form-photo-img');
                    const placeholder = document.getElementById('enrolment-form-photo-placeholder');
                    if (photoImg) {
                        photoImg.src = result.data_uri || base64Data;
                        photoImg.style.display = 'block';
                    }
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }

                    // Update global photo base64 for PDF / DOCX generators
                    originalPhotoDataUri = result.data_uri || base64Data;

                    // Also update in attachedDocFiles if 2x2 photo is present in attachments
                    if (typeof attachedDocFiles !== 'undefined' && Array.isArray(attachedDocFiles)) {
                        const photoDoc = attachedDocFiles.find(d => d.key === 'photo_2x2');
                        if (photoDoc) {
                            photoDoc.url = result.photo_url || result.data_uri || base64Data;
                            photoDoc.data_uri = result.data_uri || base64Data;
                        }
                    }

                    closePhotoCropperModal();
                    showPhotoToast('Photo updated successfully!', 'success');
                } else {
                    alert(result.message || 'Failed to save photo. Please try again.');
                }
            } catch (err) {
                console.error('Error saving photo:', err);
                alert('Failed to save photo. Please try again.');
            } finally {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalHtml;
                }
            }
        }

        function showPhotoToast(msg, type = 'success') {
            const toast = document.createElement('div');
            toast.style.position = 'fixed';
            toast.style.bottom = '24px';
            toast.style.right = '24px';
            toast.style.background = type === 'success' ? '#059669' : '#dc2626';
            toast.style.color = '#ffffff';
            toast.style.padding = '12px 20px';
            toast.style.borderRadius = '10px';
            toast.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.2)';
            toast.style.fontFamily = "'Inter', system-ui, sans-serif";
            toast.style.fontSize = '0.85rem';
            toast.style.fontWeight = '700';
            toast.style.zIndex = '999999';
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.style.gap = '8px';
            toast.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><span>${msg}</span>`;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 300);
            }, 3500);
        }

        function toggleModalityCheckbox(item) {
            const bar = item.closest('.modality-bar');
            if (!bar) return;
            const currentBox = item.querySelector('.custom-checkbox');
            const isChecked = currentBox && currentBox.innerHTML.trim() !== '';
            bar.querySelectorAll('.custom-checkbox').forEach(box => box.innerHTML = '');
            if (!isChecked && currentBox) {
                currentBox.innerHTML = '&#10003;';
            }
        }

        function toggleOldNewCheckbox(item, type) {
            const stack = item.closest('.checkbox-stack');
            if (!stack) return;
            const currentBox = item.querySelector('.custom-checkbox');
            const isChecked = currentBox && currentBox.innerHTML.trim() !== '';
            stack.querySelectorAll('.custom-checkbox').forEach(box => box.innerHTML = '');
            if (!isChecked && currentBox) {
                currentBox.innerHTML = '&#10003;';
            }
        }

        if (new URLSearchParams(window.location.search).get('auto_print') === '1') {
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => window.print(), 500);
            });
        }
    </script>
</body>
</html>
