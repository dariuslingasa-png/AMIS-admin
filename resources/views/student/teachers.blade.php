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
            $overrides = json_decode(file_get_contents($overridesJsonPath), true) ?: [];
        }

        $photoPath = null;
        if (!empty($overrides[$teacherKey]['photo_path'])) {
            $photoPath = $overrides[$teacherKey]['photo_path'];
        }

        if (!$photoPath) {
            $profileJsonPath = $adminPath . '/storage/app/academic_teacher_profiles.json';
            if (file_exists($profileJsonPath)) {
                $profiles = json_decode(file_get_contents($profileJsonPath), true) ?: [];
                if (!empty($profiles[$teacherKey]['photo_path'])) {
                    $photoPath = $profiles[$teacherKey]['photo_path'];
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

    // Build teachersList grouped by name
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

    $teachersList = [];
    foreach ($subjects as $subj) {
        $tName = $subj->teacher_name ?: 'To Be Assigned';
        if (!isset($teachersList[$tName])) {
            $teachersList[$tName] = [
                'name' => $tName,
                'subjects' => [],
                'email' => $teacherEmails[$tName] ?? ($subj->teacher_name ? strtolower(str_replace([' ', '.'], '', $subj->teacher_name)) . '@amis.edu.ph' : null),
                'team_url' => $subj->team_url ?? $section?->ms_team_url ?? 'https://teams.microsoft.com',
                'photo' => $subj->teacher_photo,
                'key' => $subj->teacher_key,
            ];
        }
        $teachersList[$tName]['subjects'][] = $subj->subject_name;
    }
    ksort($teachersList);
@endphp

<div class="space-y-6" x-data="{ previewPhoto: null, copiedText: null, copyToClipboard(text) { navigator.clipboard.writeText(text); this.copiedText = text; setTimeout(() => { if(this.copiedText === text) this.copiedText = null; }, 2000); } }">
    
    <!-- 1. Header Banner -->
    <div class="portal-card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-emerald-700 font-bold text-xs uppercase tracking-wider">
                <i data-lucide="users" class="h-4 w-4"></i>
                <span>Faculty Directory</span>
            </div>
            <h2 class="mt-1 font-heading text-2xl font-black text-slate-900">Assigned Subject Teachers</h2>
            <p class="text-xs font-medium text-slate-500">
                Official class advisers and faculty assigned to {{ $section?->name ?? 'Grade 1' }}.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="portal-badge portal-badge-emerald">
                {{ count($teachersList) }} Faculty Members
            </span>
        </div>
    </div>

    <!-- 2. Class Adviser Card (if available) -->
    @if($adviser)
        <div class="portal-card p-6 border-emerald-200 bg-linear-to-r from-emerald-50/60 to-white">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-800 mb-4">
                <i data-lucide="award" class="h-4 w-4"></i>
                <span>Official Class Adviser</span>
            </div>

            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                <div class="h-20 w-20 rounded-2xl bg-emerald-100 border-2 border-emerald-300 overflow-hidden flex items-center justify-center shrink-0">
                    @if(!empty($adviser['photo']))
                        <img src="{{ $getPhotoUrl($adviser['photo'], null, $adviser['name']) }}" alt="{{ $adviser['name'] }}" class="h-full w-full object-cover">
                    @else
                        <span class="font-heading text-2xl font-black text-emerald-800">
                            {{ strtoupper(substr(str_ireplace('TEACHER ', '', $adviser['name']), 0, 2)) }}
                        </span>
                    @endif
                </div>

                <div class="min-w-0 flex-1 text-center sm:text-left">
                    <h3 class="font-heading text-xl font-extrabold text-slate-900">
                        {{ $formatTeacherName($adviser['name']) }}
                    </h3>
                    <p class="text-xs font-bold text-emerald-700 mt-0.5">Section Adviser · {{ $section?->name ?? 'G1-AL-MUNAWWARA' }}</p>
                    
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-4 mt-3 text-xs">
                        @if(!empty($adviser['email']))
                            <div class="flex items-center gap-1.5 font-semibold text-slate-600">
                                <i data-lucide="mail" class="h-3.5 w-3.5 text-emerald-600"></i>
                                <a href="mailto:{{ $adviser['email'] }}" class="hover:underline text-emerald-800">{{ $adviser['email'] }}</a>
                                <button type="button" @click="copyToClipboard('{{ $adviser['email'] }}')" class="text-slate-400 hover:text-slate-600">
                                    <i data-lucide="copy" class="h-3 w-3"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 3. Teachers Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($teachersList as $t)
            @php
                $photoUrl = $getPhotoUrl($t['photo'], $t['key'], $t['name']);
            @endphp
            <div class="portal-card p-5 flex flex-col justify-between gap-4 hover:border-slate-300">
                <div>
                    <!-- Top: Avatar + Name -->
                    <div class="flex items-start gap-3.5">
                        <div class="h-14 w-14 rounded-2xl bg-emerald-50 border border-emerald-200 overflow-hidden flex items-center justify-center shrink-0">
                            @if($photoUrl)
                                <img src="{{ $photoUrl }}" alt="{{ $t['name'] }}" class="h-full w-full object-cover">
                            @else
                                <span class="font-heading text-lg font-black text-emerald-800">
                                    {{ strtoupper(substr(str_ireplace('TEACHER ', '', $t['name']), 0, 2)) }}
                                </span>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <span class="portal-badge portal-badge-emerald">Faculty</span>
                            <h3 class="font-heading text-base font-extrabold text-slate-900 mt-1 truncate" title="{{ $formatTeacherName($t['name']) }}">
                                {{ $formatTeacherName($t['name']) }}
                            </h3>
                            @if($t['email'])
                                <p class="text-xs font-medium text-slate-500 truncate mt-0.5" title="{{ $t['email'] }}">
                                    {{ $t['email'] }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <!-- Subjects Taught -->
                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Subject(s)</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($t['subjects'] as $subjName)
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">
                                    {{ $subjName }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Action: Microsoft Teams -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] font-semibold text-slate-400">AMIS Verified</span>
                    <a href="https://teams.microsoft.com/" target="_blank" 
                       class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 border border-indigo-200 px-2.5 py-1 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100">
                        <i data-lucide="message-square" class="h-3.5 w-3.5"></i>
                        <span>Teams Chat</span>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full portal-empty-state">
                <div class="portal-empty-icon">
                    <i data-lucide="users" class="h-6 w-6"></i>
                </div>
                <h3 class="font-heading text-base font-bold text-slate-800">No teachers assigned yet</h3>
                <p class="text-xs text-slate-500 mt-1">Teachers will appear here once section schedules are finalized.</p>
            </div>
        @endforelse
    </div>

</div>

</x-student-layout>
