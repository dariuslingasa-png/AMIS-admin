<x-app-layout :title="$title ?? 'Dashboard'">
    @include('partials.topbar')

    <div class="admin-shell">
        @include('partials.sidebar')

        <div class="admin-content bg-gray-50 dark:bg-gray-900">
            <main class="p-4 md:p-6">
                <div class="mx-auto max-w-screen-2xl">
                    @include('partials.flash')
                    @include('partials.breadcrumbs')
                    
                    <div id="adminMainContent">
                        {{ $slot }}
                    </div>

                    <!-- Global Skeleton Loader (hidden by default) -->
                    <div id="globalSkeleton" class="hidden space-y-6">
                        <!-- Banner Header Skeleton -->
                        <div class="animate-pulse rounded-3xl bg-slate-200 p-6 h-28">
                            <div class="h-4 w-32 bg-slate-350 rounded mb-3"></div>
                            <div class="h-6 w-64 bg-slate-350 rounded mb-2"></div>
                            <div class="h-3.5 w-full max-w-lg bg-slate-300 rounded"></div>
                        </div>

                        <!-- Stats/Telemetry Grid Skeleton -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 animate-pulse">
                            @for ($i = 0; $i < 4; $i++)
                                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs h-28 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="h-3 w-20 bg-slate-200 rounded"></div>
                                        <div class="h-6 w-6 bg-slate-100 rounded-lg"></div>
                                    </div>
                                    <div class="h-6 w-24 bg-slate-200 rounded"></div>
                                    <div class="h-3 w-16 bg-slate-100 rounded"></div>
                                </div>
                            @endfor
                        </div>

                        <!-- Table/Main Container Skeleton -->
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs animate-pulse space-y-5">
                            <div class="flex items-center justify-between">
                                <div class="h-8 w-48 bg-slate-200 rounded-xl"></div>
                                <div class="h-8 w-32 bg-slate-200 rounded-xl"></div>
                            </div>
                            <div class="h-px bg-slate-100"></div>
                            <div class="space-y-4">
                                @for ($i = 0; $i < 5; $i++)
                                    <div class="flex items-center justify-between py-3 border-b border-slate-100">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-xl bg-slate-200"></div>
                                            <div class="space-y-2">
                                                <div class="h-4 w-36 bg-slate-200 rounded"></div>
                                                <div class="h-3 w-24 bg-slate-150 rounded"></div>
                                            </div>
                                        </div>
                                        <div class="h-4 w-28 bg-slate-200 rounded"></div>
                                        <div class="h-4 w-20 bg-slate-150 rounded"></div>
                                        <div class="h-8 w-20 bg-slate-100 rounded-xl"></div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            @include('partials.footer')
        </div>
    </div>

    <script>
        function showGlobalSkeleton() {
            const mainContent = document.getElementById('adminMainContent');
            const globalSkeleton = document.getElementById('globalSkeleton');
            if (mainContent && globalSkeleton) {
                mainContent.classList.add('hidden');
                globalSkeleton.classList.remove('hidden');
            }
        }

        // Intercept clicks on links for page transitions
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link) return;

            // Ignore links with target="_blank"
            if (link.getAttribute('target') === '_blank') return;

            // Ignore download links
            if (link.hasAttribute('download')) return;

            const href = link.getAttribute('href');
            if (!href) return;

            // Ignore empty/javascript/anchor links
            if (href === '#' || href.startsWith('javascript:') || href.startsWith('#')) return;

            // Ignore clicks with modifier keys (Ctrl, Cmd, Shift, Alt)
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            showGlobalSkeleton();
        });

        // Intercept search/filter form submissions
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.getAttribute('target') === '_blank') return;
            showGlobalSkeleton();
        });

        // Reset skeleton state when navigating back/forward (handling bfcache)
        window.addEventListener('pageshow', function(event) {
            const mainContent = document.getElementById('adminMainContent');
            const globalSkeleton = document.getElementById('globalSkeleton');
            if (mainContent && globalSkeleton) {
                mainContent.classList.remove('hidden');
                globalSkeleton.classList.add('hidden');
            }
        });
    </script>
</x-app-layout>
