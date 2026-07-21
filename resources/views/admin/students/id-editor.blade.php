@php
    $getGradeColor = function($grade) {
        if (!$grade) return '#6d28d9';
        $g = strtoupper($grade);
        if (str_contains($g, 'NURSERY') || str_contains($g, 'KINDER') || str_contains($g, 'PRE-')) return '#ea580c';
        if (str_contains($g, 'GRADE 1') || str_contains($g, 'GRADE 2') || str_contains($g, 'GRADE 3')) return '#0284c7';
        if (str_contains($g, 'GRADE 4') || str_contains($g, 'GRADE 5') || str_contains($g, 'GRADE 6')) return '#7c3aed';
        if (str_contains($g, 'GRADE 7') || str_contains($g, 'GRADE 8') || str_contains($g, 'GRADE 9') || str_contains($g, 'GRADE 10')) return '#dc2626';
        if (str_contains($g, 'GRADE 11') || str_contains($g, 'GRADE 12') || str_contains($g, 'GRADE XI') || str_contains($g, 'GRADE XII')) return '#4f46e5';
        return '#6d28d9';
    };
@endphp
<x-admin-layout
    :title="'Student ID Editor - ' . $student->student_number"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => $student->student_number, 'href' => route('admin.students.show', $student)],
        ['label' => 'ID Editor', 'href' => null],
    ]"
>
    <script>
        const SAVE_URL = '{{ route('admin.students.update-id-font-sizes', $student) }}';
        const CSRF_TOKEN = '{{ csrf_token() }}';

        async function idEditorSaveFontSizes(ctx) {
            const btn = document.getElementById('btn-save-font-sizes');
            const oldHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Saving...';

            try {
                const response = await fetch(SAVE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        id_last_name_font_size: ctx.lastNameFontSize,
                        id_first_name_font_size: ctx.firstNameFontSize,
                        id_grade_font_size: ctx.gradeFontSize,
                        id_num_font_size: ctx.idFontSize
                    })
                });

                const result = await response.json();
                if (response.ok && result.success) {
                    btn.innerHTML = 'Saved!';
                    btn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
                    btn.classList.add('bg-blue-600');

                    const toast = document.createElement('div');
                    toast.className = 'fixed bottom-5 right-5 bg-slate-900 text-white px-4 py-3 rounded-2xl shadow-xl flex items-center gap-2 border border-slate-800 z-[99999]';
                    toast.innerHTML = '<span style="color:#10b981;font-weight:900;">\u2713</span>&nbsp;<span>ID settings saved!</span>';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 2500);

                    setTimeout(() => {
                        btn.innerHTML = oldHtml;
                        btn.classList.remove('bg-blue-600');
                        btn.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
                        btn.disabled = false;
                    }, 2000);
                } else {
                    alert(result.message || 'Failed to save font sizes.');
                    btn.innerHTML = oldHtml;
                    btn.disabled = false;
                }
            } catch (err) {
                alert('Error saving: ' + err);
                btn.innerHTML = oldHtml;
                btn.disabled = false;
            }
        }
    </script>

    <div class="w-full min-h-screen pb-12" id="id-editor-root-wrapper"
         x-data="{
             lastNameFontSize: {{ number_format($student->id_last_name_font_size ?: $lastNameFontSize, 1, '.', '') }},
             firstNameFontSize: {{ number_format($student->id_first_name_font_size ?: $displayFirstNameFontSize, 1, '.', '') }},
             gradeFontSize: {{ number_format($student->id_grade_font_size ?: 25, 1, '.', '') }},
             idFontSize: {{ number_format($student->id_num_font_size ?: 10, 1, '.', '') }},
             saveFontSizes() { idEditorSaveFontSizes(this); }
         }"
    >
        <!-- Top Toolbar Header -->
        <div class="max-w-7xl mx-auto px-6 py-6 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md">
            <div>
                <a href="{{ route('admin.students.show', $student) }}" class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition gap-1.5 mb-2">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    <span>Back to Student Profile</span>
                </a>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight flex items-center gap-2.5">
                    <span>✏️ ID Card Layout Editor</span>
                    <span class="text-xs bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/10 dark:bg-emerald-950/20 dark:text-emerald-400 dark:ring-emerald-500/20 px-2.5 py-1 rounded-full font-bold">
                        {{ $studentNumber }}
                    </span>
                </h1>
            </div>
            
            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.students.id-roster-print', $student->studentSection->section ?? -1) }}" target="_blank"
                   class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-slate-850 bg-white dark:bg-slate-900 px-4 text-xs font-bold text-slate-700 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98] transition cursor-pointer">
                    <i data-lucide="layers" class="w-4 h-4 text-slate-500"></i>
                    <span>Section Print Sheet</span>
                </a>
            </div>
        </div>

        <!-- Main Workspace: Side-by-Side ID Previews + Right Sidebar Controls -->
        <div class="max-w-7xl mx-auto px-6 py-8">
            <div class="text-center mb-8">
                <h2 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-wider">{{ strtoupper($lastName) }}, {{ strtoupper($firstName) }}</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-semibold mt-1">
                    {{ strtoupper($student->grade_level) }} 
                    @if($student->studentSection?->section)
                        | {{ strtoupper($student->studentSection->section->name) }}
                    @endif
                    | <span class="text-emerald-600 dark:text-emerald-400">LAYOUT EDITOR</span>
                </p>
            </div>

            <!-- Responsive Workspace Grid: Previews on Left, Sidebar on Right -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start w-full">
                
                <!-- Left Content: Previews (Front ID & Back ID Side-by-Side) -->
                <div class="lg:col-span-2 flex flex-col md:flex-row items-center justify-center gap-4 w-full bg-slate-50/50 dark:bg-slate-950/10 border border-slate-150 dark:border-slate-850/50 rounded-3xl p-6 shadow-xs">
                    
                    <!-- Front Side Card -->
                    <div class="flex flex-col items-center gap-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                            <span class="text-xs font-black text-slate-450 uppercase tracking-widest">Front Side Preview</span>
                        </div>
                        <div id="id-card-front-box" class="relative rounded-3xl overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.15)] border border-slate-250 dark:border-slate-800 flex-shrink-0" style="width: 340px; height: 538px; background-color: #064e3b;">
                            <!-- Background template image (Top Layer) -->
                            <img src="{{ asset('images/id/amis_frontid.png') }}?v={{ filemtime(public_path('images/id/amis_frontid.png')) }}" class="absolute inset-0 w-full h-full object-cover" style="z-index: 10; pointer-events: none;" alt="AMIS ID Template">
                            
                            <!-- Student Photo with Edit Overlay (Super Admins Only) -->
                            @if(auth()->user()?->hasRole('super_admin'))
                                 <div class="photo-clip group cursor-pointer" 
                                      onclick="openPhotoOptionsModal()"
                                      style="left: 71px; top: 144px; width: 198px; height: 192px; border-radius: 6px; z-index: 5;"
                                      title="Edit Photo">
                                    @if($photoUrl)
                                        <img id="id-preview-photo" src="{{ $photoUrl }}" class="transition duration-300 group-hover:scale-105 group-hover:brightness-75" style="object-position: center center;">
                                    @else
                                        <div class="absolute inset-0 bg-slate-150 flex flex-col items-center justify-center text-center border border-dashed border-slate-300 text-[10px] font-bold text-slate-450 gap-1 z-1">
                                            <i data-lucide="camera" class="w-5 h-5 text-slate-400"></i>
                                            <span>UPLOAD</span>
                                        </div>
                                    @endif
                                    <!-- Simple Edit Icon Overlay on hover -->
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity duration-200 text-white" style="z-index: 20;">
                                        <div class="bg-white/20 backdrop-blur-md rounded-full p-2.5 border border-white/30 shadow-md transform scale-90 group-hover:scale-100 transition duration-200">
                                            <i data-lucide="camera" class="w-5 h-5 text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <!-- Non-admin read-only image -->
                                <div class="photo-clip" style="left: 71px; top: 144px; width: 198px; height: 192px; border-radius: 6px; z-index: 5;">
                                    @if($photoUrl)
                                        <img id="id-preview-photo" src="{{ $photoUrl }}" style="object-position: center center;">
                                    @else
                                        <div class="absolute inset-0 bg-slate-100 flex items-center justify-center text-center border border-dashed border-slate-300 text-[10px] font-bold text-slate-450 z-1">NO PHOTO</div>
                                    @endif
                                </div>
                            @endif

                            <!-- Student ID -->
                            <div class="absolute text-white font-black tracking-wide text-center uppercase" style="left: 0; top: 325px; width: 340px; height: 15px; z-index: 20; line-height: 15px;" :style="{ fontSize: idFontSize + 'px' }">{{ $studentNumber }}</div>

                            <!-- Last Name -->
                            <div class="absolute text-center font-black text-[#0f172a] uppercase tracking-tight flex flex-col justify-center items-center" style="left: 15px; top: 352px; width: 310px; height: 32px; z-index: 20; padding: 0 16px; {{ $lastNameStyle }} line-height: 1; letter-spacing: -0.5px;" :style="{ fontSize: lastNameFontSize + 'px' }">{{ $lastName }}</div>

                            <!-- First Name -->
                            <div class="absolute text-center font-bold text-[#334155] uppercase leading-none flex flex-col justify-center items-center" style="left: 15px; top: 386px; width: 310px; height: 22px; z-index: 20; padding: 0 16px; line-height: 1;" :style="{ fontSize: firstNameFontSize + 'px' }">{{ $displayFirstName }}</div>

                            <!-- Grade Level -->
                            <div class="absolute text-center font-black uppercase tracking-wide flex flex-col justify-center items-center" style="left: 15px; top: 412px; width: 310px; height: 30px; z-index: 20; padding: 0 16px; line-height: 1; letter-spacing: 0.5px; text-shadow: 0 1px 1px rgba(0,0,0,0.05); color: {{ $getGradeColor($displayGrade) }};" :style="{ fontSize: gradeFontSize + 'px' }">{{ $displayGrade }}</div>

                            <!-- LRN -->
                            @if($student->applicant?->lrn && !in_array(strtoupper($student->applicant->lrn), ['N/A', 'NA', 'EMPTY', '']))
                                <div class="absolute font-bold text-[#1e293b] whitespace-nowrap" style="left: 239px; top: 394px; width: 170px; height: 22px; z-index: 20; font-size: 15.5px; transform: rotate(-90deg); transform-origin: center; display: flex; align-items: center; justify-content: flex-start; letter-spacing: 0.05em;">
                                    LRN: <span style="margin-left: 4px;">{{ $student->applicant->lrn }}</span>
                                </div>
                            @endif

                            <!-- QR Code -->
                            <div class="absolute p-0.5 rounded bg-white" style="left: 134.5px; top: 458px; width: 71px; height: 71px; z-index: 20;">
                                <img src="{{ $qrCodeBase64 ?: $qrCodeUrl }}" alt="QR Verification" class="w-full h-full object-contain">
                            </div>
                        </div>
                        <!-- Empty spacer to match the height of the back card footer text for perfect vertical alignment -->
                        <span class="text-[10px] text-transparent select-none mt-1">Spacer</span>
                    </div>
                    
                    <!-- Back Side Card -->
                    <div class="flex flex-col items-center gap-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-xs font-black text-slate-450 uppercase tracking-widest">Back Side Preview</span>
                        </div>
                        <div id="id-card-back-box" class="relative rounded-3xl overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.15)] border border-slate-250 dark:border-slate-800 flex-shrink-0" style="width: 340px; height: 538px; background-color: #064e3b;">
                            <!-- Background template image -->
                            <img src="{{ asset('images/id/amis_backid.png') }}?v=1" class="absolute inset-0 w-full h-full object-cover" style="z-index: 1; pointer-events: none;" alt="AMIS ID Template Back">

                            <!-- Emergency Details List -->
                            @php
                                $parentNameLen = strlen($emergencyName);
                                $parentNameFontSize = $parentNameLen > 24 ? '14px' : ($parentNameLen > 18 ? '16px' : '19px');
                                
                                $addressLen = strlen($homeAddress);
                                $addressFontSize = $addressLen > 60 ? '12px' : ($addressLen > 40 ? '13px' : '14px');
                            @endphp
                            <div class="emergency-info" style="position: absolute; left: 28px; top: 85px; width: 284px; z-index: 10; display: flex; flex-direction: column; gap: 7px;">
                                <!-- Contact Name -->
                                <div class="emerg-row" style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span class="emerg-icon" style="flex-shrink: 0; width: 14px; height: 14px; color: #047857; margin-top: 1.5px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" /></svg>
                                    </span>
                                    <div class="emerg-text" style="text-align: left; font-family: 'Outfit', sans-serif; font-size: {{ $parentNameFontSize }}; font-weight: 900; text-transform: uppercase; color: #0f172a; line-height: 1.1;">
                                        {{ $emergencyName }}
                                    </div>
                                </div>

                                <!-- Relationship Label -->
                                <div class="emerg-row" style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span class="emerg-icon" style="flex-shrink: 0; width: 14px; height: 14px; color: #047857; margin-top: 1.5px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" clip-rule="evenodd" /></svg>
                                    </span>
                                    <div class="emerg-text" style="text-align: left; font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; text-transform: uppercase; color: #475569; line-height: 1;">
                                        @php
                                            $relationship = 'PARENT / GUARDIAN';
                                            if (!empty($applicant->father_first_name) && str_contains(strtolower($emergencyName), strtolower($applicant->father_first_name))) {
                                                $relationship = 'FATHER';
                                            } elseif (!empty($applicant->mother_first_name) && str_contains(strtolower($emergencyName), strtolower($applicant->mother_first_name))) {
                                                $relationship = 'MOTHER';
                                            }
                                        @endphp
                                        {{ $relationship }}
                                    </div>
                                </div>

                                <!-- Contact Number -->
                                <div class="emerg-row" style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span class="emerg-icon" style="flex-shrink: 0; width: 14px; height: 14px; color: #047857; margin-top: 1.5px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l.545 2.181a1.875 1.875 0 0 1-.585 1.83l-1.503 1.201a14.73 14.73 0 0 0 6.182 6.183l1.202-1.502a1.875 1.875 0 0 1 1.83-.585l2.181.545a1.875 1.875 0 0 1 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z" clip-rule="evenodd" /></svg>
                                    </span>
                                    <div class="emerg-text" style="text-align: left; font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 800; color: #1e293b; line-height: 1;">
                                        {{ $emergencyPhone }}
                                    </div>
                                </div>

                                <!-- Address -->
                                <div class="emerg-row" style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span class="emerg-icon" style="flex-shrink: 0; width: 14px; height: 14px; color: #047857; margin-top: 2.5px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.155-1.071 31.06 31.06 0 004.01-4.578c2.112-2.923 3.402-6.166 3.402-9.336a8.91 8.91 0 00-18 0c0 3.17 1.29 6.413 3.402 9.336a31.06 31.06 0 004.01 4.578 16.975 16.975 0 001.156 1.071zM12 8.25a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5z" clip-rule="evenodd" /></svg>
                                    </span>
                                    <div class="emerg-text" style="text-align: left; font-family: 'Outfit', sans-serif; font-size: {{ $addressFontSize }}; font-weight: 700; color: #475569; line-height: 1.25; text-transform: uppercase;">
                                        {{ $homeAddress }}
                                    </div>
                                </div>
                            </div>

                            <!-- Director's Signature Box -->
                            <div class="back-signature-qr" style="position: absolute; left: 142.5px; top: 422px; width: 55px; height: 55px; z-index: 25; padding: 1.5px; border-radius: 2px; background: white; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                <img src="{{ $signatureQrBase64 ?: $signatureRawUrl }}" alt="Signature QR" class="w-full h-full object-contain">
                            </div>
                        </div>
                        <span class="text-[10px] text-slate-400 font-semibold mt-1">Back Emergency Info Sheet</span>
                    </div>
                </div>

                <!-- Right Sidebar Panel: Layout Size Controls -->
                <div class="lg:col-span-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col gap-6 self-start sticky top-6">
                    
                    <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h3 class="text-xs font-black text-slate-450 uppercase tracking-widest flex items-center gap-2">
                            <i data-lucide="sliders" class="w-4.5 h-4.5 text-emerald-600"></i>
                            <span>Text Font Sizes (px)</span>
                        </h3>
                    </div>

                    <!-- Sliders Grid (Vertical Stack) -->
                    <div class="flex flex-col gap-5 w-full">
                        <!-- Last Name Slider (UNLOCKED) -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <span>Last Name</span>
                                <span class="text-slate-900 dark:text-white font-black" x-text="lastNameFontSize + 'px'"></span>
                            </div>
                            <input type="range" min="10" max="45" step="0.5" x-model="lastNameFontSize" 
                                   class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-600">
                        </div>

                        <!-- First Name Slider (LOCKED) -->
                        <div class="space-y-2 opacity-60">
                            <div class="flex justify-between items-center text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                                <span>First Name 🔒</span>
                                <span class="text-slate-400 dark:text-slate-500 font-bold" x-text="firstNameFontSize + 'px'"></span>
                            </div>
                            <input type="range" min="8" max="25" step="0.5" x-model="firstNameFontSize" disabled
                                   class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg appearance-none cursor-not-allowed">
                        </div>

                        <!-- Grade Level Slider (LOCKED) -->
                        <div class="space-y-2 opacity-60">
                            <div class="flex justify-between items-center text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                                <span>Grade Level 🔒</span>
                                <span class="text-slate-400 dark:text-slate-500 font-bold" x-text="gradeFontSize + 'px'"></span>
                            </div>
                            <input type="range" min="12" max="35" step="0.5" x-model="gradeFontSize" disabled
                                   class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg appearance-none cursor-not-allowed">
                        </div>

                        <!-- Student ID Slider (LOCKED) -->
                        <div class="space-y-2 opacity-60">
                            <div class="flex justify-between items-center text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                                <span>Student ID 🔒</span>
                                <span class="text-slate-400 dark:text-slate-500 font-bold" x-text="idFontSize + 'px'"></span>
                            </div>
                            <input type="range" min="8" max="18" step="0.5" x-model="idFontSize" disabled
                                   class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg appearance-none cursor-not-allowed">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <!-- Save ID Settings -->
                        <button type="button" id="btn-save-font-sizes" @click="saveFontSizes()" 
                                class="w-full h-11 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-md transition active:scale-[0.97] cursor-pointer flex items-center justify-center gap-2 border border-emerald-500 whitespace-nowrap">
                            <i data-lucide="save" class="w-4.5 h-4.5"></i>
                            <span>Save ID Settings</span>
                        </button>

                        <!-- Reset -->
                        <button type="button" @click="
                            lastNameFontSize = {{ $lastNameFontSize }};
                            firstNameFontSize = {{ $displayFirstNameFontSize }};
                            gradeFontSize = 25;
                            idFontSize = 10;
                        " class="w-full h-11 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-800 transition active:scale-[0.97] cursor-pointer flex items-center justify-center gap-1.5 shadow-sm bg-white dark:bg-slate-900">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                            <span>Reset Default Sizes</span>
                        </button>
                    </div>

                    <!-- Photo Upload Button (Super Admins Only) -->
                    @if(auth()->user()?->hasRole('super_admin'))
                        <div class="pt-1">
                            <button type="button" onclick="openPhotoOptionsModal()" class="w-full h-11 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-350 hover:text-emerald-800 dark:hover:bg-emerald-900 transition active:scale-[0.97] cursor-pointer flex items-center justify-center gap-2 shadow-sm border border-emerald-150 dark:border-emerald-900 text-xs font-bold">
                                <i data-lucide="camera" class="w-4.5 h-4.5"></i>
                                <span>Upload / Edit Photo</span>
                            </button>
                        </div>
                    @endif

                    <!-- Student Navigation -->
                    <div class="border-t border-slate-100 dark:border-slate-800 pt-5 flex items-center gap-2">
                        @if($prevStudentId)
                            <a href="{{ route('admin.students.id-editor', $prevStudentId) }}" class="flex-1 h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-800 transition active:scale-[0.97] flex items-center justify-center gap-1.5 shadow-sm bg-white dark:bg-slate-900">
                                <i data-lucide="chevron-left" class="w-4 h-4 flex-shrink-0"></i>
                                <span>Prev</span>
                            </a>
                        @else
                            <button type="button" disabled class="flex-1 h-10 px-4 rounded-xl border border-slate-100 dark:border-slate-850 text-xs font-bold text-slate-300 dark:text-slate-650 cursor-not-allowed flex items-center justify-center gap-1.5">
                                <i data-lucide="chevron-left" class="w-4 h-4 flex-shrink-0"></i>
                                <span>Prev</span>
                            </button>
                        @endif

                        @if($nextStudentId)
                            <a href="{{ route('admin.students.id-editor', $nextStudentId) }}" class="flex-1 h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-800 transition active:scale-[0.97] flex items-center justify-center gap-1.5 shadow-sm bg-white dark:bg-slate-900">
                                <span>Next</span>
                                <i data-lucide="chevron-right" class="w-4 h-4 flex-shrink-0"></i>
                            </a>
                        @else
                            <button type="button" disabled class="flex-1 h-10 px-4 rounded-xl border border-slate-100 dark:border-slate-850 text-xs font-bold text-slate-300 dark:text-slate-650 cursor-not-allowed flex items-center justify-center gap-1.5">
                                <span>Next</span>
                                <i data-lucide="chevron-right" class="w-4 h-4 flex-shrink-0"></i>
                            </button>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Front Card and General Clip styles -->
    <style>
        .photo-clip {
            position: absolute;
            overflow: hidden;
            background: transparent;
            border-radius: 14px;
        }
        .photo-clip img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            z-index: 1;
        }
    </style>

    <!-- Photo Options and Cropping Modals -->
    @if(auth()->user()?->hasRole('super_admin'))
        <div id="photo-options-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden items-center justify-center p-4 z-[99999]">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-150 dark:border-slate-800 text-center animate-scale-in">
                <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-950/40 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="user-cog" class="w-6 h-6 text-emerald-600"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white uppercase mb-1">Edit Student Photo</h3>
                <p class="text-xs text-slate-500 mb-6">Choose an action below to update the photo.</p>
                
                <div class="space-y-2">
                    <button type="button" onclick="triggerFileInput()" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition active:scale-[0.98]">
                        📤 Upload New Photo
                    </button>
                    @if($student->school_email)
                        <button type="button" onclick="syncMicrosoftPhoto()" id="btn-sync-ms" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition active:scale-[0.98] flex items-center justify-center gap-1.5">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5" id="ms-sync-spinner"></i>
                            <span>Pull from Microsoft 365</span>
                        </button>
                    @endif
                    @if($photoUrl)
                        <button type="button" onclick="deletePhoto()" class="w-full py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 dark:bg-rose-950/30 dark:hover:bg-rose-900/40 rounded-xl text-xs font-bold transition active:scale-[0.98]">
                            🗑️ Delete / Reset Photo
                        </button>
                    @endif
                    <button type="button" onclick="closePhotoOptionsModal()" class="w-full py-2.5 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-slate-700 dark:hover:text-slate-350 rounded-xl text-xs font-bold transition">
                        Cancel
                    </button>
                </div>
                <input type="file" id="id-photo-file-input" class="hidden" accept="image/jpeg,image/png,image/jpg" onchange="loadPhotoToCrop(event)">
            </div>
        </div>

        <!-- Crop Photo Modal -->
        <div id="photo-crop-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs hidden items-center justify-center p-4 z-[99999]">
            <div class="bg-slate-900 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-850 flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-850 bg-slate-900">
                    <h3 class="text-sm font-bold text-white uppercase flex items-center gap-2">
                        <i data-lucide="crop" class="w-4.5 h-4.5 text-emerald-500"></i>
                        <span>Crop Student Photo</span>
                    </h3>
                    <button type="button" onclick="closeCropModal()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>
                <div class="p-6 bg-slate-950 flex items-center justify-center min-h-[300px]">
                    <img id="crop-image-element" class="max-h-[50vh] max-w-full hidden">
                </div>
                <div class="flex items-center justify-end px-6 py-4 border-t border-slate-850 bg-slate-900 gap-2">
                    <button type="button" onclick="closeCropModal()" class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-white rounded-xl transition">Cancel</button>
                    <button type="button" onclick="uploadCroppedPhoto()" id="btn-upload-cropped" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1">
                        <span>Save & Apply Photo</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Cropper JS and dynamic handlers -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
        <style>
            #photo-crop-modal .cropper-view-box,
            #photo-crop-modal .cropper-face {
                border-radius: 14px;
                box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.72);
            }
            #photo-crop-modal .cropper-view-box {
                outline: 4px solid #047857;
                outline-offset: -1px;
            }
            #photo-crop-modal .cropper-line,
            #photo-crop-modal .cropper-point {
                display: none !important;
            }
            #photo-crop-modal .cropper-bg {
                background-image: none !important;
                background-color: #090d16 !important;
            }
        </style>

        <script>
            let cropper = null;

            function openPhotoOptionsModal() {
                document.getElementById('photo-options-modal').style.display = 'flex';
            }

            function closePhotoOptionsModal() {
                document.getElementById('photo-options-modal').style.display = 'none';
            }

            function triggerFileInput() {
                closePhotoOptionsModal();
                document.getElementById('id-photo-file-input').click();
            }

            function loadPhotoToCrop(event) {
                const files = event.target.files;
                if (!files || !files.length) return;
                
                const file = files[0];
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('crop-image-element');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    
                    document.getElementById('photo-crop-modal').style.display = 'flex';
                    
                    if (cropper) cropper.destroy();
                    
                    cropper = new Cropper(img, {
                        aspectRatio: 166 / 162, // Aspect ratio of the photo box in ID template
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        restore: false,
                        modal: false,
                        guides: false,
                        highlight: false,
                        cropBoxMovable: false,
                        cropBoxResizable: false,
                        toggleDragModeOnDblclick: false
                    });
                };
                reader.readAsDataURL(file);
            }

            function closeCropModal() {
                document.getElementById('photo-crop-modal').style.display = 'none';
                if (cropper) cropper.destroy();
                document.getElementById('id-photo-file-input').value = '';
            }

            function uploadCroppedPhoto() {
                if (!cropper) return;
                
                const btn = document.getElementById('btn-upload-cropped');
                const oldText = btn.innerText;
                btn.disabled = true;
                btn.innerText = 'Uploading...';
                
                const canvas = cropper.getCroppedCanvas({
                    width: 332,
                    height: 324
                });
                
                canvas.toBlob(async function(blob) {
                    const formData = new FormData();
                    formData.append('photo', blob, 'photo.jpg');
                    formData.append('_token', '{{ csrf_token() }}');
                    
                    try {
                        const response = await fetch('{{ route('admin.students.update-photo', $student) }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        const result = await response.json();
                        if (response.ok && result.success) {
                            // Update both ID photo displays
                            const idPhotos = document.querySelectorAll('#id-preview-photo');
                            idPhotos.forEach(img => img.src = result.photo_url);
                            
                            // Visual success toast
                            const toast = document.createElement('div');
                            toast.className = 'fixed bottom-5 right-5 bg-slate-900 text-white px-4 py-3 rounded-2xl shadow-xl flex items-center gap-2 border border-slate-800 animate-fade-in z-[99999]';
                            toast.innerHTML = `<span class="text-emerald-500 font-extrabold">✓</span> <span>Photo uploaded successfully!</span>`;
                            document.body.appendChild(toast);
                            setTimeout(() => toast.remove(), 2500);

                            closeCropModal();
                        } else {
                            alert(result.message || 'Failed to upload photo.');
                            btn.disabled = false;
                            btn.innerText = oldText;
                        }
                    } catch (err) {
                        alert('Error uploading photo: ' + err);
                        btn.disabled = false;
                        btn.innerText = oldText;
                    }
                });
            }

            async function syncMicrosoftPhoto() {
                const btn = document.getElementById('btn-sync-ms');
                const spinner = document.getElementById('ms-sync-spinner');
                
                btn.disabled = true;
                spinner.classList.add('animate-spin');
                
                try {
                    const response = await fetch('{{ route('admin.students.sync-microsoft-photo', $student) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const result = await response.json();
                    if (response.ok && result.success) {
                        const idPhotos = document.querySelectorAll('#id-preview-photo');
                        idPhotos.forEach(img => img.src = result.photo_url);

                        const toast = document.createElement('div');
                        toast.className = 'fixed bottom-5 right-5 bg-slate-900 text-white px-4 py-3 rounded-2xl shadow-xl flex items-center gap-2 border border-slate-800 animate-fade-in z-[99999]';
                        toast.innerHTML = `<span class="text-emerald-500 font-extrabold">✓</span> <span>M365 Photo synced successfully!</span>`;
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 2500);

                        closePhotoOptionsModal();
                    } else {
                        alert(result.message || 'Failed to sync photo.');
                    }
                } catch (err) {
                    alert('Error syncing photo: ' + err);
                } finally {
                    btn.disabled = false;
                    spinner.classList.remove('animate-spin');
                }
            }

            async function deletePhoto() {
                if (!confirm('Are you sure you want to delete this student photo and reset to default?')) return;
                
                try {
                    const response = await fetch('{{ route('admin.students.delete-photo', $student) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const result = await response.json();
                    if (response.ok && result.success) {
                        location.reload();
                    } else {
                        alert(result.message || 'Failed to reset photo.');
                    }
                } catch (err) {
                    alert('Error resetting photo: ' + err);
                }
            }
        </script>
    @endif
</x-admin-layout>
