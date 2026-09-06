@once
<style>
    /* Main Timetable Switcher styling (Grandparent & High School Friendly) */
    .sched-tab-btn {
        border: none !important;
        border-radius: 10px !important;
        padding: 0.6rem 1.25rem !important;
        font-size: 15px !important;
        font-weight: 800 !important;
        line-height: 22px !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        background: transparent !important;
        color: #475569 !important;
    }
    .sched-tab-btn.active {
        background: white !important;
        color: #047857 !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04) !important;
    }

    /* Desktop Calendar Grid UI */
    .calendar-wrapper {
        width: 100%;
        overflow-x: auto;
    }
    
    /* Calendar Class Card (High Legibility & Smooth Elevation) */
    .sched-grid-card {
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    .sched-grid-card:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 20px -2px rgba(15, 23, 42, 0.1) !important;
    }
    
    .sched-special-strip {
        transition: all 0.2s ease !important;
    }
    .sched-special-strip:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 6px 16px -2px rgba(15, 23, 42, 0.08) !important;
    }
    
    .calendar-class-card {
        flex: 1;
        display: flex;
        flex-direction: row;
        gap: 0.75rem;
        align-items: center;
        background: white;
        border: 1.5px solid #cbd5e1;
        border-radius: 16px;
        padding: 0.85rem;
        min-height: 85px;
        position: relative;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.03);
    }
    .calendar-class-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
    }

    /* Mobile Timeline View */
    .mobile-timeline {
        display: none;
    }
    .mobile-timeline-item {
        display: grid;
        grid-template-columns: 6rem minmax(0, 1fr);
        gap: 1.15rem;
        align-items: stretch;
    }
    .mobile-time {
        display: flex;
        flex-direction: column;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: 850;
        color: #475569;
        text-align: right;
        padding-right: 0.65rem;
        border-right: 2.5px solid #cbd5e1;
        position: relative;
    }
    .mobile-time::after {
        content: '';
        position: absolute;
        right: -6px;
        top: calc(50% - 5px);
        width: 10px;
        height: 10px;
        background: #94a3b8;
        border-radius: 50%;
    }
    .mobile-time.live-time::after {
        background: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.25);
    }
    
    /* Responsive Switches */
    @media (max-width: 1023px) {
        .calendar-wrapper {
            display: none;
        }
        .mobile-timeline {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
    }
    
    /* Style tab switcher buttons */
    .day-tab-btn {
        border: none !important;
        border-radius: 12px !important;
        padding: 0.75rem 1.25rem !important;
        font-size: 15.5px !important;
        font-weight: 800 !important;
        line-height: 22px !important;
        cursor: pointer !important;
        transition: all 0.15s !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.4rem !important;
        background: transparent !important;
        color: #475569 !important;
    }
    .day-tab-btn.active {
        background: #059669 !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25) !important;
    }
    
    /* Fullscreen Calendar styling */
    .calendar-wrapper.is-fullscreen {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        max-width: none !important;
        max-height: none !important;
        z-index: 9999 !important;
        background: #ffffff !important;
        border-radius: 0 !important;
        padding: 1.5rem !important;
        overflow: auto !important;
        box-shadow: none !important;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
@endonce
