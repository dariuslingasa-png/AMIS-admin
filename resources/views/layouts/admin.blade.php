<x-app-layout :title="$title ?? 'Dashboard'">
    <!-- Top Loading Progress Bar -->
    <div id="topLoadingBar" class="fixed top-0 left-0 h-1 bg-emerald-600 dark:bg-emerald-500 z-150 transition-all duration-300 ease-out" style="width: 0%; display: none;"></div>

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
                    <div id="globalSkeleton" class="space-y-6" style="display: none;">
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
        let topLoadingTimer = null;
        let skeletonTimer = null;

        function startLoadingTransition() {
            // Reset any active timers
            clearInterval(topLoadingTimer);
            clearTimeout(skeletonTimer);

            const topBar = document.getElementById('topLoadingBar');
            if (topBar) {
                topBar.style.display = 'block';
                topBar.style.width = '0%';
                topBar.style.transition = 'width 0.2s ease-out';
                // Immediate tiny jump
                setTimeout(() => { topBar.style.width = '15%'; }, 10);

                // Asymptotically progress the top loading bar up to 90%
                let width = 15;
                topLoadingTimer = setInterval(() => {
                    if (width < 90) {
                        width += (90 - width) * 0.1;
                        topBar.style.width = width + '%';
                    }
                }, 150);
            }

            // Delay the main content skeleton loader by 250ms to prevent flickering on fast loads
            skeletonTimer = setTimeout(() => {
                const mainContent = document.getElementById('adminMainContent');
                const globalSkeleton = document.getElementById('globalSkeleton');
                if (mainContent && globalSkeleton) {
                    mainContent.style.opacity = '0.3';
                    mainContent.style.transition = 'opacity 0.2s ease';
                    
                    globalSkeleton.style.display = 'block';
                    globalSkeleton.style.opacity = '0';
                    globalSkeleton.style.transition = 'opacity 0.2s ease';
                    setTimeout(() => {
                        mainContent.style.display = 'none';
                        globalSkeleton.style.opacity = '1';
                    }, 50);
                }
            }, 250);
        }

        function stopLoadingTransition() {
            clearInterval(topLoadingTimer);
            clearTimeout(skeletonTimer);

            const topBar = document.getElementById('topLoadingBar');
            if (topBar) {
                topBar.style.width = '100%';
                setTimeout(() => {
                    topBar.style.display = 'none';
                    topBar.style.width = '0%';
                }, 200);
            }

            const mainContent = document.getElementById('adminMainContent');
            const globalSkeleton = document.getElementById('globalSkeleton');
            if (mainContent && globalSkeleton) {
                mainContent.style.display = 'block';
                mainContent.style.opacity = '1';
                globalSkeleton.style.display = 'none';
            }
        }

        // Global Toast Notification System
        window.showToast = function(message, type = 'info') {
            let container = document.getElementById('toastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toastContainer';
                container.className = 'fixed bottom-5 right-5 z-100 flex flex-col gap-3 max-w-md w-full sm:w-96 pointer-events-none';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto flex items-center gap-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-lg transition-all duration-300 transform translate-x-12 opacity-0';
            
            let borderClass = 'border-l-4 border-indigo-500';
            let iconBgClass = 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400';
            let iconSvg = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

            if (type === 'success') {
                borderClass = 'border-l-4 border-emerald-500';
                iconBgClass = 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400';
                iconSvg = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            } else if (type === 'error' || type === 'danger') {
                borderClass = 'border-l-4 border-rose-500';
                iconBgClass = 'bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-400';
                iconSvg = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
            } else if (type === 'warning') {
                borderClass = 'border-l-4 border-amber-500';
                iconBgClass = 'bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400';
                iconSvg = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
            }

            toast.className += ' ' + borderClass;

            toast.innerHTML = `
                <div class="p-1 rounded-full ${iconBgClass}">
                    ${iconSvg}
                </div>
                <div class="flex-1 text-sm font-semibold text-slate-800 dark:text-slate-250">
                    ${message}
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer toast-close">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('translate-x-12', 'opacity-0');
                toast.classList.add('translate-x-0', 'opacity-100');
            }, 10);

            const closeBtn = toast.querySelector('.toast-close');
            const dismissToast = () => {
                toast.classList.remove('translate-x-0', 'opacity-100');
                toast.classList.add('translate-x-12', 'opacity-0');
                setTimeout(() => { toast.remove(); }, 300);
            };

            closeBtn.addEventListener('click', dismissToast);
            setTimeout(dismissToast, 5000);
        };

        // Override native window.alert to use Toast Notifications
        window.alert = function(msg) {
            const lower = String(msg).toLowerCase();
            const type = (lower.includes('fail') || lower.includes('error') || lower.includes('wrong') || lower.includes('invalid')) ? 'error' : 'info';
            window.showToast(msg, type);
        };

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

            // Ignore empty/javascript/anchor/telephony links
            if (href === '#' || href.startsWith('javascript:') || href.startsWith('#') || href.startsWith('tel:') || href.startsWith('mailto:')) return;

            // Ignore external links
            if (href.startsWith('http') && !href.startsWith(window.location.origin)) return;

            // Ignore clicks with modifier keys (Ctrl, Cmd, Shift, Alt)
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            startLoadingTransition();
        });

        // Intercept search/filter form submissions & add action spinners
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.getAttribute('target') === '_blank') return;

            // HTML5 Form Validation Check
            if (form.checkValidity && !form.checkValidity()) {
                return;
            }

            // Disable submit buttons and prepend spinner
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach(button => {
                button.disabled = true;
                button.classList.add('opacity-75', 'cursor-not-allowed');

                if (!button.querySelector('.btn-spinner')) {
                    if (button.tagName === 'BUTTON') {
                        const spinnerSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                        spinnerSvg.setAttribute('class', 'animate-spin -ml-1 mr-2 h-4 w-4 text-current inline btn-spinner');
                        spinnerSvg.setAttribute('fill', 'none');
                        spinnerSvg.setAttribute('viewBox', '0 0 24 24');
                        
                        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                        circle.setAttribute('class', 'opacity-25');
                        circle.setAttribute('cx', '12');
                        circle.setAttribute('cy', '12');
                        circle.setAttribute('r', '10');
                        circle.setAttribute('stroke', 'currentColor');
                        circle.setAttribute('stroke-width', '4');
                        
                        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        path.setAttribute('class', 'opacity-75');
                        path.setAttribute('fill', 'currentColor');
                        path.setAttribute('d', 'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z');
                        
                        spinnerSvg.appendChild(circle);
                        spinnerSvg.appendChild(path);
                        button.insertBefore(spinnerSvg, button.firstChild);
                    } else if (button.tagName === 'INPUT') {
                        if (!button.hasAttribute('data-original-value')) {
                            button.setAttribute('data-original-value', button.value);
                        }
                        button.value = 'Processing...';
                    }
                }
            });

            startLoadingTransition();
        });

        // Restore focus to search input after reload if it was debounced
        document.addEventListener('DOMContentLoaded', function() {
            if (sessionStorage.getItem('restoreSearchFocus') === 'true') {
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput) {
                    searchInput.focus();
                    const start = parseInt(sessionStorage.getItem('restoreSearchCursorStart') || searchInput.value.length);
                    const end = parseInt(sessionStorage.getItem('restoreSearchCursorEnd') || searchInput.value.length);
                    try {
                        searchInput.setSelectionRange(start, end);
                    } catch(e) {}
                }
                sessionStorage.removeItem('restoreSearchFocus');
                sessionStorage.removeItem('restoreSearchCursorStart');
                sessionStorage.removeItem('restoreSearchCursorEnd');
            }

            // Debounce search inputs globally
            document.querySelectorAll('input[name="search"]').forEach(searchInput => {
                const form = searchInput.form;
                if (!form) return;

                let debounceTimer = null;
                const originalValue = searchInput.value;

                searchInput.addEventListener('input', function() {
                    if (searchInput.value === originalValue) return;

                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        sessionStorage.setItem('restoreSearchFocus', 'true');
                        sessionStorage.setItem('restoreSearchCursorStart', searchInput.selectionStart);
                        sessionStorage.setItem('restoreSearchCursorEnd', searchInput.selectionEnd);

                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                        } else {
                            const submitBtn = form.querySelector('button[type="submit"]') || form.querySelector('input[type="submit"]');
                            if (submitBtn) {
                                submitBtn.click();
                            } else {
                                form.submit();
                            }
                        }
                    }, 400);
                });

                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        clearTimeout(debounceTimer);
                    }
                });
            });
        });

        // Reset skeleton & button state when navigating back/forward (handling bfcache)
        window.addEventListener('pageshow', function(event) {
            stopLoadingTransition();

            // Reset submit buttons
            document.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(button => {
                button.disabled = false;
                button.classList.remove('opacity-75', 'cursor-not-allowed');
                const spinner = button.querySelector('.btn-spinner');
                if (spinner) {
                    spinner.remove();
                }
                if (button.tagName === 'INPUT' && button.value === 'Processing...') {
                    button.value = button.getAttribute('data-original-value') || 'Submit';
                }
            });
        });
    </script>
</x-app-layout>
