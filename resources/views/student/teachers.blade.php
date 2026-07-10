<x-student-layout title="Teachers">
@php
    $formatTeacherName = function ($name) {
        $name = $name ?: 'To Be Assigned';
        if ($name === 'To Be Assigned') {
            return $name;
        }
        $nameTrimmed = trim($name);
        $nameLower = strtolower($nameTrimmed);
        
        $titles = ['tchr', 'teacher', 'ust', 'ustadz', 'ustadh', 'ustadha', 'alim', 'alima', 'alimah'];
        
        $hasTitle = false;
        foreach ($titles as $title) {
            if (str_starts_with($nameLower, $title)) {
                $hasTitle = true;
                break;
            }
        }
        
        if ($hasTitle) {
            if (str_starts_with($nameLower, 'teacher ')) {
                return 'Tchr. ' . ucwords(strtolower(trim(substr($nameTrimmed, 8))));
            }
            return ucwords(strtolower($nameTrimmed));
        }
        
        return 'Tchr. ' . ucwords(strtolower($nameTrimmed));
    };

    $getPhotoUrl = function ($teacherPhoto, $teacherKey = null, $teacherName = '') {
        if (empty($teacherKey)) {
            if (!empty($teacherPhoto)) {
                $teacherKey = pathinfo($teacherPhoto, PATHINFO_FILENAME);
                $teacherKey = str_replace('teacher-', '', $teacherKey);
            } elseif (!empty($teacherName)) {
                $cleanName = trim((string)$teacherName);
                while (preg_match('/^(TEACHER|TCHR\.?|UST\.?|USTADZ|USTADH|USTADHA|ALIM|SIR|MA\'AM|MAAM)\s+/i', $cleanName, $matches)) {
                    $cleanName = trim(substr($cleanName, strlen($matches[0])));
                }
                $teacherKey = \Illuminate\Support\Str::slug($cleanName);
            }
        }

        if (empty($teacherKey)) {
            if (empty($teacherPhoto)) return null;
            if (str_starts_with($teacherPhoto, 'http://') || str_starts_with($teacherPhoto, 'https://')) {
                return $teacherPhoto;
            }
            return 'https://admin.amis.edu.ph/' . ltrim($teacherPhoto, '/');
        }

        $adminPath = '/home2/amisdavc/admin.amis.edu.ph';
        if (!file_exists($adminPath)) {
            $adminPath = base_path('../amis_admin');
        }

        $overrides = [];
        $overridesJsonPath = $adminPath . '/storage/app/academic_teacher_overrides.json';
        if (file_exists($overridesJsonPath)) {
            $overrides = json_decode(file_get_contents($overridesJsonPath), true) ?? [];
        }

        $photoPath = $overrides[$teacherKey]['photo'] ?? null;

        if (empty($photoPath)) {
            $possiblePaths = [
                "images/teachers/{$teacherKey}.jpg",
                "images/teachers/teacher-{$teacherKey}.jpg",
                "images/teachers/{$teacherKey}.png",
                "images/teachers/teacher-{$teacherKey}.png",
                "images/teachers/{$teacherKey}.jpeg",
                "images/teachers/teacher-{$teacherKey}.jpeg",
            ];
            foreach ($possiblePaths as $path) {
                if (file_exists($adminPath . '/public/' . $path)) {
                    $photoPath = $path;
                    break;
                }
            }
        }

        if ($photoPath) {
            return 'https://admin.amis.edu.ph/' . ltrim($photoPath, '/');
        }

        if (empty($teacherPhoto)) return null;
        if (str_starts_with($teacherPhoto, 'http://') || str_starts_with($teacherPhoto, 'https://')) {
            return $teacherPhoto;
        }
        return 'https://admin.amis.edu.ph/' . ltrim($teacherPhoto, '/');
    };

    // Build teachersList grouped by name with real photo/email from section_subjects
    $teacherPhotos = [];
    $teacherEmails = [];
    foreach ($subjects as $subj) {
        $tName = $subj->teacher_name ?: 'To Be Assigned';
        if (!isset($teacherPhotos[$tName])) {
            $teacherPhotos[$tName] = [
                'photo' => $subj->teacher_photo,
                'key' => $subj->teacher_key,
            ];
        }
        if (!isset($teacherEmails[$tName]) && $subj->teacher_email) {
            $teacherEmails[$tName] = $subj->teacher_email;
        }
    }
    $teacherAvatar = fn (string $name) => isset($teacherPhotos[$name]) ? $getPhotoUrl($teacherPhotos[$name]['photo'], $teacherPhotos[$name]['key'], $name) : null;


    $teachersList = [];
    foreach ($subjects as $subj) {
        $tName = $subj->teacher_name ?: 'To Be Assigned';
        if (!isset($teachersList[$tName])) {
            $teachersList[$tName] = [
                'name' => $tName,
                'subjects' => [],
                'email' => $teacherEmails[$tName] ?? ($subj->teacher_name ? strtolower(str_replace([' ', '.'], '', $subj->teacher_name)) . '@amis.edu.ph' : null),
                'team_url' => $subj->team_url ?? $section?->ms_team_url ?? 'https://teams.microsoft.com',
            ];
        }
        $teachersList[$tName]['subjects'][] = $subj->subject_name;
    }
    ksort($teachersList);

    // Color list for tags/badges
    $colors = ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ec4899', '#0d9488'];
    $bgs = ['#eff6ff', '#ecfdf5', '#f5f3ff', '#fffbeb', '#fdf2f8', '#f0fdfa'];
@endphp

<div class="space-y-6" x-data="{ previewPhoto: null, copiedText: null, copyToClipboard(text) { navigator.clipboard.writeText(text); this.copiedText = text; setTimeout(() => { if(this.copiedText === text) this.copiedText = null; }, 2000); } }" x-init="window.lucide && window.lucide.createIcons()">
    <!-- Header card -->
    <div class="s-quick-actions-card" style="padding: 1.5rem; background: white; border-radius: 20px; border: 1.5px solid #e2e8f0;">
        <div style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.8rem; font-weight: 850; color: #0d9488; background: #f0fdfa; border: 1px solid #ccfbf1; padding: 0.25rem 0.65rem; border-radius: 999px;">
            <i data-lucide="users" class="w-3.5 h-3.5"></i>
            <span>My Teachers</span>
        </div>
        <h2 style="font-size: 1.5rem; font-weight: 900; color: #0f172a; margin: 0.5rem 0 0.25rem;">Assigned Subject Teachers</h2>
        <p style="font-size: 0.9rem; font-weight: 700; color: #475569; margin: 0;">Contact information, subjects, and quick Teams access to your official class teachers.</p>
    </div>

    <!-- Main Content Container with left and right sections -->
    <div class="s-two-col-grid">
        
        <!-- LEFT COLUMN: Teachers list and Advisor -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem; min-width: 0; width: 100%;">
            @if($adviser)
                <!-- Class Adviser Section -->
                <div class="fade-up">
                    <div style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.8rem; font-weight: 850; color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.25rem 0.65rem; border-radius: 999px; margin-bottom: 0.85rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award"><circle cx="12" cy="8" r="7"/><path d="M8.21 13.89 7 23l5-3 5 3-1.21-9.12"/></svg>
                        <span>Official Advisor</span>
                    </div>
                    <div class="s-quick-actions-card" style="background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; padding: 1.75rem; display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;">
                        <div @if($adviser['photo']) @click="previewPhoto = { url: '{{ $getPhotoUrl($adviser['photo'], null, $adviser['name']) }}', name: '{{ $formatTeacherName($adviser['name']) }}', role: 'Official Advisor' }" @endif
                             style="width: 80px; height: 80px; border-radius: 20px; background: #ecfdf5; border: 2px solid #a7f3d0; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; @if($adviser['photo']) cursor: pointer; transition: transform 0.15s, border-color 0.15s; @endif"
                             @if($adviser['photo'])
                             onmouseover="this.style.transform='scale(1.05)'; this.style.borderColor='#059669'"
                             onmouseout="this.style.transform='none'; this.style.borderColor='#a7f3d0'"
                             title="Click to preview profile picture"
                             @endif>
                            @if($adviser['photo'])
                                <img src="{{ $getPhotoUrl($adviser['photo'], null, $adviser['name']) }}" alt="{{ $adviser['name'] }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                @php
                                    $initials = collect(explode(' ', str_ireplace('TEACHER ', '', $adviser['name'])))
                                        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                        ->take(2)
                                        ->implode('');
                                @endphp
                                <span style="font-size: 1.85rem; font-weight: 900; color: #047857;">{{ $initials }}</span>
                            @endif
                        </div>

                        <div style="flex: 1; min-width: 200px;">
                            <h3 style="font-size: 1.35rem; font-weight: 900; color: #0f172a; margin: 0; line-height: 1.2;">
                                {{ $formatTeacherName($adviser['name']) }}
                            </h3>
                            <p style="font-size: 0.85rem; font-weight: 750; color: #059669; margin: 0.25rem 0 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                Official Advisor
                            </p>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.85rem; margin-top: 0.85rem; max-width: 600px;">
                                <!-- Official Microsoft Email -->
                                <div style="display: flex; align-items: center; gap: 0.35rem; min-width: 0;">
                                    <span style="font-size: 0.85rem; font-weight: 750; color: #64748b; display: inline-flex; align-items: center; gap: 0.35rem; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                        <a href="mailto:{{ $adviser['email'] }}" style="color: #0f766e; text-decoration: none; font-weight: 800;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                            {{ $adviser['email'] }}
                                        </a>
                                    </span>
                                    <button @click.prevent="copyToClipboard('{{ $adviser['email'] }}')" 
                                            style="border: none; background: transparent; padding: 0.2rem; cursor: pointer; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; transition: color 0.15s; border-radius: 4px;"
                                            onmouseover="this.style.color='#0d9488'"
                                            onmouseout="this.style.color='#94a3b8'"
                                            title="Copy to clipboard">
                                        <template x-if="copiedText !== '{{ $adviser['email'] }}'">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-copy"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                        </template>
                                        <template x-if="copiedText === '{{ $adviser['email'] }}'">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                                        </template>
                                    </button>
                                </div>

                                <!-- Gmail -->
                                @if(!empty($adviser['gmail']))
                                    <div style="display: flex; align-items: center; gap: 0.35rem; min-width: 0;">
                                        <span style="font-size: 0.85rem; font-weight: 750; color: #64748b; display: inline-flex; align-items: center; gap: 0.35rem; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ea4335" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                            <a href="mailto:{{ $adviser['gmail'] }}" style="color: #c53030; text-decoration: none; font-weight: 800;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                                {{ $adviser['gmail'] }}
                                            </a>
                                        </span>
                                        <button @click.prevent="copyToClipboard('{{ $adviser['gmail'] }}')" 
                                                style="border: none; background: transparent; padding: 0.2rem; cursor: pointer; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; transition: color 0.15s; border-radius: 4px;"
                                                onmouseover="this.style.color='#ea4335'"
                                                onmouseout="this.style.color='#94a3b8'"
                                                title="Copy to clipboard">
                                            <template x-if="copiedText !== '{{ $adviser['gmail'] }}'">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-copy"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                            </template>
                                            <template x-if="copiedText === '{{ $adviser['gmail'] }}'">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                                            </template>
                                        </button>
                                    </div>
                                @endif

                                <!-- Facebook Link -->
                                @if(!empty($adviser['fb_url']))
                                    <div style="display: flex; align-items: center; gap: 0.35rem; min-width: 0;">
                                        <span style="font-size: 0.85rem; font-weight: 750; display: inline-flex; align-items: center; gap: 0.35rem; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-facebook"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                                            <a href="{{ $adviser['fb_url'] }}" target="_blank" style="color: #2563eb; text-decoration: none; font-weight: 800;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                                Facebook Profile
                                            </a>
                                        </span>
                                        <button @click.prevent="copyToClipboard('{{ $adviser['fb_url'] }}')" 
                                                style="border: none; background: transparent; padding: 0.2rem; cursor: pointer; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; transition: color 0.15s; border-radius: 4px;"
                                                onmouseover="this.style.color='#2563eb'"
                                                onmouseout="this.style.color='#94a3b8'"
                                                title="Copy to clipboard">
                                            <template x-if="copiedText !== '{{ $adviser['fb_url'] }}'">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-copy"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                            </template>
                                            <template x-if="copiedText === '{{ $adviser['fb_url'] }}'">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                                            </template>
                                        </button>
                                    </div>
                                @endif

                                <!-- WhatsApp Link -->
                                @if(!empty($adviser['whatsapp']))
                                    @php
                                        $waNumber = $adviser['whatsapp'];
                                        if (str_starts_with($waNumber, '0')) {
                                            $waNumber = '63' . substr($waNumber, 1);
                                        }
                                        $waUrl = "https://wa.me/{$waNumber}";
                                    @endphp
                                    <div style="display: flex; align-items: center; gap: 0.35rem; min-width: 0;">
                                        <span style="font-size: 0.85rem; font-weight: 750; display: inline-flex; align-items: center; gap: 0.35rem; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                                            <a href="{{ $waUrl }}" target="_blank" style="color: #16a34a; text-decoration: none; font-weight: 800;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                                WhatsApp ({{ $adviser['whatsapp'] }})
                                            </a>
                                        </span>
                                        <button @click.prevent="copyToClipboard('{{ $adviser['whatsapp'] }}')" 
                                                style="border: none; background: transparent; padding: 0.2rem; cursor: pointer; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; transition: color 0.15s; border-radius: 4px;"
                                                onmouseover="this.style.color='#16a34a'"
                                                onmouseout="this.style.color='#94a3b8'"
                                                title="Copy to clipboard">
                                            <template x-if="copiedText !== '{{ $adviser['whatsapp'] }}'">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-copy"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                            </template>
                                            <template x-if="copiedText === '{{ $adviser['whatsapp'] }}'">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                                            </template>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div style="flex-shrink: 0;">
                            <a href="{{ $adviser['team_url'] }}" target="_blank" style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 900; color: white; background: #059669; padding: 0.65rem 1.25rem; border-radius: 12px; text-decoration: none; transition: background 0.15s;"
                               onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-video"><path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2" ry="2"/></svg>
                                <span>Chat on Teams</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            @if(count($teachersList) > 0)
                <!-- Grid of teachers -->
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
                    @foreach($teachersList as $tName => $tData)
                        @php
                            $isAssigned = $tName !== 'To Be Assigned';
                            $avatar = $isAssigned ? $teacherAvatar($tName) : null;
                        @endphp
                        <div class="s-quick-actions-card" style="background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between; min-height: 270px; transition: transform 0.2s, box-shadow 0.2s;"
                             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.05)'"
                             onmouseout="this.style.transform='none'; this.style.boxShadow='none'">
                            
                            <div>
                                <!-- Header: Photo + Name -->
                                <div style="display: flex; align-items: center; gap: 1rem; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 1rem;">
                                    <div @if($avatar) @click="previewPhoto = { url: '{{ $avatar }}', name: '{{ $formatTeacherName($tName) }}', role: 'Official Teacher' }" @endif
                                         style="width: 54px; height: 54px; border-radius: 14px; background: #ecfdf5; border: 1.5px solid #a7f3d0; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; @if($avatar) cursor: pointer; transition: transform 0.15s, border-color 0.15s; @endif"
                                         @if($avatar)
                                         onmouseover="this.style.transform='scale(1.05)'; this.style.borderColor='#059669'"
                                         onmouseout="this.style.transform='none'; this.style.borderColor='#a7f3d0'"
                                         title="Click to preview profile picture"
                                         @endif>
                                        @if($avatar)
                                            <img src="{{ $avatar }}" alt="{{ $tName }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <span style="font-size: 1.5rem; font-weight: 900; color: #047857;">?</span>
                                        @endif
                                    </div>
                                    <div style="min-width: 0; flex: 1;">
                                        @php
                                            $formattedTeacherName = $isAssigned ? $formatTeacherName($tName) : $tName;
                                        @endphp
                                        <h3 style="font-size: 1.05rem; font-weight: 850; color: #0f172a; margin: 0; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $formattedTeacherName }}">{{ $formattedTeacherName }}</h3>
                                        <p style="font-size: 0.75rem; font-weight: 700; color: #64748b; margin: 0; margin-top: 0.25rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                            {{ $isAssigned ? 'Official Teacher' : 'Academic Load' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Subjects Taught -->
                                <div style="margin-bottom: 1rem;">
                                    <p style="font-size: 0.7rem; font-weight: 850; color: #64748b; text-transform: uppercase; margin: 0 0 0.5rem; letter-spacing: 0.05em;">Subjects Taught</p>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.375rem;">
                                        @foreach($tData['subjects'] as $idx => $sName)
                                            @php 
                                                $c = $colors[$idx % count($colors)]; 
                                                $bg = $bgs[$idx % count($bgs)]; 
                                            @endphp
                                            <span style="font-size: 0.75rem; font-weight: 850; color: {{ $c }}; background: {{ $bg }}; border: 1px solid {{ $c }}30; padding: 0.2rem 0.5rem; border-radius: 8px;">
                                                {{ $sName }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Contact Details -->
                                @if($isAssigned && $tData['email'])
                                    <div style="margin-bottom: 1rem;">
                                        <p style="font-size: 0.7rem; font-weight: 850; color: #64748b; text-transform: uppercase; margin: 0 0 0.25rem; letter-spacing: 0.05em;">Email Address</p>
                                        <a href="mailto:{{ $tData['email'] }}" style="font-size: 0.8rem; font-weight: 800; color: #0d9488; text-decoration: none; word-break: break-all;" class="hover:underline">
                                            {{ $tData['email'] }}
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <!-- Teams Link -->
                            <div>
                                @if($isAssigned)
                                    <a href="{{ $tData['team_url'] }}" target="_blank" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 900; color: white; background: #5865f2; padding: 0.6rem; border-radius: 12px; text-decoration: none; transition: background 0.15s;"
                                       onmouseover="this.style.background='#4752c4'" onmouseout="this.style.background='#5865f2'">
                                        <i data-lucide="video" class="w-4 h-4"></i>
                                        <span>Message on Teams</span>
                                    </a>
                                @else
                                    <div style="text-align: center; padding: 0.5rem; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 0.8rem; font-weight: 750; color: #64748b;">
                                        Contact details pending
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="s-empty-card" style="margin-top: 1rem;">
                    <div class="s-empty-icon-wrapper">
                        <i data-lucide="users" class="w-8 h-8 text-emerald-600"></i>
                    </div>
                    <h3 class="s-empty-title">No Teachers Assigned</h3>
                    <p class="s-empty-text">No academic subjects or teachers have been assigned to your profile yet.</p>
                </div>
            @endif
        </div>

        <!-- RIGHT COLUMN: Summary and widgets -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem; flex-shrink: 0; width: 100%;">
            <!-- Summary Card -->
            <div class="s-quick-actions-card" style="padding: 1.5rem; background: white; border-radius: 20px; border: 1.5px solid #e2e8f0;">
                <h3 style="font-size: 1.15rem; font-weight: 850; color: #0f172a; margin: 0 0 1rem; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 0.75rem;">
                    Summary
                </h3>
                
                <div style="display: flex; align-items: center; gap: 1rem; background: #f0fdfa; border: 1.5px solid #ccfbf1; border-radius: 16px; padding: 1.25rem;">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: #ccfbf1; display: flex; align-items: center; justify-content: center; color: #0d9488; flex-shrink: 0;">
                        <i data-lucide="users" style="width: 22px; height: 22px;"></i>
                    </div>
                    <div>
                        <p style="font-size: 0.75rem; font-weight: 850; color: #0f766e; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Total Teachers</p>
                        <p style="font-size: 1.85rem; font-weight: 800; font-family: 'Inter', sans-serif; color: #0d9488; margin: 0; margin-top: 0.15rem; line-height: 1;">{{ count($teachersList) }}</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Image Preview Modal Overlay -->
    <template x-teleport="body">
        <div x-show="previewPhoto" 
             x-cloak 
             @keydown.escape.window="previewPhoto = null"
             style="position: fixed !important; inset: 0 !important; z-index: 9999 !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 1.5rem !important; background: rgba(15, 23, 42, 0.75) !important; backdrop-filter: blur(4px) !important;"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
             
             <div @click.outside="previewPhoto = null"
                  style="position: relative !important; background: white !important; border-radius: 24px !important; padding: 1.25rem !important; max-width: 420px !important; width: 100% !important; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important; display: flex !important; flex-direction: column !important; gap: 1rem !important; margin: auto !important;"
                  x-transition:enter="transition ease-out duration-300 transform"
                  x-transition:enter-start="opacity-0 scale-95"
                  x-transition:enter-end="opacity-100 scale-100"
                  x-transition:leave="transition ease-in duration-200 transform"
                  x-transition:leave-start="opacity-100 scale-100"
                  x-transition:leave-end="opacity-0 scale-95">
                  
                  <!-- Modal Header: Title + Close Button -->
                  <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 0.75rem; margin-bottom: 0.25rem;">
                      <h4 style="font-size: 1.05rem; font-weight: 900; color: #0f172a; margin: 0;">Teacher Profile</h4>
                      <button @click="previewPhoto = null" 
                              style="border: none; background: #f1f5f9; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: all 0.15s;"
                              onmouseover="this.style.background='#e2e8f0'; this.style.color='#0f172a'"
                              onmouseout="this.style.background='#f1f5f9'; this.style.color='#64748b'">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                      </button>
                  </div>
    
                  <!-- Image -->
                  <div style="width: 100%; border-radius: 18px; overflow: hidden; background: #ecfdf5; border: 2px solid #a7f3d0; display: flex; align-items: center; justify-content: center;">
                      <img :src="previewPhoto?.url" :alt="previewPhoto?.name" style="width: 100%; height: auto; max-height: 60vh; object-fit: contain; display: block;">
                  </div>
    
                  <!-- Title / Name -->
                  <div style="text-align: center; margin-bottom: 0.25rem;">
                      <h3 style="font-size: 1.25rem; font-weight: 900; color: #0f172a; margin: 0;" x-text="previewPhoto?.name"></h3>
                      <p style="font-size: 0.8rem; font-weight: 850; color: #059669; margin: 0.25rem 0 0; text-transform: uppercase; letter-spacing: 0.05em;" x-text="previewPhoto?.role"></p>
                  </div>
    
                  <!-- Advisor Contact Details -->
                  <template x-if="previewPhoto?.role === 'Official Advisor'">
                      <div style="display: flex; flex-direction: column; gap: 0.65rem; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 0.85rem; width: 100%; box-sizing: border-box;">
                          @if($adviser)
                              <!-- MS Email -->
                              <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; width: 100%;">
                                  <div style="display: flex; align-items: center; gap: 0.35rem; min-width: 0; flex: 1;">
                                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                      <span style="font-size: 0.85rem; font-weight: 800; color: #0f766e; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                          {{ $adviser['email'] }}
                                      </span>
                                  </div>
                                  <button @click.prevent="copyToClipboard('{{ $adviser['email'] }}')" 
                                          style="border: none; background: transparent; padding: 0.2rem; cursor: pointer; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; transition: color 0.15s; border-radius: 4px;"
                                          onmouseover="this.style.color='#0d9488'"
                                          onmouseout="this.style.color='#94a3b8'"
                                          title="Copy to clipboard">
                                      <template x-if="copiedText !== '{{ $adviser['email'] }}'">
                                          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-copy"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                      </template>
                                      <template x-if="copiedText === '{{ $adviser['email'] }}'">
                                          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                                      </template>
                                  </button>
                              </div>
    
                              <!-- Gmail -->
                              @if(!empty($adviser['gmail']))
                                  <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; width: 100%;">
                                      <div style="display: flex; align-items: center; gap: 0.35rem; min-width: 0; flex: 1;">
                                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ea4335" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mail"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                          <span style="font-size: 0.85rem; font-weight: 800; color: #c53030; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                              {{ $adviser['gmail'] }}
                                          </span>
                                      </div>
                                      <button @click.prevent="copyToClipboard('{{ $adviser['gmail'] }}')" 
                                              style="border: none; background: transparent; padding: 0.2rem; cursor: pointer; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; transition: color 0.15s; border-radius: 4px;"
                                              onmouseover="this.style.color='#ea4335'"
                                              onmouseout="this.style.color='#94a3b8'"
                                              title="Copy to clipboard">
                                          <template x-if="copiedText !== '{{ $adviser['gmail'] }}'">
                                              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-copy"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                          </template>
                                          <template x-if="copiedText === '{{ $adviser['gmail'] }}'">
                                              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                                          </template>
                                      </button>
                                  </div>
                              @endif
    
                              <!-- Facebook -->
                              @if(!empty($adviser['fb_url']))
                                  <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; width: 100%;">
                                      <div style="display: flex; align-items: center; gap: 0.35rem; min-width: 0; flex: 1;">
                                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-facebook"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                                          <a href="{{ $adviser['fb_url'] }}" target="_blank" style="font-size: 0.85rem; font-weight: 800; color: #2563eb; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                              Facebook Profile
                                          </a>
                                      </div>
                                      <button @click.prevent="copyToClipboard('{{ $adviser['fb_url'] }}')" 
                                              style="border: none; background: transparent; padding: 0.2rem; cursor: pointer; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; transition: color 0.15s; border-radius: 4px;"
                                              onmouseover="this.style.color='#2563eb'"
                                              onmouseout="this.style.color='#94a3b8'"
                                              title="Copy to clipboard">
                                          <template x-if="copiedText !== '{{ $adviser['fb_url'] }}'">
                                              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-copy"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                          </template>
                                          <template x-if="copiedText === '{{ $adviser['fb_url'] }}'">
                                              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                                          </template>
                                      </button>
                                  </div>
                              @endif
    
                              <!-- WhatsApp -->
                              @if(!empty($adviser['whatsapp']))
                                  <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; width: 100%;">
                                      <div style="display: flex; align-items: center; gap: 0.35rem; min-width: 0; flex: 1;">
                                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                                          <a href="https://wa.me/{{ str_starts_with($adviser['whatsapp'], '0') ? '63' . substr($adviser['whatsapp'], 1) : $adviser['whatsapp'] }}" target="_blank" style="font-size: 0.85rem; font-weight: 800; color: #16a34a; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                              WhatsApp ({{ $adviser['whatsapp'] }})
                                          </a>
                                      </div>
                                      <button @click.prevent="copyToClipboard('{{ $adviser['whatsapp'] }}')" 
                                              style="border: none; background: transparent; padding: 0.2rem; cursor: pointer; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; transition: color 0.15s; border-radius: 4px;"
                                              onmouseover="this.style.color='#16a34a'"
                                              onmouseout="this.style.color='#94a3b8'"
                                              title="Copy to clipboard">
                                          <template x-if="copiedText !== '{{ $adviser['whatsapp'] }}'">
                                              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-copy"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                          </template>
                                          <template x-if="copiedText === '{{ $adviser['whatsapp'] }}'">
                                              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg>
                                          </template>
                                      </button>
                                  </div>
                              @endif
                          @endif
                      </div>
                  </template>
             </div>
        </div>
    </template>
</div>
</x-student-layout>
