@php
    $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-rose-400 focus:ring-4 focus:ring-rose-100';
@endphp

<x-admin-layout
    title="Support Center Settings"
    :breadcrumbs="[
        ['label' => 'Support Center', 'href' => route('admin.support.index')],
        ['label' => 'Settings', 'href' => null],
    ]"
>
    <section class="max-w-3xl rounded-lg border border-slate-200 bg-white shadow-sm">
        <!-- Section Header -->
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wider text-rose-700">Support Center Workspace</p>
                <h1 class="mt-1 text-xl font-bold text-slate-950">Department Email Settings</h1>
                <p class="mt-1 text-sm text-slate-500">Configure email addresses for auto-forwarding ticket alerts when new inquiries are submitted.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.support.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Back to List
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.support.settings.save') }}" class="p-6">
            @csrf

            <div class="space-y-6">
                @foreach ($concernTypes as $type)
                    @php
                        $inputName = str_replace(' ', '_', $type);
                        $value = old($inputName, $settings[$type] ?? '');
                        
                        // Icon mapping
                        $icon = match($type) {
                            'Forgot Password' => 'key',
                            'Resend Credentials' => 'shield-alert',
                            'Enrollment Concern' => 'user-plus',
                            'Payment Concern' => 'credit-card',
                            'Microsoft Account Issue' => 'mail',
                            'General Inquiry' => 'help-circle',
                            default => 'message-square'
                        };
                    @endphp

                    <div class="flex flex-col gap-2">
                        <label for="{{ $inputName }}" class="flex items-center gap-2 text-sm font-extrabold text-slate-700">
                            <i data-lucide="{{ $icon }}" class="h-4.5 w-4.5 text-slate-500 shrink-0"></i>
                            {{ $type }} Notification Email
                        </label>
                        <div class="relative">
                            <i data-lucide="mail" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                            <input 
                                type="email" 
                                id="{{ $inputName }}" 
                                name="{{ $inputName }}" 
                                value="{{ $value }}" 
                                placeholder="Enter email to notify (e.g. name@domain.com)" 
                                class="{{ $inputClass }} w-full pl-9"
                            >
                        </div>
                        <p class="text-xs text-slate-400 font-medium">
                            @if(in_array($type, ['Forgot Password', 'Resend Credentials', 'Microsoft Account Issue']))
                                Defaults to <strong class="text-slate-600">munos.amis@gmail.com</strong> if left blank.
                            @else
                                No email notifications will be sent for this category if left blank.
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>

            <!-- Submit buttons -->
            <div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('admin.support.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-6 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Cancel</a>
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-rose-700 px-6 text-sm font-bold text-white transition hover:bg-rose-800">
                    Save Settings
                </button>
            </div>
        </form>
    </section>
</x-admin-layout>
