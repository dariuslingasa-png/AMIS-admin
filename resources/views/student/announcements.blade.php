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
            'text' => '#047857',
        ],
        'sky' => [
            'accent' => '#0284c7',
            'bg' => '#f0f9ff',
            'text' => '#0369a1',
        ],
        'amber' => [
            'accent' => '#d97706',
            'bg' => '#fffbeb',
            'text' => '#b45309',
        ],
        'indigo' => [
            'accent' => '#4f46e5',
            'bg' => '#eef2ff',
            'text' => '#4338ca',
        ],
    ];
@endphp

<style>
    /* Filter Segmented Control Buttons */
    .ann-filter-group {
        display: inline-flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        gap: 4px;
        flex-wrap: wrap;
    }
    .ann-tab-btn {
        border: none !important;
        background: transparent !important;
        padding: 0.5rem 1rem !important;
        border-radius: 8px !important;
        font-size: 13.5px !important;
        font-weight: 700 !important;
        color: #64748b !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.35rem !important;
        white-space: nowrap !important;
    }
    .ann-tab-btn:hover {
        color: #0f172a !important;
    }
    .ann-tab-btn.active {
        background: #ffffff !important;
        color: #047857 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        font-weight: 800 !important;
    }

    /* Clean Card Hover */
    .announcement-card {
        transition: all 0.2s ease !important;
    }
    .announcement-card:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 20px -2px rgba(15, 23, 42, 0.06) !important;
        border-color: #cbd5e1 !important;
    }
</style>

<div class="space-y-5" x-data="{ activeCategory: 'all', searchQuery: '' }">

    <!-- 1. Hero Banner (Clean, Minimal) -->
    <div style="background: linear-gradient(135deg, #064e3b 0%, #047857 55%, #0d9488 100%); color: #ffffff; padding: 1.25rem 1.75rem; border-radius: 18px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.06); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.85rem;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(6px); border: 1.5px solid rgba(255,255,255,0.3); flex-shrink: 0;">
                <i data-lucide="megaphone" style="width: 22px; height: 22px; color: #ffffff;"></i>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 1.25rem; font-weight: 900; letter-spacing: 0.01em; text-transform: uppercase; color: #ffffff; line-height: 1.2;">
                    Official Notice Board
                </h2>
                <p style="margin: 3px 0 0 0; font-size: 14px; color: #e6fffa; font-weight: 600;">
                    Official school circulars and announcements for School Year {{ $student?->school_year ?? '2026-2027' }}.
                </p>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
            <span style="font-size: 13.5px; font-weight: 800; background: rgba(255,255,255,0.2); border: 1.5px solid rgba(255,255,255,0.3); color: #ffffff; padding: 0.35rem 0.85rem; border-radius: 999px;">
                {{ count($announcements) }} Notices
            </span>
            <span style="font-size: 13.5px; font-weight: 800; background: rgba(255,255,255,0.15); border: 1.5px solid rgba(255,255,255,0.25); color: #ffffff; padding: 0.35rem 0.85rem; border-radius: 999px;">
                {{ $student?->grade_level ?? 'All Students' }}
            </span>
        </div>
    </div>

    <!-- 2. Controls Bar (Segmented Filter Buttons + Search) -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 0.85rem 1.25rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
        
        <!-- Filter Buttons Group -->
        <div class="ann-filter-group">
            @foreach($categories as $catKey => $catLabel)
                <button type="button"
                        @click="activeCategory = '{{ $catKey }}'"
                        class="ann-tab-btn"
                        :class="activeCategory === '{{ $catKey }}' ? 'active' : ''">
                    <span>{{ $catLabel }}</span>
                </button>
            @endforeach
        </div>

        <!-- Search Input -->
        <div style="position: relative; min-width: 220px; flex-grow: 1; max-width: 320px;">
            <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: #94a3b8; pointer-events: none;"></i>
            <input type="text" 
                   x-model="searchQuery" 
                   placeholder="Search notices..." 
                   style="width: 100%; height: 38px; padding: 0.4rem 0.85rem 0.4rem 2.25rem; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; font-weight: 600; color: #0f172a; outline: none; background: #f8fafc; transition: all 0.15s ease;"
                   @focus="$el.style.borderColor = '#059669'; $el.style.background = '#ffffff';"
                   @blur="$el.style.borderColor = '#cbd5e1'; $el.style.background = '#f8fafc';">
            <button type="button" 
                    x-show="searchQuery.length > 0" 
                    @click="searchQuery = ''" 
                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: transparent; cursor: pointer; color: #94a3b8;">
                <i data-lucide="x" style="width: 14px; height: 14px;"></i>
            </button>
        </div>
    </div>

    <!-- 3. Clean Notice Cards (No Chip Clutter, No Left Color Stripe) -->
    @if(count($announcements) > 0)
        <div class="space-y-4">
            @foreach ($announcements as $announcement)
                @php
                    $category = $announcement['category'] ?? 'portal';
                    $tone = $toneMap[$announcement['tone']] ?? $toneMap['emerald'];
                    $searchData = strtolower(addslashes($announcement['title'] . ' ' . $announcement['summary']));
                @endphp

                <div x-show="(activeCategory === 'all' || activeCategory === '{{ $category }}') && (!searchQuery || '{{ $searchData }}'.includes(searchQuery.toLowerCase().trim()))"
                     class="announcement-card"
                     style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.35rem 1.5rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03); display: flex; flex-direction: column; gap: 0.85rem;">
                    
                    <!-- Clean Top Line: Category Name on left, Date on right (NO heavy chips) -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                        <span style="font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: {{ $tone['text'] }};">
                            {{ $announcement['type'] }}
                        </span>

                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 13px; font-weight: 600; color: #64748b;">
                            <i data-lucide="calendar" style="width: 14px; height: 14px; color: #94a3b8;"></i>
                            <span>{{ $announcement['date'] }}</span>
                        </span>
                    </div>

                    <!-- Clean Content Body -->
                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                        <div style="width: 44px; height: 44px; border-radius: 10px; background: {{ $tone['bg'] }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: {{ $tone['accent'] }};">
                            <i data-lucide="{{ $announcement['icon'] ?? 'megaphone' }}" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <h3 style="margin: 0; font-size: 17.5px; font-weight: 850; color: #0f172a; line-height: 1.35; letter-spacing: -0.01em;">
                                {{ $announcement['title'] }}
                            </h3>
                            <p style="margin: 0.45rem 0 0 0; font-size: 15px; font-weight: 500; color: #334155; line-height: 1.6;">
                                {{ $announcement['summary'] }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Empty Search Fallback -->
        <div x-cloak
             x-show="searchQuery.length > 0 && Array.from(document.querySelectorAll('.announcement-card')).every(el => el.style.display === 'none')"
             style="background: #ffffff; border: 1.5px dashed #cbd5e1; border-radius: 16px; padding: 3rem 2rem; text-align: center;">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: #ecfdf5; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                <i data-lucide="search-x" style="width: 24px; height: 24px; color: #059669;"></i>
            </div>
            <h3 style="font-size: 17px; font-weight: 800; color: #0f172a; margin: 0 0 0.35rem;">No Matching Announcements</h3>
            <p style="font-size: 14px; color: #64748b; margin: 0 auto; max-width: 420px;">
                No notices matched your search for "<span x-text="searchQuery" style="color: #0f172a; font-weight: 700;"></span>".
            </p>
            <button type="button" @click="searchQuery = ''; activeCategory = 'all'" 
                    style="margin-top: 0.85rem; background: #047857; color: #ffffff; border: none; padding: 0.45rem 1.15rem; border-radius: 8px; font-size: 13.5px; font-weight: 750; cursor: pointer;">
                Clear Search
            </button>
        </div>
    @else
        <!-- Full Empty State -->
        <div style="background: #ffffff; border: 1.5px dashed #cbd5e1; border-radius: 16px; padding: 3.5rem 2rem; text-align: center;">
            <div style="width: 50px; height: 50px; border-radius: 50%; background: #ecfdf5; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                <i data-lucide="bell-off" style="width: 26px; height: 26px; color: #059669;"></i>
            </div>
            <h3 style="font-size: 18px; font-weight: 850; color: #0f172a; margin: 0 0 0.35rem;">No Announcements at This Time</h3>
            <p style="font-size: 14.5px; color: #64748b; margin: 0 auto; max-width: 420px;">
                Check back later for new school notices and updates.
            </p>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
</x-student-layout>
