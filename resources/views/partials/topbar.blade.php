<nav class="fixed top-0 z-50 w-full border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <div class="px-3 py-3 lg:px-5 lg:pl-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center justify-start">
                <button data-drawer-target="default-sidebar"
                        data-drawer-toggle="default-sidebar"
                        aria-controls="default-sidebar"
                        type="button"
                        class="hidden items-center rounded-lg p-2 text-sm text-gray-500">
                    <span class="sr-only">Open sidebar</span>
                    <i data-lucide="menu" class="h-6 w-6"></i>
                </button>
                <a href="{{ route(Auth::user()?->adminHomeRouteName() ?? 'admin.login') }}" class="ms-2 flex items-center md:me-24">
                    <img src="{{ asset('images/AMIS_Logo.svg') }}" class="me-3 h-8 w-8 object-contain" alt="AMIS Logo">
                    <span class="self-center whitespace-nowrap text-xl font-semibold dark:text-white">AMIS Admin Portal</span>
                </a>
            </div>

            <div class="flex items-center gap-3" x-data="systemNotifications()">
                <!-- Notification Bell Dropdown Button -->
                <div class="relative">
                    <button @click="toggle()" type="button" class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 transition cursor-pointer">
                        <span class="sr-only">Notifications</span>
                        <i data-lucide="bell" class="h-5 w-5"></i>
                        <template x-if="unreadCount > 0">
                            <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-600 text-[9px] font-black text-white ring-2 ring-white dark:ring-gray-800 animate-pulse" x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
                        </template>
                    </button>

                    <!-- Dropdown Panel -->
                    <div x-show="open" 
                         @click.away="open = false" 
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white dark:bg-gray-800 shadow-2xl border border-gray-100 dark:border-gray-700 z-50 overflow-hidden"
                         style="display: none;">
                        
                        <!-- Panel Header -->
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs font-black uppercase tracking-wider text-gray-900 dark:text-white">System Notifications</h3>
                                <template x-if="unreadCount > 0">
                                    <span class="rounded-full bg-emerald-100 text-emerald-800 px-2 py-0.5 text-[10px] font-bold" x-text="unreadCount + ' new'"></span>
                                </template>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="markAllAsRead()" x-show="unreadCount > 0" type="button" class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                                    Mark all read
                                </button>
                            </div>
                        </div>

                        <!-- Notifications List -->
                        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-if="loading">
                                <div class="p-6 text-center text-xs text-gray-400">Loading notifications...</div>
                            </template>
                            <template x-if="!loading && notifications.length === 0">
                                <div class="p-8 text-center">
                                    <div class="inline-flex rounded-full bg-gray-100 dark:bg-gray-700 p-3 text-gray-400 mb-2">
                                        <i data-lucide="bell-off" class="h-6 w-6"></i>
                                    </div>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300">No system notifications</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5">All quiet! Real-time alerts will appear here.</p>
                                </div>
                            </template>
                            <template x-for="item in notifications" :key="item.id">
                                <div @click="handleClick(item)" 
                                     :class="item.is_read ? 'bg-white dark:bg-gray-800 opacity-75' : 'bg-emerald-50/40 dark:bg-emerald-950/20 font-medium'"
                                     class="p-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition cursor-pointer flex items-start gap-3 relative group">
                                    
                                    <!-- Type Icon -->
                                    <div class="shrink-0 rounded-xl p-2 mt-0.5" 
                                         :class="getTypeClass(item.type)">
                                         <i :data-lucide="getIcon(item.type)" class="h-4 w-4"></i>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-1">
                                            <h4 class="text-xs font-bold text-gray-900 dark:text-white truncate" x-text="item.title"></h4>
                                            <span class="text-[10px] text-gray-400 shrink-0" x-text="item.time_ago"></span>
                                        </div>
                                        <p class="text-[11px] text-gray-600 dark:text-gray-300 mt-0.5 line-clamp-2 leading-relaxed" x-text="item.message"></p>
                                    </div>

                                    <!-- Unread Dot -->
                                    <template x-if="!item.is_read">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500 shrink-0 self-center"></span>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Footer -->
                        <div class="p-2.5 bg-gray-50 dark:bg-gray-700/40 border-t border-gray-100 dark:border-gray-700 text-center flex items-center justify-between px-4">
                            <button @click="clearAll()" x-show="notifications.length > 0" type="button" class="text-[11px] font-bold text-rose-600 hover:text-rose-700">
                                Clear All
                            </button>
                            <a href="{{ route('admin.system-management.backups.index') }}" class="text-[11px] font-bold text-gray-500 hover:text-gray-700 dark:text-gray-300">
                                View Backup Center &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <button type="button"
                        class="flex rounded-full bg-gray-800 text-sm focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                        data-dropdown-toggle="dropdown-user">
                    <span class="sr-only">Open user menu</span>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-primary-700">
                        <i data-lucide="user" class="h-5 w-5"></i>
                    </span>
                </button>
                <div id="dropdown-user" class="z-50 hidden list-none divide-y divide-gray-100 rounded-lg bg-white text-base shadow dark:divide-gray-600 dark:bg-gray-700">
                    <div class="px-4 py-3">
                        <p class="text-sm text-gray-900 dark:text-white">{{ Auth::user()->name ?? 'AMIS Admin' }}</p>
                        <p class="truncate text-sm font-medium text-gray-500 dark:text-gray-300">{{ Auth::user()->email ?? '' }}</p>
                    </div>
                    <ul class="py-1">
                        <li><a href="{{ route(Auth::user()?->adminHomeRouteName() ?? 'admin.login') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">Dashboard</a></li>
                        <li>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-gray-600">Sign out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
function systemNotifications() {
    return {
        open: false,
        loading: false,
        unreadCount: 0,
        notifications: [],
        init: function() {
            this.fetchNotifications();
            var self = this;
            setInterval(function() { self.fetchNotifications(); }, 30000);
        },
        toggle: function() {
            this.open = !this.open;
            if (this.open) {
                this.fetchNotifications();
            }
        },
        fetchNotifications: function() {
            var self = this;
            fetch('{{ route('admin.notifications.index') }}')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data && data.success) {
                        self.unreadCount = data.unread_count;
                        self.notifications = data.notifications;
                        if (window.lucide) {
                            setTimeout(function() { lucide.createIcons(); }, 50);
                        }
                    }
                })
                .catch(function() {});
        },
        handleClick: function(item) {
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';
            if (!item.is_read) {
                fetch('{{ route('admin.notifications.index') }}/' + item.id + '/read', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf 
                    }
                });
                item.is_read = true;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            }
            if (item.action_url) {
                window.location.href = item.action_url;
            }
        },
        markAllAsRead: function() {
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';
            var self = this;
            fetch('{{ route('admin.notifications.mark-all-read') }}', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf 
                }
            }).then(function() {
                self.unreadCount = 0;
                self.notifications.forEach(function(n) { n.is_read = true; });
            });
        },
        clearAll: function() {
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';
            var self = this;
            if (confirm('Clear all notifications?')) {
                fetch('{{ route('admin.notifications.clear') }}', {
                    method: 'DELETE',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf 
                    }
                }).then(function() {
                    self.unreadCount = 0;
                    self.notifications = [];
                });
            }
        },
        getIcon: function(type) {
            switch(type) {
                case 'success': return 'check-circle';
                case 'error': return 'x-circle';
                case 'warning': return 'alert-triangle';
                default: return 'info';
            }
        },
        getTypeClass: function(type) {
            switch(type) {
                case 'success': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300';
                case 'error': return 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300';
                case 'warning': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300';
                default: return 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300';
            }
        }
    };
}
</script>
