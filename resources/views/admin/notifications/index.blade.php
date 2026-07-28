<x-admin-layout
    title="System Notifications Center"
    :breadcrumbs="[
        ['label' => 'Notifications', 'href' => null],
    ]"
>
    <div class="space-y-6">
        <!-- Top Banner / Actions -->
        <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <i data-lucide="bell" class="h-6 w-6"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">System Notifications Center</h1>
                        @if($unreadCount > 0)
                            <span class="rounded-full bg-emerald-100 text-emerald-800 px-2.5 py-0.5 text-xs font-extrabold">{{ $unreadCount }} Unread</span>
                        @endif
                    </div>
                    <p class="text-xs font-semibold text-slate-500 mt-1">
                        View all automated system events, backups, security alerts, and system notifications.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if($unreadCount > 0)
                    <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white hover:bg-emerald-700 active:scale-95 transition shadow-xs cursor-pointer">
                            <i data-lucide="check-check" class="h-4 w-4"></i>
                            Mark All Read
                        </button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.notifications.clear') }}" onsubmit="return confirm('Are you sure you want to clear all system notifications?')" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-rose-600 hover:bg-rose-50 active:scale-95 transition shadow-xs cursor-pointer">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                        Clear All
                    </button>
                </form>
            </div>
        </section>

        <!-- Filters & Search Toolbar -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
            <!-- Filter Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto p-1 bg-slate-100 rounded-2xl">
                <a href="{{ route('admin.notifications.index') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition whitespace-nowrap {{ !request('filter') ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    All Notifications ({{ $totalCount }})
                </a>
                <a href="{{ route('admin.notifications.index', array_merge(request()->except('filter'), ['filter' => 'unread'])) }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition whitespace-nowrap {{ request('filter') === 'unread' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Unread Only ({{ $unreadCount }})
                </a>
                <a href="{{ route('admin.notifications.index', array_merge(request()->except('filter'), ['filter' => 'backup'])) }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition whitespace-nowrap {{ request('filter') === 'backup' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    System Backups
                </a>
                <a href="{{ route('admin.notifications.index', array_merge(request()->except('filter'), ['filter' => 'system'])) }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition whitespace-nowrap {{ request('filter') === 'system' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    System Alerts
                </a>
            </div>

            <!-- Search Form -->
            <form method="GET" action="{{ route('admin.notifications.index') }}" class="relative min-w-[240px]">
                @if(request('filter'))
                    <input type="hidden" name="filter" value="{{ request('filter') }}">
                @endif
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search notifications..." class="w-full h-10 pl-9 pr-4 text-xs font-bold rounded-xl border border-slate-200 bg-white shadow-2xs focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none">
                <i data-lucide="search" class="absolute left-3 top-3 h-4 w-4 text-slate-400"></i>
            </form>
        </div>

        <!-- Notifications List -->
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
            <div class="divide-y divide-slate-100">
                @forelse($notifications as $notif)
                    @php
                        $iconMap = [
                            'backup' => 'database',
                            'security' => 'shield-alert',
                            'success' => 'check-circle-2',
                            'warning' => 'alert-triangle',
                            'error' => 'alert-circle',
                            'info' => 'info',
                        ];
                        $type = strtolower($notif->type ?? 'info');
                        if (str_contains(strtolower($notif->title), 'backup')) {
                            $type = 'backup';
                        }
                        $icon = $iconMap[$type] ?? 'bell';

                        $colorClasses = [
                            'backup' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                            'security' => 'bg-amber-50 text-amber-600 border-amber-200',
                            'success' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                            'warning' => 'bg-amber-50 text-amber-600 border-amber-200',
                            'error' => 'bg-rose-50 text-rose-600 border-rose-200',
                            'info' => 'bg-sky-50 text-sky-600 border-sky-200',
                        ][$type] ?? 'bg-slate-50 text-slate-600 border-slate-200';
                    @endphp
                    <div class="py-4 first:pt-0 last:pb-0 flex items-start gap-4 transition {{ !$notif->is_read ? 'bg-emerald-50/20 -mx-5 px-5 rounded-2xl my-1 border-l-4 border-emerald-500' : '' }}">
                        <div class="h-10 w-10 rounded-2xl border flex items-center justify-center shrink-0 {{ $colorClasses }}">
                            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-sm font-extrabold text-slate-900 leading-tight">
                                    {{ $notif->title }}
                                </h3>
                                <span class="text-xs font-bold text-slate-400 shrink-0">
                                    {{ $notif->created_at ? $notif->created_at->diffForHumans() : 'Just now' }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                {{ $notif->message }}
                            </p>
                            <div class="mt-2 flex items-center gap-3">
                                @if($notif->action_url)
                                    <a href="{{ $notif->action_url }}" class="inline-flex items-center gap-1 text-xs font-extrabold text-emerald-600 hover:text-emerald-700">
                                        <span>View Details</span>
                                        <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                                    </a>
                                @endif
                                <span class="text-[10px] font-bold text-slate-400">
                                    {{ $notif->created_at ? $notif->created_at->format('M d, Y • h:i A') : '' }}
                                </span>
                            </div>
                        </div>
                        @if(!$notif->is_read)
                            <form method="POST" action="{{ route('admin.notifications.read', $notif->id) }}" class="shrink-0">
                                @csrf
                                <button type="submit" class="inline-flex h-8 px-2.5 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-extrabold transition cursor-pointer" title="Mark as read">
                                    Mark Read
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="p-12 text-center">
                        <div class="inline-flex rounded-full bg-slate-100 p-4 text-slate-400 mb-3">
                            <i data-lucide="bell-off" class="h-8 w-8"></i>
                        </div>
                        <h4 class="text-sm font-extrabold text-slate-800">No Notifications Found</h4>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">There are no system notifications matching your filter or search criteria.</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="pt-4 border-t border-slate-100">
                {{ $notifications->links() }}
            </div>
        </section>
    </div>
</x-admin-layout>
