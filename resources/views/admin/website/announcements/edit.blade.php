<x-admin-layout title="Edit Announcement">
    <!-- Quill Rich Text Editor CDN -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

    <style>
        .ql-toolbar.ql-snow {
            border: 1px solid #e2e8f0 !important;
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
            background-color: #f8fafc;
            padding: 8px 12px !important;
        }
        .dark .ql-toolbar.ql-snow {
            border-color: #374151 !important;
            background-color: #111827;
        }
        .ql-container.ql-snow {
            border: 1px solid #e2e8f0 !important;
            border-bottom-left-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
            font-family: inherit;
        }
        .dark .ql-container.ql-snow {
            border-color: #374151 !important;
        }
        .ql-editor {
            min-height: 350px;
            font-size: 0.875rem !important;
            color: #1e293b;
            background-color: white;
        }
        .dark .ql-editor {
            color: #f8fafc;
            background-color: #111827;
        }
        .ql-editor.ql-blank::before {
            color: #94a3b8 !important;
            font-style: normal !important;
            left: 15px !important;
        }
    </style>

    <div class="max-w-3xl mx-auto">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <a href="{{ route('admin.website.announcements.index') }}" class="mb-2 inline-flex items-center gap-1 text-xs font-bold text-teal-700 hover:underline">
                    ← Back to Announcements
                </a>
                <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white font-outfit">
                    Edit Announcement
                </h1>
                <p class="mt-1 text-sm text-slate-500">Update school website post: <span class="font-extrabold text-slate-900 dark:text-white uppercase">{{ $announcement->title }}</span></p>
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200/70 dark:border-gray-700/50 p-6 shadow-sm">
            <form method="POST" action="{{ route('admin.website.announcements.update', $announcement->id) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Form Validation Errors -->
            @if ($errors->any())
                <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border border-rose-250 dark:border-rose-900 rounded-xl text-rose-800 dark:text-rose-350 text-xs font-bold space-y-1">
                    @foreach ($errors->all() as $error)
                        <div>• {{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <!-- Title -->
            <div class="space-y-1.5">
                <label for="title" class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Announcement Title</label>
                <input type="text" id="title" name="title" value="{{ old('title', $announcement->title) }}" required class="w-full rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-3 text-sm text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            </div>

            <!-- Meta details (Grid) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Category -->
                <div class="space-y-1.5">
                    <label for="category_select" class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Category</label>
                    <select id="category_select" onchange="toggleCustomCategory(this)" class="w-full rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-3 text-sm text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        <option value="AMIS News" @selected(old('category', $announcement->category) === 'AMIS News')>AMIS News</option>
                        <option value="Event" @selected(old('category', $announcement->category) === 'Event')>Event</option>
                        <option value="Announcement" @selected(old('category', $announcement->category) === 'Announcement')>Announcement</option>
                        <option value="Important Advisory" @selected(old('category', $announcement->category) === 'Important Advisory')>Important Advisory</option>
                        <option value="Good News" @selected(old('category', $announcement->category) === 'Good News')>Good News</option>
                        <option value="custom" @selected(old('category', $announcement->category) && !in_array(old('category', $announcement->category), ['AMIS News', 'Event', 'Announcement', 'Important Advisory', 'Good News']))>Custom Category...</option>
                    </select>
                    
                    @php
                        $isCustom = old('category', $announcement->category) && !in_array(old('category', $announcement->category), ['AMIS News', 'Event', 'Announcement', 'Important Advisory', 'Good News']);
                    @endphp
                    <input type="text" id="category" name="category" value="{{ old('category', $announcement->category) }}" required class="w-full rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-3 text-sm text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent mt-2 {{ $isCustom ? '' : 'hidden' }}" placeholder="Type custom category name...">
                </div>

                <!-- Priority -->
                <div class="space-y-1.5">
                    <label for="priority" class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Priority</label>
                    <select id="priority" name="priority" required class="w-full rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-3 text-sm text-slate-850 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        <option value="normal" @selected(old('priority', $announcement->priority) === 'normal')>Normal Priority</option>
                        <option value="high" @selected(old('priority', $announcement->priority) === 'high')>High / Featured</option>
                    </select>
                </div>

                <!-- Author -->
                <div class="space-y-1.5">
                    <label for="author" class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Author Name</label>
                    <input type="text" id="author" name="author" value="{{ old('author', $announcement->author) }}" required class="w-full rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-3 text-sm text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                </div>

                <!-- Publish Date -->
                <div class="space-y-1.5">
                    <label for="publish_date" class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Publish Date / Timestamp</label>
                    <input type="datetime-local" id="publish_date" name="publish_date" value="{{ old('publish_date', optional($announcement->publish_date)->format('Y-m-d\TH:i')) }}" required class="w-full rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-3 text-sm text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                </div>
            </div>

            <!-- Event-specific details (only visible if Category is Event) -->
            <div id="event_details_container" class="p-5 bg-teal-50/50 dark:bg-teal-950/10 border border-teal-100 dark:border-teal-900/50 rounded-2xl space-y-4 {{ old('category', $announcement->category) === 'Event' ? '' : 'hidden' }}">
                <h3 class="text-sm font-bold text-teal-800 dark:text-teal-400 uppercase tracking-wider">Event Information (Optional)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Event Dates -->
                    <div class="space-y-1.5">
                        <label for="event_dates" class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Event Dates / Duration</label>
                        <input type="text" id="event_dates" name="event_dates" value="{{ old('event_dates', $announcement->event_dates) }}" placeholder="e.g. August 21–24, 2026 or Every Friday" class="w-full rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-3 text-sm text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    </div>
                    
                    <!-- Event Venue -->
                    <div class="space-y-1.5">
                        <label for="event_venue" class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Event Venue / Location</label>
                        <input type="text" id="event_venue" name="event_venue" value="{{ old('event_venue', $announcement->event_venue) }}" placeholder="e.g. Berjaya Times Square Hotel, Kuala Lumpur or Zoom" class="w-full rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-3 text-sm text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    </div>
                </div>
                
                <!-- Online Class / Virtual Toggle -->
                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" id="is_online" name="is_online" value="1" @checked(old('is_online', $announcement->is_online)) class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 h-4 w-4">
                    <label for="is_online" class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider cursor-pointer select-none">This is an Online Class / Virtual Event</label>
                </div>
            </div>

            <!-- Cover Image Preview & File Upload -->
            <div class="space-y-3">
                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider block">Cover Images / Posters</label>
                
                @php
                    $currentImages = json_decode($announcement->image, true);
                    if (!is_array($currentImages)) {
                        $currentImages = $announcement->image ? [$announcement->image] : [];
                    }
                @endphp

                <!-- Hidden Input to Track Remaining Images -->
                <input type="hidden" name="remaining_images" id="remaining_images" value="{{ json_encode($currentImages) }}">

                @if(!empty($currentImages))
                    <div class="flex flex-wrap gap-3 p-4 rounded-xl border border-slate-100 dark:border-gray-750 bg-slate-50/50 dark:bg-gray-900/50">
                        @foreach($currentImages as $img)
                            <div class="relative group border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-lg p-1">
                                <div class="w-24 h-16 rounded-lg overflow-hidden flex items-center justify-center bg-slate-100 dark:bg-gray-800">
                                    <img src="{{ str_starts_with($img, '/') ? 'https://amis.edu.ph' . $img : $img }}" alt="Current Cover" class="w-full h-full object-cover">
                                </div>
                                <button type="button" onclick="removeExistingImage('{{ $img }}', this)" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center text-[9px] font-bold cursor-pointer border border-white shadow-sm z-10" title="Delete image">
                                    ✕
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="space-y-1.5">
                    <label for="image_files" class="text-xs font-semibold text-slate-500 block">Upload New Cover Images (Optional, appends to current images)</label>
                    <input type="file" id="image_files" name="image_files[]" accept="image/*" multiple onchange="previewImages(event)" class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 dark:file:bg-teal-950/20 dark:file:text-teal-400 transition cursor-pointer">
                    <div id="image_previews" class="flex flex-wrap gap-3 mt-2"></div>
                    <p class="text-[10px] text-slate-400 font-light">Supported formats: JPG, PNG, WEBP. Max size per image: 10MB.</p>
                </div>
            </div>

            <!-- Content Description -->
            <div class="space-y-1.5">
                <label for="content" class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider block">Post Content / Details</label>
                
                <!-- Quill Editor Container -->
                <div class="w-full rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-900 focus-within:ring-2 focus-within:ring-teal-500 overflow-hidden">
                    <div id="editor" style="min-height: 350px;"></div>
                </div>

                <textarea id="content" name="content" class="hidden" required>{{ old('content', $announcement->content) }}</textarea>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-gray-750">
                <a href="{{ route('admin.website.announcements.index') }}" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-5 py-2.5 text-xs font-bold text-slate-700 dark:text-slate-300 transition hover:bg-slate-50 dark:hover:bg-slate-850 active:scale-[0.98] cursor-pointer">
                    Cancel
                </a>
                <button type="submit" class="rounded-xl text-white px-6 py-2.5 text-xs font-bold shadow-md transition active:scale-[0.98] cursor-pointer border-0" style="background-color: #0d9488;">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

    <script>
        function cleanTextContent() {
            const textarea = document.getElementById('content');
            let text = textarea.value;
            if (!text) return;
            
            // Normalize: Split by double line breaks (paragraphs), merge single line breaks within paragraphs
            const paragraphs = text.split(/\n\s*\n+/);
            const cleaned = paragraphs.map(p => {
                return p.replace(/\r?\n/g, ' ').replace(/\s+/g, ' ').trim();
            });
            
            textarea.value = cleaned.join('\n\n');
            
            if (window.showToast) {
                window.showToast('Text formatted successfully! Single line breaks merged.', 'success');
            }
        }

        let accumulatedFiles = [];

        function previewImages(event) {
            const input = event.target;
            const files = input.files;
            if (!files) return;
            
            // Add new files to accumulatedFiles if not already present
            Array.from(files).forEach(file => {
                if (!accumulatedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    accumulatedFiles.push(file);
                }
            });
            
            syncFileInput(input);
            renderPreviews();
        }

        function syncFileInput(input) {
            const dataTransfer = new DataTransfer();
            accumulatedFiles.forEach(file => {
                dataTransfer.items.add(file);
            });
            input.files = dataTransfer.files;
        }

        function renderPreviews() {
            const previewContainer = document.getElementById('image_previews');
            previewContainer.innerHTML = '';
            
            accumulatedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'w-24 h-16 rounded-lg overflow-hidden border border-slate-200 bg-slate-100 relative group';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-full object-cover">
                        <button type="button" onclick="removeSelectedFile(${index})" class="absolute top-1 right-1 w-5 h-5 rounded-full bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center text-[9px] font-bold cursor-pointer border-0 shadow-sm" title="Remove image">
                            ✕
                        </button>
                    `;
                    previewContainer.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
        }

        function removeSelectedFile(index) {
            accumulatedFiles.splice(index, 1);
            const input = document.getElementById('image_files');
            syncFileInput(input);
            renderPreviews();
        }

        function removeExistingImage(imagePath, button) {
            if (confirm('Are you sure you want to delete this image?')) {
                const input = document.getElementById('remaining_images');
                let remaining = JSON.parse(input.value);
                remaining = remaining.filter(path => path !== imagePath);
                input.value = JSON.stringify(remaining);
                button.closest('.relative').remove();
            }
        }

        // Initialize Quill Editor
        let quill;
        document.addEventListener('DOMContentLoaded', () => {
            const textarea = document.getElementById('content');
            if (textarea) {
                // Initialize Quill
                quill = new Quill('#editor', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline'],
                            ['clean']
                        ]
                    },
                    placeholder: 'Write the details, description, or announcement body text here...'
                });

                // Load initial content from textarea
                if (textarea.value) {
                    quill.clipboard.dangerouslyPasteHTML(textarea.value);
                }

                // Sync Quill changes to hidden textarea
                quill.on('text-change', () => {
                    if (quill.getText().trim().length === 0) {
                        textarea.value = '';
                    } else {
                        textarea.value = quill.root.innerHTML;
                    }
                });
            }
        });

        function toggleCustomCategory(select) {
            const customInput = document.getElementById('category');
            const eventContainer = document.getElementById('event_details_container');
            if (select.value === 'custom') {
                customInput.classList.remove('hidden');
                customInput.value = '';
                customInput.focus();
                eventContainer.classList.add('hidden');
            } else {
                customInput.classList.add('hidden');
                customInput.value = select.value;
                if (select.value === 'Event') {
                    eventContainer.classList.remove('hidden');
                } else {
                    eventContainer.classList.add('hidden');
                }
            }
        }
    </script>
</x-admin-layout>
