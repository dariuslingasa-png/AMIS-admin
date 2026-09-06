@once
<style>
    /* Main Timetable Switcher styling */
    .sched-tab-btn {
        border: none !important;
        border-radius: 8px !important;
        padding: 0.45rem 1rem !important;
        font-size: 13.5px !important;
        font-weight: 700 !important;
        line-height: 20px !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.4rem !important;
        background: transparent !important;
        color: #64748b !important;
    }
    .sched-tab-btn.active {
        background: white !important;
        color: #047857 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06) !important;
    }

    /* Desktop Calendar Grid UI */
    .calendar-wrapper {
        width: 100%;
        overflow-x: auto;
    }
    
    /* Calendar Class Card */
    .sched-grid-card {
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    .sched-grid-card:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 16px -2px rgba(15, 23, 42, 0.08) !important;
    }
    
    .sched-special-strip {
        transition: all 0.2s ease !important;
    }
    .sched-special-strip:hover {
        filter: brightness(0.97);
    }
    
    .calendar-class-card {
        flex: 1;
        display: flex;
        flex-direction: row;
        gap: 0.65rem;
        align-items: center;
        background: white;
        border: 1.5px solid #e2e8f0;
        border-radius: 16px;
        padding: 0.65rem;
        min-height: 85px;
        position: relative;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .calendar-class-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.04);
    }

    /* Mobile Timeline View */
    .mobile-timeline {
        display: none;
    }
    .mobile-timeline-item {
        display: grid;
        grid-template-columns: 5.5rem minmax(0, 1fr);
        gap: 1rem;
        align-items: stretch;
    }
    .mobile-time {
        display: flex;
        flex-direction: column;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 850;
        color: #64748b;
        text-align: right;
        padding-right: 0.5rem;
        border-right: 2px solid #e2e8f0;
        position: relative;
    }
    .mobile-time::after {
        content: '';
        position: absolute;
        right: -5px;
        top: calc(50% - 4px);
        width: 8px;
        height: 8px;
        background: #cbd5e1;
        border-radius: 50%;
    }
    .mobile-time.live-time::after {
        background: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
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
        padding: 0.65rem 1.15rem !important;
        font-size: 15px !important;
        font-weight: 600 !important;
        line-height: 22px !important;
        cursor: pointer !important;
        transition: all 0.15s !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        background: transparent !important;
        color: #64748b !important;
    }
    .day-tab-btn.active {
        background: #059669 !important;
        color: white !important;
        box-shadow: 0 4px 10px rgba(5, 150, 105, 0.2) !important;
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
