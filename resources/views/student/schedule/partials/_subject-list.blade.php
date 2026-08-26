<!-- Subject List Tab -->
<div x-show="currentTab === 'list'" class="space-y-6">
    @if($subjects->isNotEmpty())
        <div class="subject-card-grid">
            @foreach($subjects as $subj)
                @php
                    $currentTeacherName = $teacherName($subj);
                    $listPhotoUrl = $getPhotoUrl($subj->teacher_photo, $subj->teacher_key, $subj->teacher_name);
                    $listInitials = collect(explode(' ', str_ireplace(['TEACHER ', 'TCHR. '], '', $currentTeacherName)))
                        ->filter()->map(fn($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
                    $isEnrolled = $subj->membership_status === 'enrolled';
                @endphp

                <article class="subject-card">
                    <div class="subject-card-head">
                        <button type="button" class="subject-teacher-photo"
                            @if($listPhotoUrl) @click="previewPhoto = { url: '{{ $listPhotoUrl }}', name: '{{ $currentTeacherName }}', role: 'Official Teacher' }" @endif
                            aria-label="View {{ $currentTeacherName }} profile picture">
                            @if($listPhotoUrl)
                                <img src="{{ $listPhotoUrl }}" alt="{{ $currentTeacherName }}" loading="lazy"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <span style="display:none">{{ $listInitials ?: '?' }}</span>
                            @else
                                <span>{{ $listInitials ?: '?' }}</span>
                            @endif
                        </button>

                        <div class="subject-card-copy">
                            <div class="subject-card-title-row">
                                <span class="subject-icon-box"><i data-lucide="{{ $subjectIcon($subj->subject_name) }}"></i></span>
                                <div class="min-w-0">
                                    <h3 title="{{ $subj->subject_name }}">{{ $subj->subject_name }}</h3>
                                    <p class="subject-teacher-name">{{ $currentTeacherName !== '—' ? $currentTeacherName : 'Teacher pending' }}</p>
                                </div>
                            </div>

                            <div class="subject-team-row">
                                <span class="subject-team-name" title="{{ $subj->ms_team_name }}">
                                    <i data-lucide="users-round"></i>{{ $subj->ms_team_name }}
                                </span>
                                <span class="subject-status {{ $isEnrolled ? 'is-enrolled' : 'is-pending' }}">
                                    <span></span>{{ $isEnrolled ? 'Enrolled' : 'Pending' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="subject-card-body">
                        <p class="subject-meta-label">Weekly schedule</p>
                        <div class="subject-schedule">
                            <i data-lucide="clock-3"></i>
                            <span>{{ $subj->schedule ?: 'Schedule to be announced' }}</span>
                        </div>

                        @if($subj->ms_channel_id && $isEnrolled)
                            <a href="{{ $subj->team_url ?? 'https://teams.microsoft.com/' }}"
                               onclick="event.preventDefault(); window.joinTeams('{{ $subj->team_url ?? 'https://teams.microsoft.com/' }}');"
                               class="subject-open-button">
                                <i data-lucide="external-link"></i><span>Open class in Microsoft Teams</span>
                            </a>
                        @elseif($subj->ms_channel_id)
                            <button type="button" disabled class="subject-open-button is-disabled" title="{{ $subj->membership_status_label }}">
                                <i data-lucide="lock-keyhole"></i><span>Teams enrollment pending</span>
                            </button>
                        @else
                            <div class="subject-room-unavailable"><i data-lucide="circle-alert"></i> Teams channel unavailable</div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="s-empty-card" style="background:white;border-radius:20px;border:1.5px solid #e2e8f0;padding:4rem 2rem;">
            <div class="s-empty-icon-wrapper"><i data-lucide="book-open" style="width:32px;height:32px;color:#059669;"></i></div>
            <h3 class="s-empty-title">No Subjects Enrolled</h3>
            <p class="s-empty-text">No subjects have been registered for your section yet.</p>
        </div>
    @endif
</div>

@once
<style>
    .subject-card-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.25rem}
    .subject-card{min-width:0;overflow:hidden;border:1px solid #e2e8f0;border-radius:20px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.03);transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}
    .subject-card:hover{transform:translateY(-2px);border-color:#99f6e4;box-shadow:0 14px 30px rgba(15,23,42,.07)}
    .subject-card-head{display:grid;grid-template-columns:104px minmax(0,1fr);gap:1rem;padding:1.25rem;border-bottom:1px solid #eef2f7;background:linear-gradient(135deg,#fff 0%,#f8fafc 100%)}
    .subject-teacher-photo{width:104px;height:104px;padding:0;overflow:hidden;border:0;border-radius:16px;background:#d1fae5;color:#047857;cursor:pointer;box-shadow:inset 0 0 0 1px #a7f3d0}
    .subject-teacher-photo img,.subject-teacher-photo span{width:100%;height:100%;object-fit:cover;align-items:center;justify-content:center;font-size:1.35rem;font-weight:900}
    .subject-card-copy{display:flex;min-width:0;flex-direction:column;justify-content:space-between;gap:.75rem}
    .subject-card-title-row{display:flex;min-width:0;align-items:flex-start;gap:.75rem}
    .subject-card-title-row .subject-icon-box{display:flex;width:38px;height:38px;flex:0 0 38px;align-items:center;justify-content:center;border-radius:11px;background:#ecfdf5;color:#047857}
    .subject-card-title-row .subject-icon-box svg{width:18px;height:18px}
    .subject-card h3{margin:1px 0 0;color:#0f172a;font-size:1rem;font-weight:900;line-height:1.3;white-space:normal;overflow-wrap:anywhere}
    .subject-teacher-name{margin:.3rem 0 0;color:#64748b;font-size:.78rem;font-weight:700}
    .subject-team-row{display:flex;min-width:0;align-items:center;justify-content:space-between;gap:.5rem}
    .subject-team-name{display:flex;min-width:0;align-items:center;gap:.35rem;color:#64748b;font-size:.7rem;font-weight:750;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .subject-team-name svg{width:13px;height:13px;flex:0 0 auto}
    .subject-status{display:inline-flex;flex:0 0 auto;align-items:center;gap:.3rem;border-radius:999px;padding:.25rem .55rem;font-size:.65rem;font-weight:850}
    .subject-status span{width:5px;height:5px;border-radius:50%}.subject-status.is-enrolled{border:1px solid #bbf7d0;background:#f0fdf4;color:#15803d}.subject-status.is-enrolled span{background:#22c55e}.subject-status.is-pending{border:1px solid #fed7aa;background:#fff7ed;color:#c2410c}.subject-status.is-pending span{background:#f97316}
    .subject-card-body{display:flex;flex-direction:column;gap:.8rem;padding:1.15rem 1.25rem 1.25rem}
    .subject-meta-label{margin:0;color:#94a3b8;font-size:.65rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em}
    .subject-schedule{display:flex;min-width:0;align-items:flex-start;gap:.5rem;border:1px solid #ccfbf1;border-radius:12px;background:#f0fdfa;padding:.65rem .75rem;color:#0f766e;font-size:.75rem;font-weight:800;line-height:1.45}
    .subject-schedule svg{width:15px;height:15px;flex:0 0 auto;margin-top:1px}.subject-schedule span{white-space:normal;overflow-wrap:anywhere}
    .subject-open-button{display:flex;width:100%;align-items:center;justify-content:center;gap:.5rem;border:0;border-radius:12px;background:#4f46e5;padding:.72rem 1rem;color:#fff;text-decoration:none;font-size:.8rem;font-weight:900;transition:background .15s ease}.subject-open-button:hover{background:#4338ca;color:#fff}.subject-open-button svg{width:15px;height:15px}.subject-open-button.is-disabled{background:#e2e8f0;color:#94a3b8;cursor:not-allowed}
    .subject-room-unavailable{display:flex;align-items:center;justify-content:center;gap:.4rem;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;padding:.7rem;color:#94a3b8;font-size:.75rem;font-weight:750}.subject-room-unavailable svg{width:14px;height:14px}
    @media(max-width:900px){.subject-card-grid{grid-template-columns:1fr}}
    @media(max-width:520px){.subject-card-head{grid-template-columns:76px minmax(0,1fr);padding:1rem;gap:.8rem}.subject-teacher-photo{width:76px;height:88px;border-radius:14px}.subject-team-row{align-items:flex-start;flex-direction:column}.subject-card-body{padding:1rem}}
</style>
@endonce
