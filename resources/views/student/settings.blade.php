<x-student-layout title="Settings">

@php
    $user = Auth::user();
    $microsoftConnected = filled($student?->ms_user_id) || filled($student?->ms_email) || filled($student?->school_email) || filled($user->ms_id);
    $schoolEmail = $student?->ms_email ?: ($student?->school_email ?: ($user->email ?: 'Not assigned'));
    $googleConfigured = filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    $googleLinked = filled($user->google_id);

    $connectionRows = [
        ['Microsoft 365 Account', $microsoftConnected ? 'Connected via Azure AD' : 'Not connected', $microsoftConnected ? 'check-circle-2' : 'alert-circle', $microsoftConnected ? 'emerald' : 'amber'],
        ['School Email UPN', $schoolEmail, 'mail', filled($schoolEmail) ? 'emerald' : 'slate'],
        ['Google Account', $googleLinked ? ($user->google_email ?: 'Linked') : ($googleConfigured ? 'Ready to bind' : 'Optional / Not configured'), 'chrome', $googleLinked ? 'emerald' : 'slate'],
    ];
@endphp

<div class="space-y-6">
    
    <!-- 1. Settings Header Card -->
    <div class="portal-card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-emerald-700 font-bold text-xs uppercase tracking-wider">
                <i data-lucide="settings" class="h-4 w-4"></i>
                <span>Account & Security</span>
            </div>
            <h2 class="mt-1 font-heading text-2xl font-black text-slate-900">Account Connections</h2>
            <p class="text-xs font-medium text-slate-500">
                Manage your single sign-on access, verified email, and security settings.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="portal-badge portal-badge-emerald">SSO Active</span>
        </div>
    </div>

    <!-- 2. Connection Overview Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        <!-- School Email Card -->
        <div class="portal-card p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-100">
                    <i data-lucide="mail" class="h-5 w-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">School Email</p>
                    <p class="font-bold text-xs text-slate-900 truncate mt-0.5" title="{{ $schoolEmail }}">{{ $schoolEmail }}</p>
                </div>
            </div>
        </div>

        <!-- Microsoft Connection Card -->
        <div class="portal-card p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center shrink-0 border border-indigo-100">
                    <i data-lucide="shield-check" class="h-5 w-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Microsoft SSO</p>
                    <p class="font-bold text-xs text-slate-900 truncate mt-0.5">{{ $microsoftConnected ? 'Connected (Active)' : 'Pending' }}</p>
                </div>
            </div>
        </div>

        <!-- Portal Role Card -->
        <div class="portal-card p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center shrink-0 border border-teal-100">
                    <i data-lucide="user-check" class="h-5 w-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Portal Access</p>
                    <p class="font-bold text-xs text-slate-900 truncate mt-0.5">Grade 1 Student</p>
                </div>
            </div>
        </div>

    </div>

    <!-- 3. Connection Details Table -->
    <div class="portal-card p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="font-heading text-base font-extrabold text-slate-900">Integration Details</h3>
                <p class="text-xs text-slate-500 mt-0.5">Linked authentication providers and identity attributes.</p>
            </div>
        </div>

        <div class="portal-table-container mt-4">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th class="portal-th">Provider / Field</th>
                        <th class="portal-th">Account Identifier</th>
                        <th class="portal-th">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($connectionRows as [$label, $value, $icon, $tone])
                        <tr class="portal-tr">
                            <td class="portal-td">
                                <span class="flex items-center gap-2 font-bold text-slate-900">
                                    <i data-lucide="{{ $icon }}" class="h-4 w-4 text-emerald-600"></i>
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="portal-td font-semibold text-slate-600 max-w-xs truncate" title="{{ $value }}">
                                {{ $value }}
                            </td>
                            <td class="portal-td">
                                <span class="portal-badge {{ $tone === 'emerald' ? 'portal-badge-emerald' : ($tone === 'amber' ? 'portal-badge-amber' : 'portal-badge-slate') }}">
                                    {{ $tone === 'emerald' ? 'Active' : 'Optional' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

</x-student-layout>
