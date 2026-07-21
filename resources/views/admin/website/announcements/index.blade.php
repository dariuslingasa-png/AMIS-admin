<x-admin-layout title="Website Announcements CMS">
    <div class="relative overflow-hidden p-6 md:p-8 rounded-2xl border border-teal-700/30 shadow-sm text-white mb-6" style="background: linear-gradient(135deg, #0f766e 0%, #064e3b 100%);">
        <div class="absolute right-0 top-0 -mt-4 -mr-4 w-56 h-56 rounded-full bg-teal-500/10 blur-3xl"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full border border-teal-500/30 backdrop-blur-xs mb-3" style="background-color: rgba(20, 184, 166, 0.2); color: #99f6e4;">
                    <i data-lucide="megaphone" class="w-3.5 h-3.5"></i>
                    Website CMS
                </span>
                <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-white font-outfit">School Website Announcements</h1>
                <p class="mt-2 text-sm text-teal-100 max-w-2xl font-light">
                    Manage the public announcements, news posts, events, and bulletins displayed on the school's primary website (<a href="https://amis.edu.ph" target="_blank" class="underline hover:text-white transition">amis.edu.ph</a>).
                </p>
            </div>
            <div>
                <a href="{{ route('admin.website.announcements.create') }}" class="inline-flex items-center gap-2 rounded-xl text-white font-bold text-xs px-4 py-3 shadow-md transition active:scale-[0.98] cursor-pointer" style="background-color: #0d9488;">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Create Announcement</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200/70 dark:border-gray-700/50 p-6 shadow-sm">
        <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-150 dark:border-gray-750">
            <div>
                <h2 class="text-base font-semibold text-slate-950 dark:text-white">Active Posts</h2>
                <p class="mt-1 text-xs text-slate-500">Currently published or scheduled school website content</p>
            </div>
            <form method="GET" action="{{ route('admin.website.announcements.index') }}" class="flex items-center gap-3">
                <label class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400"></i>
                    <input type="search" name="search" value="{{ $search }}" placeholder="Search announcements..." class="table-control pl-9 text-xs">
                </label>
                @if(filled($search))
                    <a href="{{ route('admin.website.announcements.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-bold px-2">Clear</a>
                @endif
                <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">Filter</button>
            </form>
        </div>

        @if(session('success'))
            <div class="mb-5 p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-250 dark:border-emerald-900 rounded-xl text-emerald-800 dark:text-emerald-350 text-xs font-bold flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="premium-table-wrap border border-slate-150 dark:border-slate-750 rounded-xl overflow-hidden">
            <table class="premium-table w-full">
                <thead>
                    <tr>
                        <th class="px-5 py-3">Image</th>
                        <th class="px-5 py-3">Title & Summary</th>
                        <th class="px-5 py-3 text-center">Category</th>
                        <th class="px-5 py-3 text-center">Priority</th>
                        <th class="px-5 py-3">Publish Date</th>
                        <th class="px-5 py-3">Author</th>
                        <th class="px-5 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($announcements as $announcement)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-850/50 transition">
                            <td class="px-5 py-4">
                                <div class="w-16 h-12 rounded-lg overflow-hidden border border-slate-200 bg-slate-100 flex items-center justify-center">
                                    @php
                                        $imgs = json_decode($announcement->image, true);
                                        $firstImg = is_array($imgs) ? ($imgs[0] ?? null) : $announcement->image;
                                    @endphp
                                    @if($firstImg)
                                        <img src="{{ str_starts_with($firstImg, '/') ? 'https://amis.edu.ph' . $firstImg : $firstImg }}" alt="Cover" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-[9px] text-slate-400 font-bold uppercase">No Image</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-extrabold text-slate-950 dark:text-white text-sm uppercase tracking-wide">{{ $announcement->title }}</div>
                                <div class="mt-1 text-xs text-slate-500 line-clamp-2 max-w-md font-light">{{ $announcement->content }}</div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 border border-slate-200/50">
                                    {{ $announcement->category ?: 'General' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if($announcement->priority === 'high')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-350 border border-rose-150">
                                        High
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-50 dark:bg-gray-800 text-slate-500 dark:text-gray-450 border border-slate-200">
                                        Normal
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                {{ optional($announcement->publish_date)->format('M d, Y') ?: '-' }}
                            </td>
                            <td class="px-5 py-4 text-xs font-bold text-slate-800 dark:text-slate-200">
                                {{ $announcement->author ?: '-' }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.website.announcements.edit', $announcement->id) }}" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-650 text-slate-700 dark:text-slate-200 transition cursor-pointer" title="Edit Post">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.website.announcements.destroy', $announcement->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-955/20 dark:hover:bg-rose-955/40 text-rose-700 dark:text-rose-400 transition cursor-pointer border-0" title="Delete Post">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center p-4">
                                    <i data-lucide="inbox" class="h-8 w-8 text-slate-300 mb-2"></i>
                                    <p class="font-bold text-sm">No announcements found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $announcements->links() }}
        </div>
    </div>
</x-admin-layout>
