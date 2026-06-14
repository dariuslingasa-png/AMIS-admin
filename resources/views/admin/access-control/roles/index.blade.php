<x-admin-layout title="Roles Management">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Access Control</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Roles Directory</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        Create, modify, and manage administrative profiles and their hierarchy levels.
                    </p>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <!-- Roles Table -->
            <x-card title="System Roles" subtitle="Defined administration profiles and rankings">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                                <th class="px-4 py-3">Role Profile</th>
                                <th class="px-4 py-3">Rank Level</th>
                                <th class="px-4 py-3">System Protection</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($roles as $role)
                                <tr class="align-middle">
                                    <td class="px-4 py-4">
                                        <div class="font-extrabold text-slate-900 flex items-center gap-1.5 uppercase">
                                            {{ $role->name }}
                                        </div>
                                        <span class="text-xs font-semibold text-slate-500 block mt-1">{{ $role->description ?: 'No description provided.' }}</span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">
                                            LVL {{ $role->hierarchy_level }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        @if ($role->isProtected())
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-blue-700 ring-1 ring-blue-100">
                                                Protected
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-slate-500 ring-1 ring-slate-100">
                                                Custom
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        @if ($role->isProtected() && $role->slug === 'super_admin')
                                            <span class="text-xs font-bold text-slate-400">System Lock</span>
                                        @else
                                            <div class="flex items-center justify-end gap-2">
                                                <!-- Inline Edit Trigger (Simulated for this implementation by standard confirm/actions) -->
                                                @if(!$role->isProtected())
                                                    <form method="POST" action="{{ route('admin.access-control.roles.destroy', $role) }}" class="inline-block" onsubmit="return confirm('Permanently delete the {{ $role->name }} role?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="rounded bg-rose-50 px-2.5 py-1 text-xs font-black uppercase text-rose-700 hover:bg-rose-100 transition cursor-pointer">
                                                            Delete
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-xs font-bold text-slate-400">Protected</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>

            <!-- Create Role Form -->
            <x-card title="Define New Role" subtitle="Configure a custom access profile">
                <form method="POST" action="{{ route('admin.access-control.roles.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Role Name</label>
                        <input name="name" required placeholder="e.g. Registrar Officer" class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Description</label>
                        <textarea name="description" rows="3" placeholder="Enter role scope and department permissions..." class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"></textarea>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Hierarchy Rank Level (0-99)</label>
                        <input name="hierarchy_level" type="number" min="0" max="99" value="10" required class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                        <span class="text-[10px] text-slate-400 font-semibold mt-1 block">Staff rank = 10, Finance = 50, Admin = 80. Hierarchy ranks control profile management priorities.</span>
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-black uppercase tracking-wider text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 cursor-pointer">
                        Create Role
                    </button>
                </form>
            </x-card>
        </div>
    </div>
</x-admin-layout>
