<x-admin-layout title="Enrollment Settings">
    <x-card title="Enrollment Settings" subtitle="Configure enrollment approval readiness checks and generation features">
        <form method="POST" action="{{ route('admin.settings.enrollment.update') }}" class="grid gap-4">
            @csrf
            @method('PATCH')

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                <h3 class="mb-4 text-sm font-extrabold uppercase tracking-wider text-slate-600">Onboarding Email</h3>

                <label class="flex items-center gap-3 text-sm font-medium text-gray-900 cursor-pointer">
                    <input type="hidden" name="send_onboarding_email" value="0">
                    <input type="checkbox" name="send_onboarding_email" value="1" @checked(old('send_onboarding_email', $setting->send_onboarding_email ?? false)) class="h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Send welcome email with student credentials after approval when payment proof is uploaded</span>
                </label>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                <h3 class="mb-4 text-sm font-extrabold uppercase tracking-wider text-slate-600">Generation Features (Auto-create during Approval)</h3>
                <p class="mb-4 text-xs text-slate-500">Uncheck to skip a generation step. At minimum, AMIS Student ID is required to approve.</p>

                <input type="hidden" name="generate_soa" value="0">

                <div class="grid gap-3">
                    @foreach ([
                        'generate_amis_id' => 'Generate AMIS Student ID (required)',
                        'generate_microsoft_account' => 'Auto-create Microsoft 365 account + school email',
                    ] as $field => $label)
                        <label class="flex items-center gap-3 text-sm font-medium text-gray-900 cursor-pointer">
                            <input type="hidden" name="{{ $field }}" value="0">
                            <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $setting->{$field} ?? true)) class="h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                <h3 class="mb-4 text-sm font-extrabold uppercase tracking-wider text-slate-600">Readiness Checks (Required before Approval)</h3>
                <p class="mb-4 text-xs text-slate-500">Uncheck to skip a check and allow approval without it.</p>

                <div class="grid gap-3">
                    @foreach ([
                        'require_documents_approved' => 'All required documents must be approved',
                        'require_payment_verified' => 'Payment must be verified',
                        'require_complete_fields' => 'All required fields must be complete',
                    ] as $field => $label)
                        <label class="flex items-center gap-3 text-sm font-medium text-gray-900 cursor-pointer">
                            <input type="hidden" name="{{ $field }}" value="0">
                            <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $setting->{$field} ?? true)) class="h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <button class="rounded-lg bg-emerald-700 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                    Save Enrollment Settings
                </button>
            </div>
        </form>
    </x-card>
</x-admin-layout>
