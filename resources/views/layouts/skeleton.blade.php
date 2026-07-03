<div class="student-shell">
    <!-- Skeleton Sidebar (visible on desktop) -->
    <aside class="student-sidebar skeleton-sidebar-only" style="background: #ffffff; border-right: 1px solid var(--s-border);">
        <div class="student-sidebar-top">
            <!-- Brand -->
            <div class="student-sidebar-brand">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" style="width: 32px; height: 32px; filter: grayscale(1); opacity: 0.15; flex-shrink: 0;">
                <div style="display: flex; flex-direction: column; gap: 4px; flex: 1;">
                    <div class="skeleton-shimmer" style="width: 50px; height: 14px; border-radius: 4px;"></div>
                    <div class="skeleton-shimmer" style="width: 80px; height: 9px; border-radius: 3px;"></div>
                </div>
            </div>
            
            <!-- Nav Items -->
            <nav class="student-sidebar-nav" style="margin-top: 12px;">
                <div class="student-sidebar-section">Menu</div>
                
                <div class="student-nav-item disabled" style="gap: 12px; padding: 10px 12px; margin-bottom: 2px;">
                    <div class="skeleton-shimmer" style="width: 16px; height: 16px; border-radius: 4px; flex-shrink: 0;"></div>
                    <div class="skeleton-shimmer" style="width: 80px; height: 12px; border-radius: 3px;"></div>
                </div>
                
                <div class="student-nav-item disabled" style="gap: 12px; padding: 10px 12px; margin-bottom: 2px;">
                    <div class="skeleton-shimmer" style="width: 16px; height: 16px; border-radius: 4px; flex-shrink: 0;"></div>
                    <div class="skeleton-shimmer" style="width: 95px; height: 12px; border-radius: 3px;"></div>
                </div>
                
                <div class="student-sidebar-section" style="margin-top: 0.5rem;">Academic</div>
                
                @for($i = 0; $i < 4; $i++)
                    <div class="student-nav-item disabled" style="gap: 12px; padding: 10px 12px; margin-bottom: 2px;">
                        <div class="skeleton-shimmer" style="width: 16px; height: 16px; border-radius: 4px; flex-shrink: 0;"></div>
                        <div class="skeleton-shimmer" style="width: 90px; height: 12px; border-radius: 3px;"></div>
                    </div>
                @endfor
                
                <div class="student-sidebar-section" style="margin-top: 0.5rem;">Finance</div>
                
                <div class="student-nav-item disabled" style="gap: 12px; padding: 10px 12px; margin-bottom: 2px;">
                    <div class="skeleton-shimmer" style="width: 16px; height: 16px; border-radius: 4px; flex-shrink: 0;"></div>
                    <div class="skeleton-shimmer" style="width: 120px; height: 12px; border-radius: 3px;"></div>
                </div>
            </nav>
        </div>
        
        <!-- Profile bottom -->
        <div class="student-sidebar-footer" style="padding: 12px 18px 18px;">
            <div style="display: flex; align-items: center; gap: 0.75rem; width: 100%; padding: 0.8125rem 1rem; border-radius: 12px; border: 1px solid #e8eaf0; height: 46px; box-sizing: border-box;">
                <div class="skeleton-shimmer" style="width: 16px; height: 16px; border-radius: 4px; flex-shrink: 0;"></div>
                <div class="skeleton-shimmer" style="width: 60px; height: 12px; border-radius: 3px;"></div>
            </div>
        </div>
    </aside>
    
    <!-- Skeleton Main Content -->
    <div class="student-main">
        <div class="student-container">
        <!-- Topbar Skeleton -->
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 24px; margin-bottom: 1.5rem; padding: 1rem 1.5rem; background: white; border-radius: 16px; border: 1px solid #e8eaf0; box-shadow: 0 1px 6px rgba(0,0,0,0.04); height: 72px; box-sizing: border-box; width: 100%;">
            <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                <div class="skeleton-shimmer" style="width: 120px; height: 16px; border-radius: 4px;"></div>
                <div class="skeleton-shimmer" style="width: 180px; height: 10px; border-radius: 3px;"></div>
            </div>
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
                <div class="skeleton-shimmer" style="width: 38px; height: 38px; border-radius: 50%;"></div>
                <div class="skeleton-shimmer" style="width: 110px; height: 32px; border-radius: 999px;"></div>
            </div>
        </div>
        
        <!-- Two Col Grid Skeleton -->
        <div class="s-two-col-grid" style="width: 100%;">
            <!-- Left Column -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem; width: 100%;">
                <!-- Hero Banner Skeleton -->
                <div class="skeleton-shimmer" style="width: 100%; height: 180px; border-radius: 24px;"></div>
                
                <!-- Stat Cards Grid Skeleton -->
                <div class="s-stats-grid-3">
                    @for($i = 0; $i < 3; $i++)
                        <div class="s-stat-card" style="display: flex; align-items: center; gap: 16px; box-shadow: none !important; border: 1px solid #e8eaf0 !important;">
                            <div class="skeleton-shimmer" style="width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;"></div>
                            <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                                <div class="skeleton-shimmer" style="width: 90px; height: 10px; border-radius: 3px;"></div>
                                <div class="skeleton-shimmer" style="width: 120px; height: 24px; border-radius: 6px;"></div>
                            </div>
                        </div>
                    @endfor
                </div>
                
                <!-- Schedule Table Card Skeleton -->
                <div style="display: flex; flex-direction: column; gap: 0.85rem; width: 100%;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <div class="skeleton-shimmer" style="width: 180px; height: 20px; border-radius: 6px;"></div>
                            <div class="skeleton-shimmer" style="width: 120px; height: 10px; border-radius: 3px;"></div>
                        </div>
                        <div class="skeleton-shimmer" style="width: 140px; height: 36px; border-radius: 12px;"></div>
                    </div>
                    
                    <div class="s-table-card" style="border: 1px solid #e8eaf0 !important; box-shadow: none !important; width: 100%;">
                        <div class="s-table-header" style="border-bottom: 1px solid #e8eaf0 !important; background: #f8fafc; height: 45px; display: flex; align-items: center; padding: 0 24px;">
                            <div class="skeleton-shimmer" style="width: 150px; height: 14px; border-radius: 4px;"></div>
                        </div>
                        <div style="padding: 12px 24px; display: flex; flex-direction: column; gap: 20px; width: 100%;">
                            @for($i = 0; $i < 3; $i++)
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; width: 100%;">
                                    <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                                        <div class="skeleton-shimmer" style="width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;"></div>
                                        <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                                            <div class="skeleton-shimmer" style="width: 60%; height: 14px; border-radius: 4px;"></div>
                                            <div class="skeleton-shimmer" style="width: 40%; height: 10px; border-radius: 3px;"></div>
                                        </div>
                                    </div>
                                    <div class="skeleton-shimmer" style="width: 100px; height: 32px; border-radius: 10px; flex-shrink: 0;"></div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem; width: 100%;">
                <!-- Announcement Card Skeleton -->
                <div class="s-quick-actions-card" style="border: 1px solid #e8eaf0 !important; box-shadow: none !important; padding: 24px; width: 100%;">
                    <div style="margin-bottom: 20px;">
                        <div class="skeleton-shimmer" style="width: 130px; height: 18px; border-radius: 4px;"></div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 16px; width: 100%;">
                        @for($i = 0; $i < 3; $i++)
                            <div style="display: flex; flex-direction: column; gap: 8px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; width: 100%;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; width: 100%;">
                                    <div class="skeleton-shimmer" style="width: 80px; height: 16px; border-radius: 6px;"></div>
                                    <div class="skeleton-shimmer" style="width: 60px; height: 10px; border-radius: 3px;"></div>
                                </div>
                                <div class="skeleton-shimmer" style="width: 100%; height: 14px; border-radius: 4px;"></div>
                                <div class="skeleton-shimmer" style="width: 85%; height: 14px; border-radius: 4px;"></div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>
