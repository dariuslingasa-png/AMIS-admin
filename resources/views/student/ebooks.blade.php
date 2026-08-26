<x-student-layout title="E-Books">

@php
    $gradients = [
        'linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%)', // Blue
        'linear-gradient(135deg, #064e3b 0%, #10b981 100%)', // Emerald
        'linear-gradient(135deg, #581c87 0%, #8b5cf6 100%)', // Purple
        'linear-gradient(135deg, #7c2d12 0%, #f97316 100%)', // Orange
        'linear-gradient(135deg, #831843 0%, #ec4899 100%)', // Pink
        'linear-gradient(135deg, #0f766e 0%, #0d9488 100%)', // Teal
    ];
@endphp

<div class="space-y-6">
    <!-- 1. Header Banner -->
    <div class="portal-card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-emerald-700 font-bold text-xs uppercase tracking-wider">
                <i data-lucide="book-open" class="h-4 w-4"></i>
                <span>Digital Library</span>
            </div>
            <h2 class="mt-1 font-heading text-2xl font-black text-slate-900">My Assigned E-Books</h2>
            <p class="text-xs font-medium text-slate-500">
                Official digital learning materials and textbooks for {{ $gradeLevel ?: 'Grade 1' }}.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="portal-badge portal-badge-emerald">
                {{ $ebooks->count() }} {{ $ebooks->count() === 1 ? 'Book' : 'Books' }} Available
            </span>
        </div>
    </div>

    <!-- 2. Books Grid -->
    @if($ebooks->isEmpty())
        <div class="portal-empty-state">
            <div class="portal-empty-icon">
                <i data-lucide="book-x" class="h-6 w-6"></i>
            </div>
            <h3 class="font-heading text-base font-bold text-slate-800">No E-Books Assigned</h3>
            <p class="text-xs text-slate-500 mt-1">There are currently no digital books published for your grade level.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach($ebooks as $book)
                @php
                    $gradient = $gradients[abs(crc32($book->title . $book->id)) % count($gradients)];
                @endphp
                <div class="portal-card p-4 flex flex-col justify-between hover:border-slate-300">
                    <div>
                        <!-- Cover Area -->
                        @if($book->cover_image_path)
                            <div class="h-56 rounded-xl overflow-hidden relative border border-slate-200 bg-slate-100 mb-3">
                                <img src="https://ebook.amis.edu.ph/storage/{{ $book->cover_image_path }}" 
                                     alt="{{ $book->title }}" 
                                     loading="lazy" 
                                     class="h-full w-full object-cover transition-transform duration-300 hover:scale-105">
                                <div class="absolute bottom-2 left-2">
                                    <span class="portal-badge portal-badge-emerald">
                                        {{ $book->grade_level }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="h-56 rounded-xl p-4 flex flex-col justify-between text-white relative overflow-hidden mb-3 shadow-xs" style="background: {{ $gradient }};">
                                <div class="space-y-1">
                                    <span class="text-[10px] font-extrabold uppercase tracking-widest opacity-80">Digital Textbook</span>
                                    <h4 class="font-heading text-sm font-black line-clamp-3 leading-snug">{{ $book->title }}</h4>
                                </div>
                                <div class="pt-2 border-t border-white/20 text-xs">
                                    <p class="font-bold truncate opacity-90">{{ $book->author ?: 'AMIS Faculty' }}</p>
                                    <span class="text-[10px] opacity-75 font-semibold">{{ $book->grade_level }}</span>
                                </div>
                            </div>
                        @endif

                        <!-- Book Meta -->
                        <div>
                            <h3 class="font-heading text-sm font-extrabold text-slate-900 line-clamp-2 leading-snug" title="{{ $book->title }}">
                                {{ $book->title }}
                            </h3>
                            <p class="text-xs font-semibold text-emerald-700 mt-1 truncate">
                                By {{ $book->author ?: 'AMIS Faculty' }}
                            </p>
                        </div>
                    </div>

                    <!-- Action: Read Online -->
                    <div class="mt-4 pt-3 border-t border-slate-100">
                        <a href="{{ route('student.ebooks.read', $book->id) }}" target="_blank" 
                           class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-700 px-3 py-2 text-xs font-bold text-white transition hover:bg-emerald-800">
                            <i data-lucide="book-open" class="h-3.5 w-3.5"></i>
                            <span>Read E-Book</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

</x-student-layout>
