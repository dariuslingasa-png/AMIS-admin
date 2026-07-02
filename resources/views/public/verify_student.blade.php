<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Verification | AMIS</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .card-header {
            background: linear-gradient(135deg, #022c22 0%, #064e3b 100%);
            padding: 24px 20px;
            text-align: center;
            position: relative;
        }
        .logo-img {
            height: 48px;
            width: auto;
            display: inline-block;
            margin-bottom: 6px;
        }
        .school-name {
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 800;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .school-subtitle {
            font-size: 8.5px;
            font-weight: 700;
            color: #eab308;
            text-transform: uppercase;
            margin-top: 2px;
            letter-spacing: 0.1em;
        }
        .card-body {
            padding: 28px 20px;
            text-align: center;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 20px;
            font-family: 'Outfit', sans-serif;
        }
        .status-verified {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .status-unverified {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .status-icon {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }
        .student-name {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
            line-height: 1.25;
            margin-bottom: 6px;
        }
        .student-role {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 20px;
        }
        .details-list {
            text-align: left;
            background-color: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 20px;
        }
        .details-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11.5px;
        }
        .details-item:last-child {
            border-bottom: none;
        }
        .details-label {
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5px;
            letter-spacing: 0.05em;
        }
        .details-value {
            color: #1e293b;
            font-weight: 700;
        }
        .details-value.font-mono {
            font-family: monospace;
        }
        .card-footer {
            border-top: 1px solid #f1f5f9;
            padding: 14px 20px;
            text-align: center;
            font-size: 9.5px;
            color: #94a3b8;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card-header">
            <img class="logo-img" src="/images/AMIS_Logo.png" alt="AMIS Logo" onerror="this.onerror=null; this.src='/logo/AMIS_Logo.png'; this.onerror=function(){this.style.display='none';};">
            <div class="school-arabic" style="font-family: 'Times New Roman', serif; font-size: 15.5px; font-weight: bold; color: #ffffff; margin-top: 5px; direction: rtl; unicode-bidi: embed;">المدرسة المنورة الإسلامية</div>
            <div class="school-name" style="margin-top: 1px;">AL MUNAWWARA ISLAMIC SCHOOL</div>
            <div class="school-subtitle" style="margin-top: 4px;">Student Verification System</div>
        </div>
        
        <div class="card-body">
            @if ($student)
                @php
                    $applicant = $student->applicant;
                    $fullName = trim(($applicant->first_name ?? '').' '.($applicant->middle_name ?? '').' '.($applicant->last_name ?? ''));
                    $status = $student->user->account_status ?? 'verified';
                    
                    // Fetch current active school year
                    $currentSchoolYear = (string) (\Illuminate\Support\Facades\DB::table('enrollment_applicants')->whereNotNull('school_year')->latest('id')->value('school_year') ?? config('services.school.year', '2026-2027'));
                    
                    $isActive = $status === 'verified' && (string) $student->school_year === $currentSchoolYear;
                    
                    if ($status === 'verified' && (string) $student->school_year !== $currentSchoolYear) {
                        $statusLabel = 'Inactive (Outdated SY)';
                    } else {
                        $statusLabel = $status === 'verified' ? 'Active Student' : strtoupper($status);
                    }
                @endphp

                @if ($isActive)
                    <div class="status-badge status-verified">
                        <!-- SVG Check -->
                        <svg class="status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Active Student (S.Y. {{ $currentSchoolYear }})
                    </div>
                @else
                    <div class="status-badge status-unverified">
                        <!-- SVG Warning -->
                        <svg class="status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        {{ $statusLabel }}
                    </div>
                @endif
                
                <h2 class="student-name">{{ $fullName ?: 'Student Record' }}</h2>
                <div class="student-role">Enrollment Verified</div>
                
                <div class="details-list">
                    <div class="details-item">
                        <span class="details-label">Student ID</span>
                        <span class="details-value font-mono">{{ $student->student_number }}</span>
                    </div>
                    <div class="details-item">
                        <span class="details-label">Grade Level</span>
                        <span class="details-value">{{ $student->grade_level }}</span>
                    </div>
                    <div class="details-item">
                        <span class="details-label">School Year</span>
                        <span class="details-value" @style(['color: #ef4444;' => (string)$student->school_year !== $currentSchoolYear])>
                            S.Y. {{ $student->school_year }}
                            @if ((string)$student->school_year !== $currentSchoolYear)
                                (Outdated)
                            @endif
                        </span>
                    </div>
                    <div class="details-item">
                        <span class="details-label">Learning Mode</span>
                        <span class="details-value">{{ $applicant->learning_mode ?: 'Face-to-Face' }}</span>
                    </div>
                </div>
            @else
                <div class="status-badge status-unverified">
                    <!-- SVG Cross -->
                    <svg class="status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Invalid ID
                </div>
                
                <h2 class="student-name">Record Not Found</h2>
                <div class="student-role" style="color: #ef4444;">Verification Failed</div>
                
                <div class="details-list" style="background-color: #fef2f2; border-color: #fee2e2;">
                    <p style="font-size: 11px; color: #991b1b; line-height: 1.5;">
                        We couldn't find any registered student with ID number <strong class="font-mono">{{ $studentNumber }}</strong> in the school database.
                    </p>
                </div>
            @endif
        </div>
        
        <div class="card-footer">
            Official AMIS Verification Portal &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
