<!-- Subject List Tab -->
<div x-show="currentTab === 'list'" class="space-y-6">
    @if($subjects->isNotEmpty())
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 1.5rem;">
            @foreach($subjects as $subj)
                @php
                    $currentTeacherName = $teacherName($subj);
                    $listPhotoUrl = $getPhotoUrl($subj->teacher_photo, $subj->teacher_key, $subj->teacher_name);
                @endphp
                <article class="s-quick-actions-card" style="background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; min-height: 230px; padding: 0;">
                    <div class="teacher-strip" style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0; height: 110px; display: grid; grid-template-columns: 90px 1fr;">
                        <div class="teacher-photo-panel"
                             @if($listPhotoUrl) @click="previewPhoto = { url: '{{ $listPhotoUrl }}', name: '{{ $currentTeacherName }}', role: 'Official Teacher' }" @endif
                             style="cursor: pointer; display: flex; align-items: center; justify-content: center; background: #ecfdf5; overflow: hidden;"
                             title="Click to preview profile picture">
                            @if($listPhotoUrl)
                                <img src="{{ $listPhotoUrl }}" alt="{{ $currentTeacherName }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                @php
                                    $listInitials = collect(explode(' ', str_ireplace('TEACHER ', '', $currentTeacherName)))
                                        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                        ->take(2)
                                        ->implode('');
                                @endphp
                                <span style="font-size: 1.25rem; font-weight: 850; color: #047857;">{{ $listInitials ?: '?' }}</span>
                            @endif
                        </div>

                        <div style="display: flex; align-items: start; gap: 0.75rem; padding: 1rem; min-width: 0;">
                            <span class="subject-icon-box" style="height: 2.25rem; width: 2.25rem; border-radius: 0.75rem;">
                                <i data-lucide="{{ $subjectIcon($subj->subject_name) }}" style="width: 18px; height: 18px;"></i>
                            </span>

                            <div style="min-width: 0; flex: 1;">
                                <h4 style="font-size: 0.95rem; font-weight: 850; color: #0f172a; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $subj->subject_name }}">{{ $subj->subject_name }}</h4>
                                 <p style="font-size: 0.65rem; font-weight: 850; color: #94a3b8; text-transform: uppercase; margin: 0.5rem 0 0; letter-spacing: 0.05em;">MS Team & Status</p>
                                 <p style="font-size: 0.8rem; font-weight: 750; color: #475569; margin: 0.15rem 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $subj->ms_team_name }}">{{ $subj->ms_team_name }}</p>
                                 <div style="margin-top: 0.25rem;">
                                     @if($subj->membership_status === 'enrolled')
                                         <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.65rem;font-weight:700;color:#15803d;background:#f0fdf4;border:1px solid #bbf7d0;padding:0.1rem 0.4rem;border-radius:5px;white-space:nowrap;">
                                             <span style="width:4px;height:4px;background:#16a34a;border-radius:50%;"></span>Enrolled
                                         </span>
                                     @elseif($subj->membership_status === 'not_enrolled')
                                         <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.65rem;font-weight:700;color:#c2410c;background:#fff7ed;border:1px solid #fed7aa;padding:0.1rem 0.4rem;border-radius:5px;white-space:nowrap;" title="Not yet enrolled in Microsoft Teams. Click 'Sync MS Teams' on dashboard to retry.">
                                             <span style="width:4px;height:4px;background:#ea580c;border-radius:50%;"></span>Not Enrolled
                                         </span>
                                     @else
                                         <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.65rem;font-weight:700;color:#b91c1c;background:#fef2f2;border:1px solid #fca5a5;padding:0.1rem 0.4rem;border-radius:5px;white-space:nowrap;" title="Section has no Microsoft Team ID.">
                                             <span style="width:4px;height:4px;background:#dc2626;border-radius:50%;"></span>No Team ID
                                         </span>
                                     @endif
                                 </div>
                             </div>
                         </div>
                    </div>

                    <div style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; gap: 1rem; flex: 1;">
                        <div>
                            <p style="font-size: 0.65rem; font-weight: 850; color: #94a3b8; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Weekly Schedule</p>
                            <div style="margin-top: 0.25rem; display: inline-flex; max-width: 100%; align-items: center; gap: 0.25rem; border-radius: 999px; border: 1px solid #ccfbf1; background: #f0fdfa; font-size: 0.75rem; font-weight: 850; color: #0f766e; padding: 0.25rem 0.65rem;">
                                <i data-lucide="clock" style="width: 12px; height: 12px; flex-shrink: 0; color: #0d9488;"></i>
                                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $subj->schedule ?: 'To Be Announced' }}</span>
                            </div>
                        </div>

                        <div>
                            @if($subj->ms_channel_id)
                                @if($subj->is_joinable)
                                    <a href="{{ $subj->team_url ?? 'https://teams.microsoft.com/' }}" onclick="event.preventDefault(); window.joinTeams('{{ $subj->team_url ?? 'https://teams.microsoft.com/' }}');" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 900; color: white; background: #5865f2; padding: 0.6rem; border-radius: 12px; text-decoration: none; transition: background 0.15s; cursor: pointer;"
                                       onmouseover="this.style.background='#4752c4'" onmouseout="this.style.background='#5865f2'">
                                        <i data-lucide="video" class="w-4 h-4"></i>
                                        <span>Join Class</span>
                                    </a>
                                @else
                                    <button type="button" disabled style="display: flex; width: 100%; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 900; color: #94a3b8; background: #cbd5e1; border: none; padding: 0.6rem; border-radius: 12px; cursor: not-allowed; opacity: 0.85;"
                                       title="{{ $subj->membership_status_label }}">
                                        <i data-lucide="lock" class="w-4 h-4"></i>
                                        <span>Join Class</span>
                                    </button>
                                @endif
                           @else
                               <div style="text-align: center; padding: 0.5rem; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 0.75rem; font-weight: 750; color: #94a3b8;">
                                   Live room unavailable
                               </div>
                           @endif
                       </div>
                   </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="s-empty-card" style="background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; padding: 4rem 2rem;">
            <div class="s-empty-icon-wrapper">
                <i data-lucide="book-open" style="width: 32px; height: 32px; color: #059669; flex-shrink: 0;"></i>
            </div>
            <h3 class="s-empty-title">No Subjects Enrolled</h3>
            <p class="s-empty-text">No subjects have been registered for your section yet.</p>
        </div>
    @endif
</div>
