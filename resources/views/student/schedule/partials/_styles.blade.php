@once
<style>
    /* Main Timetable Switcher styling */
    .sched-tab-btn {
        border: none !important;
        border-radius: 10px !important;
        padding: 0.5rem 1.25rem !important;
        font-size: 15px !important;
        font-weight: 600 !important;
        line-height: 22px !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        background: transparent !important;
        color: #64748b !important;
    }
    .sched-tab-btn.active {
        background: white !important;
        color: #0d9488 !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.04) !important;
    }

    /* Desktop Calendar Grid UI */
    .calendar-wrapper {
        width: 100%;
        overflow-x: auto;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 28px;
        padding: 1.5rem;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    }
    .calendar-grid {
        display: grid;
        grid-template-columns: 8rem repeat(5, minmax(180px, 1fr));
        gap: 0.65rem;
        min-width: 1000px;
    }
    .calendar-grid-header {
        font-size: 15px !important;
        font-weight: 800 !important;
        line-height: 1.2 !important;
        text-transform: uppercase;
        color: white;
        background: #0d9488;
        padding: 0.85rem 0.5rem;
        border-radius: 12px;
        letter-spacing: 0.05em;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        min-height: 52px !important;
    }
    .calendar-time-header {
        background: #115e59;
    }
    .calendar-grid-row {
        display: contents;
    }
    .calendar-time-block {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 0.5rem;
        font-size: 13px !important;
        font-weight: 500 !important;
        line-height: 18px !important;
        color: #1e293b;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        min-height: 85px;
    }
    .calendar-cell {
        display: flex;
        flex-direction: column;
    }
    
    /* Calendar Class Card */
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
    
    /* Live Glow Alert */
    .class-live {
        animation: pulse-border 2s infinite alternate;
        position: relative;
    }
    .class-live::before {
        content: '';
        position: absolute;
        top: 6px;
        right: 6px;
        width: 8px;
        height: 8px;
        background-color: #ef4444;
        border-radius: 50%;
        animation: pulse-live 1.8s infinite;
        z-index: 10;
    }
    
    /* Completed State */
    .class-completed {
        opacity: 0.5;
        background: #f1f5f9 !important;
        border-color: #cbd5e1 !important;
        color: #64748b !important;
        box-shadow: none !important;
    }
    .class-completed .icon-small {
        background: #cbd5e1 !important;
        color: #64748b !important;
        border-color: #94a3b8 !important;
    }
    
    /* Special/Break Slots (Assembly, Recess, Homeroom, Transition) */
    .class-special {
        background: #f1f5f9 !important;
        border: 1.5px dashed #cbd5e1 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 16px !important;
        box-shadow: none !important;
        min-height: 85px !important;
    }
    .class-special:hover {
        transform: none !important;
        box-shadow: none !important;
    }
    .class-special-title {
        font-size: 0.85rem !important;
        font-weight: 800;
        color: #64748b;
        margin: 0;
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
    
    /* Animations */
    @keyframes pulse-live {
        0% { transform: scale(0.9); opacity: 1; }
        50% { transform: scale(1.3); opacity: 0.4; }
        100% { transform: scale(0.9); opacity: 1; }
    }
    @keyframes pulse-border {
        from { box-shadow: 0 0 4px rgba(16,185,129,0.1); }
        to { box-shadow: 0 0 12px rgba(16,185,129,0.3); }
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
        background: #0d9488 !important;
        color: white !important;
        box-shadow: 0 4px 10px rgba(13, 148, 136, 0.15) !important;
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
        padding: 2rem !important;
        overflow: auto !important;
        box-shadow: none !important;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
@endonce
