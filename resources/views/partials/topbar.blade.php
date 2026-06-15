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
                <a href="{{ route(Auth::user()->adminHomeRouteName()) }}" class="ms-2 flex items-center md:me-24">
                    <img src="{{ asset('images/AMIS_Logo.svg') }}" class="me-3 h-8 w-8 object-contain" alt="AMIS Logo">
                    <span class="self-center whitespace-nowrap text-xl font-semibold dark:text-white">AMIS Admin Portal</span>
                </a>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
                    <span class="sr-only">Notifications</span>
                    <i data-lucide="bell" class="h-5 w-5"></i>
                </button>
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
                        <li><a href="{{ route(Auth::user()->adminHomeRouteName()) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600">Dashboard</a></li>
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
