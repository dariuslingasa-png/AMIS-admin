<x-admin-layout title="Permissions Matrix">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-indigo-700/30 bg-gradient-to-br from-indigo-900 via-indigo-700 to-blue-600 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Access Control</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Permission Matrix</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        Map modular category permissions across system roles. Super Administrators always bypass matrix restrictions.
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

        <form method="POST" action="{{ route('admin.access-control.permissions.update') }}">
            @csrf
            <x-card title="System Authorization Grid" subtitle="Assign capabilities to roles">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm min-w-[700px]">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                                <th class="px-5 py-4 w-[280px]">Permission Directive</th>
                                @foreach ($roles as $role)
                                    <th class="px-4 py-4 text-center">
                                        <span class="block text-slate-950 font-extrabold uppercase">{{ $role->name }}</span>
                                        <span class="block text-[9px] text-slate-400 font-semibold mt-0.5">LVL {{ $role->hierarchy_level }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($permissions as $category => $perms)
                                <tr class="bg-slate-50/40">
                                    <td colspan="{{ count($roles) + 1 }}" class="px-5 py-2 text-[10px] font-black uppercase tracking-wider text-slate-400 border-y border-slate-100">
                                        Category: {{ str_replace('_', ' ', $category) }}
                                    </td>
                                </tr>
                                @foreach ($perms as $perm)
                                    <tr class="hover:bg-slate-50/20 transition-colors">
                                        <td class="px-5 py-4">
                                            <span class="font-extrabold text-slate-900 block text-xs uppercase tracking-wide">{{ $perm->name }}</span>
                                            <span class="text-xs font-semibold text-slate-400 block mt-1 leading-5">{{ $perm->description }}</span>
                                        </td>
                                        @foreach ($roles as $role)
                                            <td class="px-4 py-4 text-center">
                                                @if ($role->slug === 'super_admin')
                                                    <!-- Super admin is locked to true -->
                                                    <input type="checkbox" checked disabled class="rounded border-slate-300 text-indigo-600/60 focus:ring-indigo-500">
                                                @else
                                                    @php
                                                        $hasPermission = $role->permissions->contains($perm->id);
                                                    @endphp
                                                    <input type="checkbox" name="matrix[{{ $role->id }}][{{ $perm->id }}]" value="1" @checked($hasPermission) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-4 sm:px-6 border-t border-slate-100 flex items-center justify-end bg-slate-50/30">
                    <button type="submit" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-6 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 cursor-pointer">
                        Save Changes
                    </button>
                </div>
            </x-card>
        </form>
    </div>
</x-admin-layout>
