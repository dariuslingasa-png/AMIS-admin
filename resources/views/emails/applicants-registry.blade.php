<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>AMIS Families Registry Report</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Inter', Arial, sans-serif; -webkit-font-smoothing: antialiased;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 30px 15px;">
    <tr>
        <td align="center">
            <table width="100%" max-width="800" style="max-width: 800px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border-collapse: collapse;">
                <!-- Header Banner -->
                <tr>
                    <td style="background: linear-gradient(135deg, #059669, #047857); padding: 30px 40px; text-align: center; color: #ffffff;">
                        <h1 style="font-size: 24px; margin: 0 0 6px; font-weight: 800; tracking-tight: -0.025em;">Al Munawwara Islamic School</h1>
                        <p style="color: rgba(255, 255, 255, 0.9); font-size: 14px; margin: 0; font-weight: 600;">AMIS Enrollment Office &bull; Families Registry Report</p>
                    </td>
                </tr>
                
                <!-- Main Body -->
                <tr>
                    <td style="padding: 35px 40px;">
                        <!-- Custom Message Body -->
                        <div style="font-size: 17px; line-height: 1.7; color: #1e293b; margin-bottom: 30px; border-bottom: 1px solid #f1f5f9; padding-bottom: 25px;">{!! nl2br(e($messageBody)) !!}</div>
                        
                        <!-- Registry Count Badge -->
                        <div style="margin-bottom: 15px; text-align: right;">
                            <span style="background-color: #ecfdf5; color: #047857; font-size: 14px; font-weight: bold; padding: 6px 12px; border-radius: 9999px; border: 1px solid #a7f3d0;">
                                Total Families Listed: {{ count($families) }}
                            </span>
                        </div>

                        <!-- Families Registry Table -->
                        @forelse($families as $family)
                            @php
                                $accentColors = ['#059669', '#2563eb', '#d97706', '#7c3aed', '#e11d48'];
                                $color = $accentColors[$family['family_no'] % count($accentColors)];
                                $lastName = strtoupper(explode(',', $family['family_label'])[0]);

                                // Determine if family has payment proof
                                $hasFamilyProof = false;
                                $payments = $family['family_payments'] ?? collect();
                                foreach ($payments as $p) {
                                    if ($p->receipt_url) {
                                        $hasFamilyProof = true;
                                        break;
                                    }
                                }
                            @endphp
                            <table cellpadding="8" cellspacing="0" width="100%" style="width: 100%; border-collapse: collapse; margin-top: 25px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                <thead>
                                    <!-- Merged Family Title Block -->
                                    <tr>
                                        <th colspan="4" align="center" style="background-color: #ffffff; padding: 24px 20px; border-bottom: 1px solid #e2e8f0; text-align: center;">
                                            <h2 style="margin: 0; font-size: 28px; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.05em; font-family: 'Outfit', Arial, sans-serif;">
                                                FAMILY OF {{ $lastName }}
                                            </h2>
                                            <div style="font-size: 16px; color: #64748b; margin-top: 8px; line-height: 1.4; font-weight: 500;">
                                                Parent: {{ $family['parent_name'] }} &bull; Email: {{ $family['parent_email'] }}
                                            </div>
                                            <div style="margin-top: 10px;">
                                                @if($hasFamilyProof)
                                                    <span style="font-size: 14px; font-weight: bold; padding: 6px 12px; border-radius: 6px; background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; display: inline-block; text-transform: uppercase;">
                                                        Payment Proof: Attached
                                                    </span>
                                                @else
                                                    <span style="font-size: 14px; font-weight: bold; padding: 6px 12px; border-radius: 6px; background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; display: inline-block; text-transform: uppercase;">
                                                        No Payment Proof Uploaded
                                                    </span>
                                                @endif
                                            </div>
                                        </th>
                                    </tr>
                                    <!-- Column Headers -->
                                    <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left; font-size: 13px; font-weight: bold; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">
                                        <th style="padding: 12px 15px; border: 1px solid #e2e8f0; width: 40%;">Children Name</th>
                                        <th style="padding: 12px 15px; border: 1px solid #e2e8f0; width: 15%;">Grade Level</th>
                                        <th style="padding: 12px 15px; border: 1px solid #e2e8f0; width: 25%;">Learning Type</th>
                                        <th style="padding: 12px 15px; border: 1px solid #e2e8f0; width: 20%;">Enrollment Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($family['children'] as $child)
                                        @php
                                            $childName = trim(($child->first_name ?? '').' '.($child->last_name ?? ''));
                                            
                                            // Determine Learning Type text
                                            $learningMode = match (strtolower($child->learning_mode ?? '')) {
                                                'face_to_face' => 'Face-to-Face',
                                                'online', 'odl' => 'Online / ODL',
                                                'blended' => 'Blended',
                                                default => ucwords(str_replace('_', ' ', $child->learning_mode ?? ''))
                                            };
                                            
                                            // Determine Status text and color badge
                                            $statusText = str_replace('_', ' ', strtoupper($child->status));
                                            $statusColor = match (strtolower($child->status)) {
                                                'submitted' => '#2563eb',
                                                'verified' => '#059669',
                                                'approved' => '#059669',
                                                'pending' => '#d97706',
                                                default => '#475569'
                                            };
                                            $childType = strtoupper((string) ($child->student_type ?? 'new'));
                                        @endphp
                                        <tr style="border-bottom: 1px solid #e2e8f0;">
                                            <td style="padding: 14px 15px; border: 1px solid #e2e8f0; vertical-align: middle; font-size: 15px; color: #1e293b;">
                                                <strong style="font-size: 16px;">{{ $childName }}</strong>
                                                <span style="font-size: 10px; background-color: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; margin-left: 6px; font-weight: 700; display: inline-block; vertical-align: middle;">
                                                    {{ $childType }}
                                                </span>
                                            </td>
                                            <td style="padding: 14px 15px; border: 1px solid #e2e8f0; vertical-align: middle; font-size: 14px; color: #334155;">
                                                {{ $child->grade_level ?? 'N/A' }}
                                            </td>
                                            <td style="padding: 14px 15px; border: 1px solid #e2e8f0; vertical-align: middle; font-size: 14px; color: #334155;">
                                                {{ $learningMode }}
                                            </td>
                                            <td style="padding: 14px 15px; border: 1px solid #e2e8f0; vertical-align: middle; font-size: 13px; font-weight: bold; color: {{ $statusColor }};">
                                                {{ $statusText }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @empty
                            <table cellpadding="8" cellspacing="0" width="100%" style="width: 100%; border-collapse: collapse; margin-top: 15px; border: 1px solid #e2e8f0; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 30px; text-align: center; color: #64748b; font-size: 14px;">No family applications matched the selected filters.</td>
                                </tr>
                            </table>
                        @endforelse
                    </td>
                </tr>
                
                <!-- Footer -->
                <tr>
                    <td style="background-color: #f9fafb; padding: 25px 40px; text-align: center; border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 12px; font-weight: 500;">
                        <p style="margin: 0 0 4px; color: #64748b; font-weight: 700;">Al Munawwara Islamic School</p>
                        <p style="margin: 0;">&copy; {{ date('Y') }} AMIS Enrollment System. All rights reserved.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
