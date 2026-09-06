<x-student-layout title="Announcements">

@php
    $categories = [
        'all' => 'All Notices',
        'portal' => 'Portal Updates',
        'academic' => 'Academic',
        'finance' => 'Finance & Billing',
        'campus' => 'Campus Life',
    ];

    $toneMap = [
        'emerald' => [
            'accent' => '#059669',
            'bg' => '#ecfdf5',
            'border' => '#a7f3d0',
            'text' => '#065f46',
            'icon_bg' => '#d1fae5',
        ],
        'sky' => [
            'accent' => '#0284c7',
            'bg' => '#f0f9ff',
            'border' => '#bae6fd',
            'text' => '#0369a1',
            'icon_bg' => '#e0f2fe',
        ],
        'amber' => [
            'accent' => '#d97706',
            'bg' => '#fffbeb',
            'border' => '#fde68a',
            'text' => '#92400e',
            'icon_bg' => '#fef3c7',
        ],
        'indigo' => [
            'accent' => '#4f46e5',
            'bg' => '#eef2ff',
            'border' => '#c7d2fe',
            'text' => '#3730a3',
            'icon_bg' => '#e0e7ff',
        ],
    ];
@endphp

<div class="space-y-6" x-data="{ activeCategory: 'all', searchQuery: '' }">

    <!-- 1. Emerald Hero Banner (Senior & Student Friendly) -->
    <div style="background: linear-gradient(135deg, #064e3b 0%, #047857 55%, #0d9488 100%); color: #ffffff; padding: 1.35rem 1.75rem; border-radius: 20px; box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.08); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); border: 1.5px solid rgba(255,255,255,0.3); flex-shrink: 0;">
                <i data-lucide="megaphone" style="width: 26px; height: 26px; color: #ffffff;"></i>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 1.3rem; font-weight: 900; letter-spacing: 0.01em; text-transform: uppercase; color: #ffffff; line-height: 1.2;">
                    Official Notice & Circulars Board
                </h2>
                <p style="margin: 4px 0 0 0; font-size: 14.5px; color: #e6fffa; font-weight: 600;">
                    Official school announcements, academic schedules, and reminders for School Year {{ $student?->school_year ?? '2026-2027' }}.
                </p>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <span style="font-size: 14px; font-weight: 800; background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); border: 1.5px solid rgba(255,255,255,0.35); color: #ffffff; padding: 0.45rem 1rem; border-radius: 999px; letter-spacing: 0.02em;">
                {{ count($announcements) }} {{ count($announcements) === 1 ? 'Notice' : 'Notices' }}
            </span>
            <span style="font-size: 14px; font-weight: 800; background: rgba(255,255,255,0.15); border: 1.5px solid rgba(255,255,255,0.25); color: #ffffff; padding: 0.45rem 1rem; border-radius: 999px;">
                {{ $student?->grade_level ?? 'All Students' }}
            </span>
        </div>
    </div>

    <!-- 2. Controls Bar (Category Filter Pills + Real-Time Search) -->
    <div style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 18px; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
        
        <!-- Category Filter Pills -->
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            @foreach($categories as $catKey => $catLabel)
                <button type="button"
                        @click="activeCategory = '{{ $catKey }}'"
                        :style="activeCategory === '{{ $catKey }}' 
                            ? 'background: #047857; color: #ffffff; border-color: #047857; box-shadow: 0 2px 6px rgba(4,120,87,0.25);' 
                            : 'background: #f1f5f9; color: #334155; border-color: #cbd5e1;'"
                        style="border: 1.5px solid; border-radius: 10px; padding: 0.5rem 1rem; font-size: 14px; font-weight: 800; cursor: pointer; transition: all 0.15s ease; display: inline-flex; align-items: center; gap: 0.35rem;">
                    <span>{{ $catLabel }}</span>
                    @if($catKey === 'all')
                        <span style="font-size: 12px; opacity: 0.85; background: rgba(0,0,0,0.08); padding: 0.1rem 0.45rem; border-radius: 999px;">
                            {{ count($announcements) }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        <!-- Search Bar -->
        <div style="position: relative; min-width: 250px; flex-grow: 1; max-width: 360px;">
            <i data-lucide="search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 17px; height: 17px; color: #64748b; pointer-events: none;"></i>
            <input type="text" 
                   x-model="searchQuery" 
                   placeholder="Search notices by keyword..." 
                   style="width: 100%; height: 42px; padding: 0.5rem 1rem 0.5rem 2.5rem; border: 1.5px solid #cbd5e1; border-radius: 12px; font-size: 14px; font-weight: 600; color: #0f172a; outline: none; background: #fafbfc; transition: border-color 0.15s ease;"
                   @focus="$el.style.borderColor = '#059669'; $el.style.background = '#ffffff';"
                   @blur="$el.style.borderColor = '#cbd5e1'; $el.style.background = '#fafbfc';">
            <button type="button" 
                    x-show="searchQuery.length > 0" 
                    @click="searchQuery = ''" 
                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: transparent; cursor: pointer; color: #94a3b8;">
                <i data-lucide="x" style="width: 16px; height: 16px;"></i>
            </button>
        </div>
    </div>

    <!-- 3. Announcements Feed Cards -->
    @if(count($announcements) > 0)
        <div class="space-y-4">
            @foreach ($announcements as $announcement)
                @php
                    $category = $announcement['category'] ?? 'portal';
                    $tone = $toneMap[$announcement['tone']] ?? $toneMap['emerald'];
                    $isPinned = !empty($announcement['is_pinned']);
                    $searchData = strtolower(addslashes($announcement['title'] . ' ' . $announcement['summary'] . ' ' . ($announcement['details'] ?? '')));
                @endphp

                <div x-show="(activeCategory === 'all' || activeCategory === '{{ $category }}') && (!searchQuery || '{{ $searchData }}'.includes(searchQuery.toLowerCase().trim()))"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform -translate-y-1"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="announcement-card"
                     style="background: #ffffff; border: 1.5px solid #cbd5e1; border-left: 6px solid {{ $tone['accent'] }}; border-radius: 18px; padding: 1.5rem; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04); display: flex; flex-direction: column; gap: 1rem; transition: all 0.2s ease;">
                    
                    <!-- Top Meta Line -->
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <span style="font-size: 12.5px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.04em; color: {{ $tone['text'] }}; background: {{ $tone['bg'] }}; border: 1.5px solid {{ $tone['border'] }}; padding: 0.25rem 0.75rem; border-radius: 8px;">
                                {{ $announcement['type'] }}
                            </span>

                            @if($isPinned)
                                <span style="font-size: 12px; font-weight: 900; color: #92400e; background: #fef3c7; border: 1.5px solid #fde68a; padding: 0.22rem 0.65rem; border-radius: 999px; display: inline-flex; align-items: center; gap: 0.35rem;">
                                    <i data-lucide="pin" style="width: 13px; height: 13px;"></i>
                                    PINNED NOTICE
                                </span>
                            @endif

                            @if(!empty($announcement['audience']))
                                <span style="font-size: 12.5px; font-weight: 750; color: #334155; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 0.22rem 0.65rem; border-radius: 999px; display: inline-flex; align-items: center; gap: 0.35rem;">
                                    <i data-lucide="users" style="width: 13px; height: 13px; color: #059669;"></i>
                                    <span>Target: <strong style="color: #0f172a;">{{ $announcement['audience'] }}</strong></span>
                                </span>
                            @endif
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.4rem; font-size: 13.5px; font-weight: 750; color: #64748b;">
                            <i data-lucide="calendar" style="width: 15px; height: 15px; color: #059669;"></i>
                            <span>{{ $announcement['date'] }}</span>
                        </div>
                    </div>

                    <!-- Main Announcement Content -->
                    <div style="display: flex; align-items: flex-start; gap: 1.15rem;">
                        <div style="width: 48px; height: 48px; border-radius: 12px; background: {{ $tone['icon_bg'] }}; border: 1.5px solid {{ $tone['border'] }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: {{ $tone['accent'] }};">
                            <i data-lucide="{{ $announcement['icon'] ?? 'megaphone' }}" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <h3 style="margin: 0; font-size: 18px; font-weight: 900; color: #0f172a; line-height: 1.35; letter-spacing: -0.01em;">
                                {{ $announcement['title'] }}
                            </h3>
                            <p style="margin: 0.5rem 0 0 0; font-size: 15.5px; font-weight: 600; color: #334155; line-height: 1.6;">
                                {{ $announcement['summary'] }}
                            </p>
                        </div>
                    </div>

                    <!-- Full Details Panel -->
                    @if(!empty($announcement['details']) && $announcement['details'] !== $announcement['summary'])
                        <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 1.1rem 1.35rem; margin-top: 0.25rem;">
                            <div style="font-size: 12.5px; font-weight: 850; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.4rem;">
                                <i data-lucide="info" style="width: 15px; height: 15px; color: #059669;"></i>
                                <span>Notice Details & Guidelines</span>
                            </div>
                            <div style="font-size: 15px; font-weight: 500; color: #1e293b; line-height: 1.65;">
                                {{ $announcement['details'] }}
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Empty Search Result Fallback -->
        <div x-cloak
             x-show="searchQuery.length > 0 && Array.from(document.querySelectorAll('.announcement-card')).every(el => el.style.display === 'none')"
             style="background: #ffffff; border: 2px dashed #cbd5e1; border-radius: 20px; padding: 3.5rem 2rem; text-align: center;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: #ecfdf5; border: 1.5px solid #a7f3d0; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                <i data-lucide="search-x" style="width: 28px; height: 28px; color: #059669;"></i>
            </div>
            <h3 style="font-size: 18px; font-weight: 850; color: #0f172a; margin: 0 0 0.5rem;">No Matching Announcements</h3>
            <p style="font-size: 14.5px; font-weight: 600; color: #64748b; margin: 0 auto; max-width: 450px;">
                No notices matched your search for "<span x-text="searchQuery" style="color: #0f172a; font-weight: 800;"></span>". Try using another keyword or selecting "All Notices".
            </p>
            <button type="button" @click="searchQuery = ''; activeCategory = 'all'" 
                    style="margin-top: 1rem; background: #047857; color: #ffffff; border: none; padding: 0.5rem 1.25rem; border-radius: 10px; font-size: 14px; font-weight: 800; cursor: pointer;">
                Reset Filter
            </button>
        </div>
    @else
        <!-- Full Empty State -->
        <div style="background: #ffffff; border: 2px dashed #cbd5e1; border-radius: 20px; padding: 4rem 2rem; text-align: center;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: #ecfdf5; border: 1.5px solid #a7f3d0; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                <i data-lucide="bell-off" style="width: 30px; height: 30px; color: #059669;"></i>
            </div>
            <h3 style="font-size: 20px; font-weight: 900; color: #0f172a; margin: 0 0 0.5rem;">No Announcements at This Time</h3>
            <p style="font-size: 15px; font-weight: 600; color: #64748b; margin: 0 auto; max-width: 450px;">
                Check back later for new school notices, circulars, and academic updates.
            </p>
        </div>
    @endif
</div>

<style>
    .announcement-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px -2px rgba(15, 23, 42, 0.08) !important;
        border-color: #94a3b8 !important;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
</x-student-layout>
