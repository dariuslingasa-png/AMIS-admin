@extends('student.layout', ['heading' => 'Account Connections'])

@section('content')
@php
    $user = Auth::user();
    $microsoftConnected = filled($student->ms_user_id) || filled($student->ms_email) || filled($student->school_email);
    $schoolEmail = $student->ms_email ?: ($student->school_email ?: 'Not assigned');
    $googleConfigured = filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    $googleLinked = filled($user->google_id);

    $connectionRows = [
        ['Microsoft Status', $microsoftConnected ? 'Connected' : 'Not connected', $microsoftConnected ? 'check-circle-2' : 'alert-circle', $microsoftConnected ? 'emerald' : 'amber'],
        ['School Email', $schoolEmail, 'mail', filled($student->ms_email) || filled($student->school_email) ? 'emerald' : 'slate'],
        ['Google Account', $googleLinked ? ($user->google_email ?: 'Linked') : ($googleConfigured ? 'Not linked yet' : 'Not configured'), 'chrome', $googleLinked ? 'emerald' : ($googleConfigured ? 'amber' : 'slate')],
    ];

    $toneClasses = [
        'emerald' => 'border-emerald-100 bg-emerald-50 text-emerald-700',
        'amber' => 'border-amber-100 bg-amber-50 text-amber-700',
        'slate' => 'border-slate-200 bg-slate-50 text-slate-650',
    ];
@endphp

<div class="space-y-6">
    {{-- Welcome Hero --}}
    <section class="dash-welcome">
        <div class="dash-welcome-body">
            <p class="dash-welcome-greeting">Settings</p>
            <h2 class="dash-welcome-title">Account Connections</h2>
            <p class="dash-welcome-sub">Review your Microsoft 365 access and bind your Google account for faster portal sign-in.</p>
        </div>
        <div class="hidden min-w-[15rem] rounded-2xl border border-white/20 bg-white/10 p-5 lg:block">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-100">Google Account</p>
            <p class="mt-1 text-2xl font-black text-white">{{ $googleLinked ? 'Linked' : 'Not linked' }}</p>
        </div>
    </section>

    {{-- Stats Row --}}
    <section class="dash-stats">
        <article class="dash-stat">
            <span class="dash-stat-icon"><i data-lucide="mail"></i></span>
            <div>
                <p class="dash-stat-label">School Email</p>
                <strong class="dash-stat-value" style="font-size: 14px; word-break: break-all;">{{ $schoolEmail }}</strong>
            </div>
        </article>

        <article class="dash-stat">
            <span class="dash-stat-icon"><i data-lucide="{{ $microsoftConnected ? 'check-circle-2' : 'alert-circle' }}"></i></span>
            <div>
                <p class="dash-stat-label">Microsoft Status</p>
                <strong class="dash-stat-value">{{ $microsoftConnected ? 'Connected' : 'Pending' }}</strong>
            </div>
        </article>

        <article class="dash-stat">
            <span class="dash-stat-icon"><i data-lucide="chrome"></i></span>
            <div>
                <p class="dash-stat-label">Google Account</p>
                <strong class="dash-stat-value" style="font-size: 14px; word-break: break-all;">{{ $googleLinked ? 'Linked' : ($googleConfigured ? 'Ready to bind' : 'Not set') }}</strong>
            </div>
        </article>
    </section>

    <div class="dash-grid">
        {{-- Main Column --}}
        <div class="dash-main-col lg:col-span-8">
            <div class="student-panel">
                <div class="student-panel-header">
                    <div>
                        <h2>Connection Details</h2>
                        <span>Visible student account values from AMIS, Microsoft 365, and Firebase.</span>
                    </div>
                    <span class="student-status-pill {{ $googleLinked ? '' : 'bg-amber-50 text-amber-700 border-amber-100' }}">
                        <i data-lucide="{{ $googleLinked ? 'check-circle-2' : ($googleConfigured ? 'link' : 'settings') }}" class="h-3.5 w-3.5 mr-1"></i>
                        {{ $googleLinked ? 'Google Linked' : ($googleConfigured ? 'Ready to bind' : 'Needs config') }}
                    </span>
                </div>

                <div class="student-table-scroll mt-4">
                    <table>
                        <thead>
                            <tr>
                                <th>Setting</th>
                                <th>Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($connectionRows as [$label, $value, $icon, $tone])
                                <tr>
                                    <td>
                                        <span class="flex items-center gap-2 font-bold text-gray-900">
                                            <i data-lucide="{{ $icon }}" class="h-4 w-4 text-slate-400"></i>
                                            {{ $label }}
                                        </span>
                                    </td>
                                    <td class="max-w-xs truncate" title="{{ $value }}">{{ $value }}</td>
                                    <td>
                                        <span class="student-status-pill {{ $tone === 'emerald' ? '' : ($tone === 'amber' ? 'bg-amber-50 text-amber-700 border-amber-100' : 'bg-gray-50 text-gray-500 border-gray-200') }}">
                                            {{ $tone === 'emerald' ? 'Ready' : ($tone === 'amber' ? 'Pending' : 'Not set') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sidebar Column --}}
        <div class="dash-sidebar-col lg:col-span-4">
            <div class="student-panel">
                <div class="student-panel-header" style="padding:0; margin-bottom: 16px;">
                    <h2>Link Accounts</h2>
                </div>
                <p class="text-gray-500 text-xs mt-1">Bind your Google account to this exact student portal user.</p>

                <div class="mt-5 space-y-3">
                    <div class="rounded-xl border border-gray-150 bg-white p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border bg-emerald-50 text-emerald-700 border-emerald-100">
                                <i data-lucide="chrome" class="h-5 w-5"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-950">Google Account</span>
                                <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $googleLinked ? ($user->google_email ?: 'Linked') : ($googleConfigured ? 'Not linked yet' : 'Google not configured') }}</span>
                            </div>
                        </div>
                        <div class="mt-4">
                            @if($googleLinked)
                                <form method="POST" action="{{ route('student.settings.google.unlink') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="student-outline-btn w-full text-rose-700 hover:bg-rose-50 border-rose-200">
                                        <i data-lucide="unlink" class="h-4 w-4 mr-1"></i>
                                        Unlink Google Account
                                    </button>
                                </form>
                            @elseif($googleConfigured)
                                <a href="{{ route('student.settings.google.redirect') }}" class="student-primary-btn w-full">
                                    <i data-lucide="link" class="h-4 w-4 mr-1"></i>
                                    Bind Google Account
                                </a>
                            @else
                                <button type="button" disabled class="student-outline-btn w-full cursor-not-allowed opacity-50">
                                    Configure Google first
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="p-4 rounded-xl border border-gray-150 bg-gray-50/20 flex gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border bg-emerald-50 text-emerald-700 border-emerald-100"><i data-lucide="{{ $microsoftConnected ? 'check' : 'clock' }}" class="h-4 w-4"></i></span>
                        <div>
                            <span class="block text-xs font-semibold text-slate-950">Microsoft account</span>
                            <span class="mt-0.5 block text-[11px] text-slate-500">{{ $microsoftConnected ? 'Ready for Microsoft access' : 'Waiting for provisioning' }}</span>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl border border-gray-150 bg-gray-50/20 flex gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border bg-emerald-50 text-emerald-700 border-emerald-100"><i data-lucide="mail" class="h-4 w-4"></i></span>
                        <div class="min-w-0">
                            <span class="block text-xs font-semibold text-slate-950">School email</span>
                            <span class="mt-0.5 block text-[11px] text-slate-500 truncate" title="{{ $schoolEmail }}">{{ $schoolEmail }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-amber-100 bg-amber-50 p-4">
                    <p class="text-xs font-bold text-amber-800 flex items-center gap-1"><i data-lucide="alert-circle" class="w-4 h-4"></i> Bind first</p>
                    <p class="mt-1 text-[11px] font-semibold leading-relaxed text-amber-700">
                        Login with student ID first, bind your Google account here, then use Google sign-in on the login screen next time.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
