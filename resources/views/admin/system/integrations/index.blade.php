<x-admin-layout title="API Integrations">
    <div class="space-y-6">
        <!-- Banner Component (UNIFIED COLOR & DESIGN) -->
        <x-system-nav title="API & Service Integrations" subtitle="Check connection states and configurations for external API identity providers, email gateways, and cloud storage directories." activeTab="integrations" />

        <!-- Integrations listings -->
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Microsoft Entra ID -->
            <x-card title="Microsoft Entra ID" subtitle="Single Sign-on identity configuration">
                <div class="p-6 space-y-4">
                    <p class="text-xs font-semibold text-slate-500 leading-normal">
                        {{ $integrations['microsoft_entra']['description'] }}
                    </p>
                    <div class="border-t border-slate-100 pt-3 space-y-2 text-xs font-semibold text-slate-500">
                        <div class="flex justify-between">
                            <span>Status:</span>
                            @if ($integrations['microsoft_entra']['configured'])
                                <span class="font-bold text-emerald-600 uppercase">Configured</span>
                            @else
                                <span class="font-bold text-rose-600 uppercase">Not Configured</span>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <span>Client ID:</span>
                            <span class="font-bold text-slate-800">{{ Str::limit($integrations['microsoft_entra']['client_id'], 18) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Tenant ID:</span>
                            <span class="font-bold text-slate-800">{{ Str::limit($integrations['microsoft_entra']['tenant_id'], 18) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Redirect URI:</span>
                            <span class="font-bold text-slate-800">{{ $integrations['microsoft_entra']['redirect_uri'] }}</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Microsoft Graph API -->
            <x-card title="Microsoft Graph API" subtitle="Active directory and classroom provisioning">
                <div class="p-6 space-y-4">
                    <p class="text-xs font-semibold text-slate-500 leading-normal">
                        {{ $integrations['microsoft_graph']['description'] }}
                    </p>
                    <div class="border-t border-slate-100 pt-3 space-y-2 text-xs font-semibold text-slate-500">
                        <div class="flex justify-between">
                            <span>Status:</span>
                            @if ($integrations['microsoft_graph']['configured'])
                                <span class="font-bold text-emerald-600 uppercase">Active</span>
                            @else
                                <span class="font-bold text-rose-600 uppercase">Missing Credentials</span>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <span>Scopes Requested:</span>
                            <span class="font-bold text-slate-800 max-w-[200px] truncate" title="{{ $integrations['microsoft_graph']['scopes'] }}">
                                {{ $integrations['microsoft_graph']['scopes'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Google Drive API -->
            <x-card title="Google Drive Cloud Backups" subtitle="Stateless rclone credentials settings">
                <div class="p-6 space-y-4">
                    <p class="text-xs font-semibold text-slate-500 leading-normal">
                        {{ $integrations['google_drive']['description'] }}
                    </p>
                    <div class="border-t border-slate-100 pt-3 space-y-2 text-xs font-semibold text-slate-500">
                        <div class="flex justify-between">
                            <span>Status:</span>
                            @if ($integrations['google_drive']['configured'])
                                <span class="font-bold text-emerald-600 uppercase">Ready</span>
                            @else
                                <span class="font-bold text-rose-600 uppercase">Rclone Not Configured</span>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <span>GDrive Folder ID:</span>
                            <span class="font-bold text-slate-800">{{ Str::limit($integrations['google_drive']['folder_id'], 18) }}</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Email SMTP gateway -->
            <x-card title="SMTP Mail Gateway" subtitle="System notifications and alerts transport">
                <div class="p-6 space-y-4">
                    <p class="text-xs font-semibold text-slate-500 leading-normal">
                        {{ $integrations['email']['description'] }}
                    </p>
                    <div class="border-t border-slate-100 pt-3 space-y-2 text-xs font-semibold text-slate-500">
                        <div class="flex justify-between">
                            <span>Status:</span>
                            @if ($integrations['email']['configured'])
                                <span class="font-bold text-emerald-600 uppercase">Configured</span>
                            @else
                                <span class="font-bold text-rose-600 uppercase">SMTP Disabled</span>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <span>SMTP Gateway Host:</span>
                            <span class="font-bold text-slate-800">{{ $integrations['email']['host'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Port / Encryption:</span>
                            <span class="font-bold text-slate-800">{{ $integrations['email']['port'] }} ({{ $integrations['email']['encryption'] }})</span>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-admin-layout>
