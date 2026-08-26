<x-student-layout title="Ebooks">
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

<div class="space-y-6" x-data="{}" x-init="window.lucide && window.lucide.createIcons()">
    <!-- Header banner -->
    <div class="s-quick-actions-card" style="padding: 1.75rem; background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.8rem; font-weight: 850; color: #be185d; background: #fdf2f8; border: 1px solid #fbcfe8; padding: 0.25rem 0.65rem; border-radius: 999px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                <span>My Grade Library</span>
            </div>
            <h2 style="font-size: 1.5rem; font-weight: 900; color: #0f172a; margin: 0.5rem 0 0.25rem;">My Assigned E-Books</h2>
            <p style="font-size: 0.9rem; font-weight: 700; color: #475569; margin: 0;">Access and read eBooks assigned specifically to your grade level ({{ $gradeLevel }}).</p>
        </div>
        
        <div style="background: #fdf2f8; border: 1.5px solid #fbcfe8; border-radius: 14px; padding: 0.75rem 1.5rem; text-align: center; min-width: 120px; flex-shrink: 0;">
            <p style="font-size: 0.75rem; font-weight: 850; color: #9d174d; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Books Available</p>
            <p style="font-size: 1.75rem; font-weight: 800; font-family: 'Inter', sans-serif; color: #be185d; margin: 0; margin-top: 0.25rem; line-height: 1;">{{ $ebooks->count() }}</p>
        </div>
    </div>

    @if($ebooks->isEmpty())
        <!-- Empty State -->
        <div class="s-empty-card" style="border: 1.5px solid #e2e8f0; box-shadow: none;">
            <div class="s-empty-icon-wrapper" style="background: #fdf2f8; border-color: #fbcfe8;">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#be185d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-x"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="m14.5 13.5-5-5"/><path d="m9.5 13.5 5-5"/></svg>
            </div>
            <h3 class="s-empty-title">No E-Books Assigned</h3>
            <p class="s-empty-text">There are currently no eBooks assigned to your grade level ({{ $gradeLevel }}). Please check back later or contact your advisor.</p>
        </div>
    @else
        <!-- eBooks Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(265px, 1fr)); gap: 1.5rem;">
            @foreach($ebooks as $book)
                @php
                    $gradient = $gradients[abs(crc32($book->title . $book->id)) % count($gradients)];
                @endphp
                <div class="s-quick-actions-card fade-up" style="background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; height: 100%; transition: transform 0.2s, box-shadow 0.2s;" 
                     onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 20px rgba(0,0,0,0.06)'"
                     onmouseout="this.style.transform='none'; this.style.boxShadow='none'">
                    
                    <div>
                        <!-- Cover Area -->
                        @if($book->cover_image_path)
                            <div style="height: 240px; border-radius: 12px; overflow: hidden; position: relative; border: 1px solid #e2e8f0; background: #f8fafc;">
                                <img src="https://ebook.amis.edu.ph/storage/{{ $book->cover_image_path }}" 
                                     alt="{{ $book->title }}" 
                                     loading="lazy" 
                                     style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;"
                                     onmouseover="this.style.transform='scale(1.05)'"
                                     onmouseout="this.style.transform='none'">
                                <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 100%); padding: 1rem 1rem 0.5rem; color: white;">
                                    <span style="font-size: 0.65rem; font-weight: 850; background: #be185d; padding: 0.15rem 0.45rem; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em;">
                                        {{ $book->grade_level }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <!-- Stylized Book Placeholder -->
                            <div style="height: 240px; background: {{ $gradient }}; border-radius: 12px; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; padding: 1.5rem; color: white; box-shadow: inset 5px 0 10px rgba(0,0,0,0.2), 0 4px 12px rgba(0,0,0,0.1);">
                                <div style="position: absolute; top: 0; left: 0; bottom: 0; width: 12px; background: rgba(0,0,0,0.15); box-shadow: 1px 0 2px rgba(255,255,255,0.15) inset;"></div>
                                
                                <div style="margin-left: 8px;">
                                    <div style="font-size: 0.75rem; font-weight: 850; text-transform: uppercase; letter-spacing: 0.1em; opacity: 0.8; margin-bottom: 0.25rem;">E-BOOK</div>
                                    <div style="font-size: 1.15rem; font-weight: 900; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical;">
                                        {{ $book->title }}
                                    </div>
                                </div>
                                
                                <div style="margin-left: 8px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 0.75rem;">
                                    <div style="font-size: 0.8rem; font-weight: 750; opacity: 0.9; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $book->author ?: 'AMIS Faculty' }}
                                    </div>
                                    <div style="font-size: 0.7rem; font-weight: 800; opacity: 0.7; margin-top: 2px;">
                                        {{ $book->grade_level }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Metadata -->
                        <div style="margin-top: 1rem;">
                            <h3 style="font-size: 1.1rem; font-weight: 900; color: #0f172a; margin: 0; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.6rem;" title="{{ $book->title }}">
                                {{ $book->title }}
                            </h3>
                            
                            <p style="font-size: 0.825rem; font-weight: 750; color: #be185d; margin: 0.25rem 0 0.5rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                By {{ $book->author ?: 'AMIS Faculty' }}
                            </p>
                            
                            <p style="font-size: 0.8rem; font-weight: 650; color: #475569; margin: 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; min-height: 3.3rem;">
                                {{ $book->description ?: 'No description available for this eBook.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div style="margin-top: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        <a href="{{ route('student.ebooks.read', $book->id) }}"
                           style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.7rem 1rem; border-radius: 10px; background: #be185d; color: white; font-size: 0.9rem; font-weight: 800; text-decoration: none; text-align: center; transition: background 0.15s;"
                           onmouseover="this.style.background='#9d174d'"
                           onmouseout="this.style.background='#be185d'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                            <span>Read E-Book</span>
                        </a>

                        @if($book->is_downloadable)
                            <a href="{{ route('student.ebooks.stream', $book->id) }}?download=1" target="_blank"
                               style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.65rem 1rem; border-radius: 10px; border: 1.5px solid #cbd5e1; background: white; color: #475569; font-size: 0.85rem; font-weight: 800; text-decoration: none; text-align: center; transition: all 0.15s;"
                               onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8'; this.style.color='#0f172a'"
                               onmouseout="this.style.background='white'; this.style.borderColor='#cbd5e1'; this.style.color='#475569'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                <span>Download PDF</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
</x-student-layout>
