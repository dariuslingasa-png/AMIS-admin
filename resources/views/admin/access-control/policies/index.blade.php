<x-admin-layout title="Access Policies">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-indigo-700/30 bg-gradient-to-br from-indigo-900 via-indigo-700 to-blue-600 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Access Control</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Access Policies</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        System security rules, hierarchical boundaries, and privilege evaluation guidelines.
                    </p>
                </div>
            </div>
        </section>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Hierarchy levels card -->
            <x-card title="Hierarchy Rules" subtitle="Administrative role authority levels">
                <div class="p-6 space-y-4">
                    <p class="text-xs font-semibold text-slate-500 leading-5">
                        The AMIS Portal utilizes a rank-based hierarchy system. Accounts with higher ranks can manage accounts and roles of equal or lower ranking, but cannot modify accounts with superior hierarchy levels.
                    </p>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-50 text-[10px] font-black text-indigo-700">1</span>
                                <span class="text-xs font-extrabold text-slate-900 uppercase">Super Administrator (LVL 100)</span>
                            </div>
                            <span class="text-xs font-bold text-slate-400">Owner Status</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 text-[10px] font-bold text-slate-700">2</span>
                                <span class="text-xs font-extrabold text-slate-900 uppercase">System Administrator (LVL 80)</span>
                            </div>
                            <span class="text-xs font-bold text-slate-400">Full Access</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 text-[10px] font-bold text-slate-700">3</span>
                                <span class="text-xs font-extrabold text-slate-900 uppercase">Finance Officer (LVL 50)</span>
                            </div>
                            <span class="text-xs font-bold text-slate-400">Department Lock</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100 text-[10px] font-bold text-slate-700">4</span>
                                <span class="text-xs font-extrabold text-slate-900 uppercase">Staff Member (LVL 10)</span>
                            </div>
                            <span class="text-xs font-bold text-slate-400">View Only</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Super Admin protection card -->
            <x-card title="Super Admin Safeguards" subtitle="Built-in safeguards against privilege escalations">
                <div class="p-6 space-y-4">
                    <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4 text-xs font-bold text-amber-800 leading-5">
                        <div class="flex items-center gap-2 mb-2">
                            <i data-lucide="shield-alert" class="h-4 w-4"></i>
                            <span>Protected Policies</span>
                        </div>
                        * The primary Super Administrator role is fully protected in the system core and cannot be edited, modified, or deleted.<br>
                        * Users who do not hold a Super Admin role cannot assign other accounts to the Super Admin role.<br>
                        * No user can demote their own account or suspend themselves, preventing administrator locks.
                    </div>

                    <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-4 text-xs font-bold text-rose-800 leading-5">
                        <div class="flex items-center gap-2 mb-2">
                            <i data-lucide="shield-x" class="h-4 w-4"></i>
                            <span>View-Only Mode</span>
                        </div>
                        Any account that has the `view_only` permission assigned will automatically have all write queries (such as creating users, editing grades, or deleting backups) blocked, even if they have other high-level roles assigned.
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-admin-layout>
