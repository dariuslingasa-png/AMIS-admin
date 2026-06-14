@php
    $workspaceSections = [
        [
            'active' => request()->routeIs('admin.dashboard'),
            'icon' => 'layout-dashboard', 'iconClass' => 'text-slate-600', 'headerClass' => 'text-slate-700', 'activeClass' => 'sidebar-link-active-slate', 'title' => 'Dashboard',
            'links' => [
                ['Overview', 'layout-dashboard', route('admin.dashboard'), true],
                ['App Modules', 'layout-grid', route('admin.dashboard').'#modules', false],
                ['Quick Actions', 'zap', route('admin.dashboard').'#quick-actions', false],
            ],
        ],
        [
            'active' => (request()->routeIs('admin.applications.*') || request()->routeIs('admin.applicants.*') || request()->routeIs('admin.enrollment.index') || request()->routeIs('admin.enrollment.masters-list')) && request('workspace') !== 'reports',
            'icon' => 'clipboard-check', 'iconClass' => 'text-emerald-600', 'headerClass' => 'text-emerald-700', 'activeClass' => 'sidebar-link-active-emerald', 'title' => 'Applications',
            'links' => [
                ['Dashboard', 'layout-dashboard', route('admin.applications.dashboard'), request()->routeIs('admin.applications.dashboard')],
                ['Enrollment Applications', 'file-text', route('admin.applications.enrollment'), request()->routeIs('admin.applications.enrollment') || request()->routeIs('admin.applicants.index')],
                ['Applicant Review', 'file-search', route('admin.applications.review'), request()->routeIs('admin.applications.review') || request()->routeIs('admin.applicants.show')],
                ['Requirements', 'list-checks', route('admin.applications.requirements'), request()->routeIs('admin.applications.requirements')],
                ['Approval Workflow', 'shield-check', route('admin.applications.approval'), request()->routeIs('admin.applications.approval')],
                ['Enrollee Masters List', 'list', route('admin.enrollment.masters-list'), request()->routeIs('admin.enrollment.masters-list') && request('workspace') !== 'reports'],
            ],
        ],
        [
            'active' => request()->routeIs('admin.students.*'),
            'icon' => 'users', 'iconClass' => 'text-violet-600', 'headerClass' => 'text-violet-700', 'activeClass' => 'sidebar-link-active-violet', 'title' => 'Students',
            'links' => [
                ['Dashboard', 'layout-dashboard', route('admin.students.dashboard'), request()->routeIs('admin.students.dashboard')],
                ['Student Records', 'user-check', route('admin.students.index'), request()->routeIs('admin.students.index') || request()->routeIs('admin.students.show')],
                ['Account Onboarding', 'user-cog', route('admin.students.accounts'), request()->routeIs('admin.students.accounts')],
                ['Enrollment History', 'history', route('admin.students.history'), request()->routeIs('admin.students.history')],
            ],
        ],
        [
            'active' => request()->routeIs('admin.ms-teams.*') || request()->routeIs('admin.academic.*'),
            'icon' => 'book-open-check', 'iconClass' => 'text-sky-600', 'headerClass' => 'text-sky-700', 'activeClass' => 'sidebar-link-active-sky', 'title' => 'Academic',
            'links' => [
                ['Dashboard', 'layout-dashboard', route('admin.academic.dashboard'), request()->routeIs('admin.academic.dashboard') || request()->routeIs('admin.academic.dashboard.index')],
                ['Curriculum', 'map', route('admin.academic.curriculum'), request()->routeIs('admin.academic.curriculum') || request()->routeIs('admin.academic.grade-levels') || request()->routeIs('admin.academic.school-years') || request()->routeIs('admin.academic.calendar')],
                ['Class Management', 'users-round', route('admin.academic.schedules'), request()->routeIs('admin.academic.schedules') && request('tab') !== 'schedule'],
                ['Class Schedule', 'calendar-days', route('admin.academic.schedules', ['tab' => 'schedule']), request()->routeIs('admin.academic.schedules') && request('tab') === 'schedule'],
                ['Class Workspace', 'graduation-cap', route('admin.ms-teams.index'), request()->routeIs('admin.ms-teams.*')],
                ['Class Advisory', 'contact-2', route('admin.academic.class-advisory'), request()->routeIs('admin.academic.class-advisory')],
                ['Teachers', 'contact-2', route('admin.academic.teachers'), request()->routeIs('admin.academic.teachers')],
                ['Operations', 'activity', route('admin.academic.operations'), request()->routeIs('admin.academic.operations')],
            ],
        ],
        [
            'active' => request()->routeIs('admin.ebook.*'),
            'icon' => 'book-open', 'iconClass' => 'text-emerald-600', 'headerClass' => 'text-emerald-700', 'activeClass' => 'sidebar-link-active-emerald', 'title' => 'eBook',
            'links' => [
                ['Library Dashboard', 'layout-dashboard', route('admin.ebook.index'), request()->routeIs('admin.ebook.index')],
                ['Upload eBook', 'upload-cloud', route('admin.ebook.create'), request()->routeIs('admin.ebook.create')],
            ],
        ],
        [
            'active' => request()->routeIs('admin.finance.*') || request()->routeIs('admin.soa.*') || request()->routeIs('admin.payments.*'),
            'icon' => 'wallet', 'iconClass' => 'text-amber-600', 'headerClass' => 'text-amber-700', 'activeClass' => 'sidebar-link-active-amber', 'title' => 'Finance Management',
            'links' => [
                ['Dashboard', 'layout-dashboard', route('admin.finance.dashboard'), request()->routeIs('admin.finance.dashboard')],
                ['Enrollment Payment Approval', 'credit-card', route('admin.payments.index'), request()->routeIs('admin.payments.*')],
                ['Finance Masters List', 'list-checks', route('admin.finance.masters-list'), request()->routeIs('admin.finance.masters-list')],
                ['Fee & Discount', 'receipt', route('admin.finance.fees'), request()->routeIs('admin.finance.fees')],
            ],
        ],
        [
            'active' => request()->routeIs('admin.enrollment.analytics'),
            'icon' => 'chart-no-axes-combined', 'iconClass' => 'text-lime-600', 'headerClass' => 'text-lime-700', 'activeClass' => 'sidebar-link-active-lime', 'title' => 'Analytics',
            'links' => [
                ['Enrollment Analytics', 'chart-no-axes-combined', route('admin.enrollment.analytics'), true],
                ['Performance Reports', 'activity', route('admin.enrollment.analytics'), false],
                ['Charts', 'bar-chart-3', route('admin.enrollment.analytics'), false],
                ['Insights', 'sparkles', route('admin.enrollment.analytics'), false],
            ],
        ],
        [
            'active' => request()->routeIs('admin.enrollment.reports') || (request()->routeIs('admin.enrollment.masters-list') && request('workspace') === 'reports'),
            'icon' => 'file-down', 'iconClass' => 'text-slate-600', 'headerClass' => 'text-slate-700', 'activeClass' => 'sidebar-link-active-slate', 'title' => 'Reports',
            'links' => [
                ['Enrollee Masters List', 'list', route('admin.enrollment.masters-list', ['workspace' => 'reports']), request()->routeIs('admin.enrollment.masters-list') && request('workspace') === 'reports'],
                ['Export', 'download', route('admin.enrollment.reports'), request()->routeIs('admin.enrollment.reports')],
                ['PDF Reports', 'file-text', route('admin.enrollment.reports'), false],
                ['Excel Reports', 'sheet', route('admin.enrollment.reports'), false],
                ['Registrar / Finance', 'briefcase-business', route('admin.enrollment.reports'), false],
            ],
        ],
        [
            'active' => request()->routeIs('admin.admins.*'),
            'icon' => 'shield-check', 'iconClass' => 'text-violet-600', 'headerClass' => 'text-violet-700', 'activeClass' => 'sidebar-link-active-violet', 'title' => 'Security',
            'links' => [
                ['Admin Accounts', 'user-cog', route('admin.admins.index'), request()->routeIs('admin.admins.*')],
                ['Roles & Permissions', 'key-round', route('admin.admins.index'), false],
                ['Audit Logs', 'logs', route('admin.admins.audit-logs'), request()->routeIs('admin.admins.audit-logs')],
                ['Login Activity', 'activity', route('admin.admins.index'), false],
                ['Backup', 'database-backup', route('admin.admins.backups'), request()->routeIs('admin.admins.backups')],
            ],
        ],
        [
            'active' => request()->routeIs('admin.settings.*') || request()->routeIs('admin.ms-sync.*'),
            'icon' => 'settings', 'iconClass' => 'text-lime-600', 'headerClass' => 'text-lime-700', 'activeClass' => 'sidebar-link-active-lime', 'title' => 'Settings',
            'links' => [
                ['General Settings', 'sliders', route('admin.settings.discounts'), request()->routeIs('admin.settings.*')],
                ['School Profile', 'school', route('admin.settings.discounts'), false],
                ['Email / Notifications', 'mail', route('admin.settings.discounts'), false],
                ['MS365 Sync', 'refresh-cw', route('admin.ms-sync.index'), request()->routeIs('admin.ms-sync.*')],
                ['Integrations', 'plug', route('admin.ms-sync.index'), false],
                ['Branding', 'palette', route('admin.settings.discounts'), false],
                ['System Preferences', 'settings-2', route('admin.settings.discounts'), false],
            ],
        ],
    ];

    $workspaceSections = array_filter($workspaceSections, function($section) {
        return !($section['hidden'] ?? false);
    });

    $activeSection = collect($workspaceSections)->firstWhere('active', true) ?? $workspaceSections[0];
@endphp

<aside id="default-sidebar"
       class="admin-sidebar fixed left-0 z-40 w-64 border-r border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
       aria-label="Sidebar">
    <div class="flex h-full flex-col px-3 py-4">
        @unless (request()->routeIs('admin.dashboard'))
            <a href="{{ route('admin.dashboard') }}" class="module-dashboard-link mb-3">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                <span>Main Dashboard</span>
            </a>
        @endunless

        <div class="sidebar-section-container">
            <div class="sidebar-section-header">
                <i data-lucide="{{ $activeSection['icon'] }}" class="sidebar-section-header-icon {{ $activeSection['iconClass'] }}"></i>
                <span class="{{ $activeSection['headerClass'] }} font-extrabold text-xs tracking-wider uppercase">{{ $activeSection['title'] }} Workspace</span>
            </div>
            <div class="space-y-1">
                @foreach ($activeSection['links'] as [$label, $icon, $href, $active])
                    <a href="{{ $href }}" class="sidebar-link{{ $active ? ' '.$activeSection['activeClass'] : '' }}">
                        <i data-lucide="{{ $icon }}"></i>
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="sidebar-profile-card mt-auto">
            <div class="sidebar-profile-info">
                <span class="sidebar-profile-label">Signed in as</span>
                <span class="sidebar-profile-name" title="{{ Auth::user()->name ?? 'Administrator' }}">
                    {{ Auth::user()->name ?? 'Administrator' }}
                </span>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="sidebar-logout-btn">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
    </div>
</aside>
