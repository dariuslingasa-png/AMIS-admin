<x-admin-layout title="Microsoft 365 Sync">
    <div x-data="{
        rows: [],
        stats: {
            azure_total: 0,
            in_portal: 0,
            missing_portal: 0,
            guest_users: 0,
            teams_enrolled: 0,
            teams_failed: 0,
            test_accounts: 0
        },
        azureError: null,
        loading: true,
        search: '',
        sortBy: 'upn',
        sortAsc: true,
        page: 1,
        perPage: 15,

        async init() {
            try {
                const res = await fetch('{{ route('admin.ms-sync.data') }}');
                if (!res.ok) throw new Error('Failed to fetch synchronization data.');
                const data = await res.json();
                this.rows = data.rows || [];
                this.stats = data.stats || this.stats;
                this.azureError = data.azureError;
            } catch (err) {
                this.azureError = err.message;
            } finally {
                this.loading = false;
            }
        },

        get filteredRows() {
            let items = [...this.rows];

            if (this.search) {
                const q = this.search.toLowerCase();
                items = items.filter(r => 
                    (r.upn && r.upn.toLowerCase().includes(q)) ||
                    (r.display_name && r.display_name.toLowerCase().includes(q)) ||
                    (r.azure_type && r.azure_type.toLowerCase().includes(q)) ||
                    (r.teams_status && r.teams_status.toLowerCase().includes(q))
                );
            }

            items.sort((a, b) => {
                let valA = '';
                let valB = '';
                
                if (this.sortBy === 'upn') {
                    valA = a.upn || '';
                    valB = b.upn || '';
                } else if (this.sortBy === 'name') {
                    valA = a.display_name || '';
                    valB = b.display_name || '';
                } else if (this.sortBy === 'type') {
                    valA = a.azure_type || '';
                    valB = b.azure_type || '';
                } else if (this.sortBy === 'portal') {
                    valA = a.in_portal ? 'Linked' : 'Missing';
                    valB = b.in_portal ? 'Linked' : 'Missing';
                } else if (this.sortBy === 'teams') {
                    valA = a.teams_status || '';
                    valB = b.teams_status || '';
                }

                valA = valA.toLowerCase();
                valB = valB.toLowerCase();

                if (valA < valB) return this.sortAsc ? -1 : 1;
                if (valA > valB) return this.sortAsc ? 1 : -1;
                return 0;
            });

            return items;
        },

        get paginatedRows() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredRows.slice(start, start + this.perPage);
        },

        get totalPages() {
            return Math.ceil(this.filteredRows.length / this.perPage) || 1;
        },

        sort(field) {
            if (this.sortBy === field) {
                this.sortAsc = !this.sortAsc;
            } else {
                this.sortBy = field;
                this.sortAsc = true;
            }
            this.page = 1;
        },

        nextPage() {
            if (this.page < this.totalPages) this.page++;
        },

        prevPage() {
            if (this.page > 1) this.page--;
        }
    }">

        <!-- Error Notifications -->
        <template x-if="azureError">
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 flex items-start gap-3 shadow-xs">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-650 shrink-0 mt-0.5"></i>
                <div>
                    <span class="font-bold">Azure Integration Alert:</span>
                    <span x-text="azureError"></span>
                </div>
            </div>
        </template>

        <!-- Actions Panel -->
        <div class="mb-6">
            <x-card title="Sync Actions" subtitle="Trigger Microsoft 365 synchronization processes">
                <div class="p-6 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex flex-wrap gap-4">
                        <form method="POST" action="{{ route('admin.ms-sync.import-all') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Import All Missing Users
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.ms-sync.fix-guests') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
                                <i data-lucide="user-check" class="w-4 h-4"></i>
                                Convert Guests to Members
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.ms-sync.retry-failed') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
                                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                Retry Failed Teams Enrollment
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.ms-sync.cleanup-test') }}" onsubmit="return confirm('Are you sure you want to delete all test accounts from Azure AD?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                Cleanup Azure Test Accounts
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.ms-sync.cleanup-portal') }}" onsubmit="return confirm('Are you sure you want to remove all test student data from the portal database?');">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
                                <i data-lucide="eraser" class="w-4 h-4"></i>
                                Cleanup Portal Test Data
                            </button>
                        </form>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Premium Metrics Grid -->
        <div class="mb-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Azure total -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">Azure total</div>
                        <div class="mt-2.5">
                            <template x-if="loading">
                                <div class="skeleton-box w-16 h-8"></div>
                            </template>
                            <template x-if="!loading">
                                <div class="text-3.5xl font-extrabold text-slate-800 dark:text-white tracking-tight" x-text="stats.azure_total"></div>
                            </template>
                        </div>
                    </div>
                    <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400 transition-transform duration-305 group-hover:scale-110">
                        <i data-lucide="users" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>

            <!-- In portal -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">In portal</div>
                        <div class="mt-2.5">
                            <template x-if="loading">
                                <div class="skeleton-box w-16 h-8"></div>
                            </template>
                            <template x-if="!loading">
                                <div class="text-3.5xl font-extrabold text-slate-800 dark:text-white tracking-tight" x-text="stats.in_portal"></div>
                            </template>
                        </div>
                    </div>
                    <div class="p-3 rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-950/30 dark:text-sky-400 transition-transform duration-305 group-hover:scale-110">
                        <i data-lucide="database" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>

            <!-- Missing portal -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">Missing portal</div>
                        <div class="mt-2.5">
                            <template x-if="loading">
                                <div class="skeleton-box w-16 h-8"></div>
                            </template>
                            <template x-if="!loading">
                                <div class="text-3.5xl font-extrabold text-slate-800 dark:text-white tracking-tight" x-text="stats.missing_portal"></div>
                            </template>
                        </div>
                    </div>
                    <div class="p-3 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400 transition-transform duration-305 group-hover:scale-110">
                        <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>

            <!-- Guest users -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">Guest users</div>
                        <div class="mt-2.5">
                            <template x-if="loading">
                                <div class="skeleton-box w-16 h-8"></div>
                            </template>
                            <template x-if="!loading">
                                <div class="text-3.5xl font-extrabold text-slate-800 dark:text-white tracking-tight" x-text="stats.guest_users"></div>
                            </template>
                        </div>
                    </div>
                    <div class="p-3 rounded-xl bg-fuchsia-50 text-fuchsia-600 dark:bg-fuchsia-950/30 dark:text-fuchsia-400 transition-transform duration-305 group-hover:scale-110">
                        <i data-lucide="user-minus" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>

            <!-- Teams enrolled -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">Teams enrolled</div>
                        <div class="mt-2.5">
                            <template x-if="loading">
                                <div class="skeleton-box w-16 h-8"></div>
                            </template>
                            <template x-if="!loading">
                                <div class="text-3.5xl font-extrabold text-slate-800 dark:text-white tracking-tight" x-text="stats.teams_enrolled"></div>
                            </template>
                        </div>
                    </div>
                    <div class="p-3 rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-950/30 dark:text-teal-400 transition-transform duration-305 group-hover:scale-110">
                        <i data-lucide="slack" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>

            <!-- Teams failed -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Teams failed</div>
                        <div class="mt-2.5">
                            <template x-if="loading">
                                <div class="skeleton-box w-16 h-8"></div>
                            </template>
                            <template x-if="!loading">
                                <div class="text-3.5xl font-extrabold text-slate-800 dark:text-white tracking-tight" x-text="stats.teams_failed"></div>
                            </template>
                        </div>
                    </div>
                    <div class="p-3 rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-400 transition-transform duration-305 group-hover:scale-110">
                        <i data-lucide="x-octagon" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>

            <!-- Test accounts -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">Test accounts</div>
                        <div class="mt-2.5">
                            <template x-if="loading">
                                <div class="skeleton-box w-16 h-8"></div>
                            </template>
                            <template x-if="!loading">
                                <div class="text-3.5xl font-extrabold text-slate-800 dark:text-white tracking-tight" x-text="stats.test_accounts"></div>
                            </template>
                        </div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800/40 dark:text-slate-400 transition-transform duration-305 group-hover:scale-110">
                        <i data-lucide="terminal" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accounts Unified Table Card -->
        <div class="mt-8">
            <x-card>
                <!-- Card Header with Title, Search and Per-Page Select -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 dark:border-slate-800 px-6 py-5 bg-white dark:bg-slate-900 rounded-t-2xl">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Azure / Portal Accounts</h3>
                        <p class="text-xs text-slate-500 font-light mt-0.5">Listing and filtering synced identities</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="relative block">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
                            </span>
                            <input type="search" x-model="search" @input="page = 1" placeholder="Search accounts..." class="w-64 rounded-xl border border-slate-250 py-2 pl-10 pr-4 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-hidden bg-slate-50/50 focus:bg-white transition-all duration-150">
                        </label>
                        <select x-model="perPage" @change="page = 1" class="rounded-xl border border-slate-250 py-2 px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-hidden bg-white dark:bg-slate-900 cursor-pointer">
                            <option value="15">15 per page</option>
                            <option value="25">25 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                        </select>
                    </div>
                </div>

                <!-- Datatable -->
                <div class="overflow-x-auto">
                    <table class="amis-table w-full">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-800">
                                <th class="px-6 py-4.5 text-left text-xs font-bold text-slate-500 uppercase cursor-pointer select-none transition-colors hover:text-slate-800" @click="sort('upn')">
                                    <div class="flex items-center gap-2">
                                        <span>UPN</span>
                                        <span class="inline-flex flex-col">
                                            <svg class="w-2.5 h-2.5" :class="sortBy === 'upn' && sortAsc ? 'text-emerald-600' : 'text-slate-300 dark:text-slate-600'" fill="currentColor" viewBox="0 0 24 24"><path d="M7 14l5-5 5 5z"/></svg>
                                            <svg class="w-2.5 h-2.5 -mt-1" :class="sortBy === 'upn' && !sortAsc ? 'text-emerald-600' : 'text-slate-300 dark:text-slate-600'" fill="currentColor" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-4.5 text-left text-xs font-bold text-slate-500 uppercase cursor-pointer select-none transition-colors hover:text-slate-800" @click="sort('name')">
                                    <div class="flex items-center gap-2">
                                        <span>Name</span>
                                        <span class="inline-flex flex-col">
                                            <svg class="w-2.5 h-2.5" :class="sortBy === 'name' && sortAsc ? 'text-emerald-600' : 'text-slate-300 dark:text-slate-600'" fill="currentColor" viewBox="0 0 24 24"><path d="M7 14l5-5 5 5z"/></svg>
                                            <svg class="w-2.5 h-2.5 -mt-1" :class="sortBy === 'name' && !sortAsc ? 'text-emerald-600' : 'text-slate-300 dark:text-slate-600'" fill="currentColor" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-4.5 text-left text-xs font-bold text-slate-500 uppercase cursor-pointer select-none transition-colors hover:text-slate-800" @click="sort('type')">
                                    <div class="flex items-center gap-2">
                                        <span>Type</span>
                                        <span class="inline-flex flex-col">
                                            <svg class="w-2.5 h-2.5" :class="sortBy === 'type' && sortAsc ? 'text-emerald-600' : 'text-slate-300 dark:text-slate-600'" fill="currentColor" viewBox="0 0 24 24"><path d="M7 14l5-5 5 5z"/></svg>
                                            <svg class="w-2.5 h-2.5 -mt-1" :class="sortBy === 'type' && !sortAsc ? 'text-emerald-600' : 'text-slate-300 dark:text-slate-600'" fill="currentColor" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-4.5 text-left text-xs font-bold text-slate-500 uppercase cursor-pointer select-none transition-colors hover:text-slate-800" @click="sort('portal')">
                                    <div class="flex items-center gap-2">
                                        <span>Portal</span>
                                        <span class="inline-flex flex-col">
                                            <svg class="w-2.5 h-2.5" :class="sortBy === 'portal' && sortAsc ? 'text-emerald-600' : 'text-slate-300 dark:text-slate-600'" fill="currentColor" viewBox="0 0 24 24"><path d="M7 14l5-5 5 5z"/></svg>
                                            <svg class="w-2.5 h-2.5 -mt-1" :class="sortBy === 'portal' && !sortAsc ? 'text-emerald-600' : 'text-slate-300 dark:text-slate-600'" fill="currentColor" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-4.5 text-left text-xs font-bold text-slate-500 uppercase cursor-pointer select-none transition-colors hover:text-slate-800" @click="sort('teams')">
                                    <div class="flex items-center gap-2">
                                        <span>Teams</span>
                                        <span class="inline-flex flex-col">
                                            <svg class="w-2.5 h-2.5" :class="sortBy === 'teams' && sortAsc ? 'text-emerald-600' : 'text-slate-300 dark:text-slate-600'" fill="currentColor" viewBox="0 0 24 24"><path d="M7 14l5-5 5 5z"/></svg>
                                            <svg class="w-2.5 h-2.5 -mt-1" :class="sortBy === 'teams' && !sortAsc ? 'text-emerald-600' : 'text-slate-300 dark:text-slate-600'" fill="currentColor" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
                                        </span>
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <!-- Loading skeletons -->
                        <tbody x-show="loading">
                            <template x-for="i in Array.from({length: 10})">
                                <tr class="border-b border-slate-100 dark:border-slate-800">
                                    <td class="px-6 py-4"><div class="skeleton-box w-48 h-4"></div></td>
                                    <td class="px-6 py-4"><div class="skeleton-box w-40 h-4"></div></td>
                                    <td class="px-6 py-4"><div class="skeleton-box w-20 h-4"></div></td>
                                    <td class="px-6 py-4"><div class="skeleton-box w-16 h-4"></div></td>
                                    <td class="px-6 py-4"><div class="skeleton-box w-24 h-4"></div></td>
                                </tr>
                            </template>
                        </tbody>

                        <!-- Rows list -->
                        <tbody x-show="!loading">
                            <template x-for="row in paginatedRows" :key="row.upn">
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors border-b border-slate-100 dark:border-slate-800">
                                    <td class="px-6 py-4 text-sm text-slate-700 dark:text-slate-300 font-mono" x-text="row.upn || '-'"></td>
                                    <td class="px-6 py-4 text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wide" x-text="row.display_name ? row.display_name.toUpperCase() : '-'"></td>
                                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400" x-text="row.azure_type || '-'"></td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold tracking-wide border"
                                              :class="row.in_portal ? 'bg-emerald-50 text-emerald-700 border-emerald-100/60 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30' : 'bg-rose-50 text-rose-700 border-rose-100/60 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30'">
                                            <span x-text="row.in_portal ? 'Linked' : 'Missing'"></span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold tracking-wide border"
                                              :class="row.teams_status === 'enrolled' ? 'bg-emerald-50 text-emerald-700 border-emerald-100/60 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30' : (row.teams_status === 'failed' ? 'bg-rose-50 text-rose-700 border-rose-100/60 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30' : 'bg-slate-50 text-slate-600 border-slate-100/60 dark:bg-slate-800/40 dark:text-slate-400 dark:border-slate-700/40')">
                                            <span x-text="row.teams_status || '-'"></span>
                                        </span>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredRows.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                    No accounts matching search query.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination controls -->
                <div x-show="!loading" class="px-6 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-t border-slate-200 dark:border-slate-800">
                    <div class="text-xs text-slate-500">
                        Showing <span class="font-semibold text-slate-800 dark:text-slate-300" x-text="filteredRows.length === 0 ? 0 : (page - 1) * perPage + 1"></span> to 
                        <span class="font-semibold text-slate-800 dark:text-slate-300" x-text="Math.min(page * perPage, filteredRows.length)"></span> of 
                        <span class="font-semibold text-slate-800 dark:text-slate-300" x-text="filteredRows.length"></span> accounts
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="prevPage()" :disabled="page === 1" class="px-3.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition-all duration-150">
                            Previous
                        </button>
                        <div class="text-xs text-slate-500">
                            Page <span class="font-semibold text-slate-800 dark:text-slate-300" x-text="page"></span> of <span class="font-semibold text-slate-800 dark:text-slate-300" x-text="totalPages"></span>
                        </div>
                        <button @click="nextPage()" :disabled="page === totalPages" class="px-3.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition-all duration-150">
                            Next
                        </button>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-admin-layout>
