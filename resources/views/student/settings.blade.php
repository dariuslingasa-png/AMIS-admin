@extends('student.layout')

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
        'slate' => 'border-slate-200 bg-slate-50 text-slate-600',
    ];
@endphp

<div class="space-y-6">
    <section class="dashboard-hero text-white">
        <div>
            <span class="hero-eyebrow">
                <i data-lucide="settings" class="mr-2 h-4 w-4"></i>
                Settings
            </span>
            <h1 class="mt-4 text-3xl font-black leading-tight md:text-4xl">Account Connections</h1>
            <p class="mt-3 max-w-2xl text-sm font-semibold leading-relaxed text-emerald-50/90">
                Review your Microsoft 365 access and bind your Google account for faster portal sign-in.
            </p>
        </div>

        <div class="hidden min-w-[15rem] rounded-2xl border border-white/20 bg-white/10 p-5 lg:block">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-100">Google Account</p>
            <p class="mt-1 text-2xl font-black">{{ $googleLinked ? 'Linked' : 'Not linked' }}</p>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
        <article class="dashboard-card">
            <div class="flex items-center gap-3">
                <span class="kpi-icon"><i data-lucide="mail" class="h-5 w-5"></i></span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-500">School Email</p>
                    <p class="truncate text-base font-bold text-slate-950">{{ $schoolEmail }}</p>
                </div>
            </div>
        </article>

        <article class="dashboard-card">
            <div class="flex items-center gap-3">
                <span class="kpi-icon"><i data-lucide="{{ $microsoftConnected ? 'check-circle-2' : 'alert-circle' }}" class="h-5 w-5"></i></span>
                <div>
                    <p class="text-sm font-medium text-slate-500">Microsoft Status</p>
                    <p class="text-base font-bold text-slate-950">{{ $microsoftConnected ? 'Connected' : 'Pending' }}</p>
                </div>
            </div>
        </article>

        <article class="dashboard-card">
            <div class="flex items-center gap-3">
                <span class="kpi-icon"><i data-lucide="chrome" class="h-5 w-5"></i></span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-500">Google Account</p>
                    <p class="truncate text-base font-bold text-slate-950">{{ $googleLinked ? 'Linked' : ($googleConfigured ? 'Ready to bind' : 'Not configured') }}</p>
                </div>
            </div>
        </article>
    </section>

    <div class="grid gap-6 lg:grid-cols-12">
        <section class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm lg:col-span-8">
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-950">Connection Details</h2>
                    <p class="mt-1 text-sm text-slate-500">Visible student account values from AMIS, Microsoft 365, and Firebase.</p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold {{ $googleLinked ? $toneClasses['emerald'] : ($googleConfigured ? $toneClasses['amber'] : $toneClasses['slate']) }}">
                    <i data-lucide="{{ $googleLinked ? 'check-circle-2' : ($googleConfigured ? 'link' : 'settings') }}" class="h-3.5 w-3.5"></i>
                    {{ $googleLinked ? 'Google Linked' : ($googleConfigured ? 'Ready to bind' : 'Needs config') }}
                </span>
            </div>

            <div class="premium-table-wrap max-h-none">
                <table class="premium-table">
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
                                    <span class="flex items-center gap-2 font-bold text-slate-900">
                                        <i data-lucide="{{ $icon }}" class="h-4 w-4 text-slate-400"></i>
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="max-w-xs truncate" title="{{ $value }}">{{ $value }}</td>
                                <td>
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $toneClasses[$tone] }}">
                                        {{ $tone === 'emerald' ? 'Ready' : ($tone === 'amber' ? 'Pending' : 'Not set') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm lg:col-span-4">
            <h2 class="text-xl font-bold text-slate-950">Link Accounts</h2>
            <p class="mt-1 text-sm text-slate-500">Bind your Google account to this exact student portal user.</p>

            <div class="mt-5 space-y-3">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex items-start gap-3">
                        <span class="quick-action-icon"><i data-lucide="chrome" class="h-5 w-5"></i></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-950">Google Account</span>
                            <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $googleLinked ? ($user->google_email ?: 'Linked') : ($googleConfigured ? 'Not linked yet' : 'Google not configured') }}</span>
                        </span>
                    </div>
                    <div class="mt-4">
                        @if($googleLinked)
                            <form method="POST" action="{{ route('student.settings.google.unlink') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-rose-100 bg-rose-50 px-4 py-2 text-sm font-bold text-rose-700 hover:bg-rose-100">
                                    <i data-lucide="unlink" class="h-4 w-4"></i>
                                    Unlink Google Account
                                </button>
                            </form>
                        @elseif($googleConfigured)
                            <a href="{{ route('student.settings.google.redirect') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                                <i data-lucide="link" class="h-4 w-4"></i>
                                Bind Google Account
                            </a>
                        @else
                            <button type="button" disabled class="inline-flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-bold text-slate-400">
                                Configure Google first
                            </button>
                        @endif
                    </div>
                </div>

                <div class="quick-action">
                    <span class="quick-action-icon"><i data-lucide="{{ $microsoftConnected ? 'check' : 'clock' }}" class="h-5 w-5"></i></span>
                    <span>
                        <span class="block text-sm font-semibold text-slate-950">Microsoft account</span>
                        <span class="mt-0.5 block text-xs text-slate-500">{{ $microsoftConnected ? 'Ready for Microsoft access' : 'Waiting for provisioning' }}</span>
                    </span>
                </div>

                <div class="quick-action">
                    <span class="quick-action-icon"><i data-lucide="mail" class="h-5 w-5"></i></span>
                    <span>
                        <span class="block text-sm font-semibold text-slate-950">School email</span>
                        <span class="mt-0.5 block text-xs text-slate-500">{{ $schoolEmail }}</span>
                    </span>
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-amber-100 bg-amber-50 p-4">
                <p class="text-sm font-bold text-amber-800">Bind first</p>
                <p class="mt-1 text-xs font-semibold leading-relaxed text-amber-700">
                    Login with student ID first, bind your Google account here, then use Google sign-in on the login screen next time.
                </p>
            </div>
        </aside>
    </div>
</div>

@endsection
