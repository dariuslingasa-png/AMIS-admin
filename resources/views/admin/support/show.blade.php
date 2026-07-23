<x-admin-layout
    title="Ticket Details"
    :breadcrumbs="[
        ['label' => 'Support Center', 'href' => route('admin.support.index')],
        ['label' => $ticket->reference_number, 'href' => null],
    ]"
>
    <!-- Page back link -->
    <div class="mb-5 flex justify-between items-center">
        <div>
            <span class="text-xs font-extrabold uppercase tracking-wider text-rose-700 dark:text-rose-400">Support Center Workspace</span>
        </div>
        <a href="{{ route('admin.support.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98]">
            <i data-lucide="chevron-left" class="h-4 w-4"></i>
            Back to directory
        </a>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <!-- Alpine.js wrapper for Preview Modal -->
    <div class="support-ticket-detail" x-data="{
         preview: false,
         src: '',
         label: '',
         pdf: false,
         zoom: 1,
         panning: false,
         panEl: null,
         panX: 0,
         panY: 0,
         panLeft: 0,
         panTop: 0,
         openPreview(url, title, isPdf) {
             this.preview = true;
             this.src = url;
             this.label = title;
             this.pdf = isPdf;
             this.zoom = 1;
         },
         closePreview() {
             this.preview = false;
             this.zoom = 1;
             this.stopPan();
         },
         zoomIn() {
             this.zoom = Math.min(3, Number((this.zoom + 0.1).toFixed(2)));
         },
         zoomOut() {
             this.zoom = Math.max(0.1, Number((this.zoom - 0.1).toFixed(2)));
         },
         resetZoom() {
             this.zoom = 1;
         },
         startPan(event) {
             if (this.pdf) return;
             const point = event.touches ? event.touches[0] : event;
             this.panning = true;
             this.panEl = event.currentTarget;
             this.panX = point.pageX;
             this.panY = point.pageY;
             this.panLeft = this.panEl.scrollLeft;
             this.panTop = this.panEl.scrollTop;
             this.panEl.classList.add('cursor-grabbing');
         },
         movePan(event) {
             if (!this.panning || !this.panEl) return;
             event.preventDefault();
             const point = event.touches ? event.touches[0] : event;
             this.panEl.scrollLeft = this.panLeft - (point.pageX - this.panX);
             this.panEl.scrollTop = this.panTop - (point.pageY - this.panY);
         },
         stopPan() {
             if (this.panEl) this.panEl.classList.remove('cursor-grabbing');
             this.panning = false;
             this.panEl = null;
         },
         async downloadPdf() {
             if (!this.src) return;
             const link = document.createElement('a');
             link.href = this.src;
             link.download = this.label || 'screenshot';
             document.body.appendChild(link);
             link.click();
             document.body.removeChild(link);
         }
    }"
    x-effect="document.body.classList.toggle('overflow-hidden', preview)"
    @keydown.escape.window="closePreview()"
    @mouseup.window="stopPan()"
    @touchend.window="stopPan()">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content: Ticket Details (Left Column) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Ticket Header Info -->
                <div class="relative overflow-hidden p-6 bg-gradient-to-r from-rose-800 to-rose-950 rounded-2xl border border-rose-900 shadow-sm text-white">
                    <div class="absolute right-0 top-0 -mt-4 -mr-4 w-40 h-40 rounded-full bg-rose-500/10 blur-2xl"></div>
                    <div class="relative z-10">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-rose-500/20 text-rose-300 rounded-full border border-rose-500/30 backdrop-blur-xs mb-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                            Ticket Reference
                        </span>
                        <h1 class="text-3xl font-extrabold tracking-tight text-white">{{ $ticket->reference_number }}</h1>
                        <p class="mt-2 text-sm text-rose-100 max-w-xl font-light">
                            Submitted on {{ $ticket->created_at ? $ticket->created_at->format('F d, Y \a\t h:i A') : 'N/A' }}
                        </p>
                    </div>
                </div>

                <!-- Submitter Information Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 p-6 shadow-sm">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-rose-700 mb-4 flex items-center gap-2">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        Submitter Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase">Full Name</span>
                            <span class="block mt-1 font-bold text-slate-800 uppercase tracking-wide">{{ $ticket->full_name }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase">Email Address</span>
                            <a href="mailto:{{ $ticket->email }}" class="block mt-1 font-bold text-rose-600 hover:text-rose-800 hover:underline">
                                {{ $ticket->email }}
                            </a>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase">Contact Number</span>
                            <span class="block mt-1 font-bold text-slate-850">{{ $ticket->contact_number ?: 'Not provided' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase">FB Profile / WhatsApp</span>
                            @if($ticket->fb_or_whatsapp)
                                @if(str_starts_with(strtolower($ticket->fb_or_whatsapp), 'http') || str_starts_with(strtolower($ticket->fb_or_whatsapp), 'www.'))
                                    <a href="{{ str_starts_with(strtolower($ticket->fb_or_whatsapp), 'http') ? $ticket->fb_or_whatsapp : 'https://' . $ticket->fb_or_whatsapp }}" target="_blank" class="block mt-1 font-bold text-rose-600 hover:text-rose-800 hover:underline flex items-center gap-1">
                                        <i data-lucide="external-link" class="w-3.5 h-3.5 inline"></i> Open Link
                                    </a>
                                @else
                                    <span class="block mt-1 font-bold text-slate-850">{{ $ticket->fb_or_whatsapp }}</span>
                                @endif
                            @else
                                <span class="block mt-1 text-slate-400 italic">Not provided</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Student Context Card (conditional) -->
                @if($ticket->student_full_name)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 p-6 shadow-sm">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-rose-700 mb-4 flex items-center gap-2">
                            <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                            Student Context
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <span class="block text-xs font-bold text-slate-400 uppercase">Student Name</span>
                                <span class="block mt-1 font-bold text-slate-800 uppercase tracking-wide">{{ $ticket->student_full_name }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-bold text-slate-400 uppercase">Grade Level</span>
                                <span class="block mt-1 font-bold text-slate-800">{{ $ticket->grade_level ?: 'Not provided' }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-bold text-slate-400 uppercase">AMIS Student ID</span>
                                <span class="block mt-1 font-mono font-bold text-slate-850">{{ $ticket->amis_id ?: 'Not provided' }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Ticket Content Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 p-6 shadow-sm">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-rose-700 mb-4 flex items-center gap-2">
                        <i data-lucide="help-circle" class="w-4 h-4"></i>
                        Inquiry Concern details
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase">Concern Category</span>
                            <span class="inline-flex items-center mt-1.5 rounded-md bg-slate-100 dark:bg-slate-700 px-2.5 py-1 text-xs font-extrabold uppercase text-slate-700 dark:text-slate-200 tracking-wider">
                                {{ $ticket->concern_type }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase">Subject</span>
                            <span class="block mt-1 text-lg font-bold text-slate-900 leading-snug">{{ $ticket->subject }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase mb-2">Description / Message</span>
                            <div class="p-5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-150 dark:border-slate-800 text-sm leading-relaxed text-slate-800 dark:text-slate-200 whitespace-pre-wrap font-sans">
                                {{ $ticket->description }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Reply & Image Attachment Upload Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-rose-200 dark:border-rose-900/50 p-6 shadow-md shadow-rose-900/5">
                    <h2 class="text-sm font-black uppercase tracking-wider text-rose-700 dark:text-rose-400 mb-4 flex items-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Send Reply Email (Auto-Failover Multi-SMTP)
                    </h2>

                    <form method="POST" action="{{ route('admin.support.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1">Email Subject</label>
                            <input type="text" name="subject" value="Re: {{ $ticket->subject }}" required
                                   class="w-full h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100">
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1">Message Content</label>
                            <textarea name="message" rows="5" required placeholder="Type your response to {{ $ticket->full_name }} here..."
                                      class="w-full rounded-xl border border-slate-200 bg-white p-4 text-sm font-medium text-slate-800 outline-none transition focus:border-rose-500 focus:ring-4 focus:ring-rose-100"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1">
                                    🖼️ Upload Image / Attachment (Optional)
                                </label>
                                <input type="file" name="attachment" accept="image/*,.pdf,.doc,.docx"
                                       class="w-full text-xs font-semibold text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100 cursor-pointer">
                            </div>

                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1">Update Ticket Status</label>
                                <select name="status" class="w-full h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 outline-none transition focus:border-rose-500">
                                    <option value="in_progress" @selected($ticket->status === 'in_progress')>Mark In Progress</option>
                                    <option value="resolved" @selected($ticket->status === 'resolved')>Mark Resolved</option>
                                    <option value="open" @selected($ticket->status === 'open')>Keep Open</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-between">
                            <span class="text-[11px] font-medium text-slate-400 flex items-center gap-1">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                Multi-SMTP Auto Switcher Protected
                            </span>
                            <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-rose-700 px-6 text-xs font-black uppercase tracking-wider text-white shadow-md shadow-rose-900/10 transition hover:bg-rose-800 active:scale-[0.98] cursor-pointer">
                                <i data-lucide="send" class="w-4 h-4"></i>
                                Send Reply Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ticket Control Panels (Right Sidebar) -->
            <div class="space-y-6">
                <!-- Status Control Panel -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 p-6 shadow-sm">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-rose-700 mb-4 flex items-center gap-2">
                        <i data-lucide="settings" class="w-4 h-4"></i>
                        Ticket Status Control
                    </h2>
                    
                    <div class="mb-5 flex items-center justify-between border-b border-slate-100 dark:border-gray-700 pb-4">
                        <span class="text-sm font-medium text-slate-500">Current Status</span>
                        @php
                            $badgeColor = $statusBadges[$ticket->status] ?? 'slate';
                            $lbl = $statusLabels[$ticket->status] ?? $ticket->status;
                        @endphp
                        @if($badgeColor === 'emerald')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                {{ $lbl }}
                            </span>
                        @elseif($badgeColor === 'amber')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-amber-50 text-amber-700 border border-amber-100">
                                {{ $lbl }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-rose-50 text-rose-700 border border-rose-100">
                                {{ $lbl }}
                            </span>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.support.status', $ticket) }}">
                        @csrf
                        @method('PATCH')
                        
                        <label class="block mb-4">
                            <span class="block text-xs font-bold text-slate-500 uppercase mb-2">Update Status</span>
                            <select name="status" class="w-full h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-100">
                                <option value="open" @selected($ticket->status === 'open')>Open</option>
                                <option value="in_progress" @selected($ticket->status === 'in_progress')>In Progress</option>
                                <option value="resolved" @selected($ticket->status === 'resolved')>Resolved</option>
                            </select>
                        </label>

                        <button type="submit" class="w-full inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-rose-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-rose-800 cursor-pointer">
                            <i data-lucide="check" class="h-4 w-4"></i>
                            Save Status Changes
                        </button>
                    </form>
                </div>

                <!-- Attachment Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200 dark:border-gray-700 p-6 shadow-sm">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-rose-700 mb-4 flex items-center gap-2">
                        <i data-lucide="image" class="w-4 h-4"></i>
                        Attachment Screenshot
                    </h2>
                    @if($ticket->screenshot_path)
                        @php
                            $screenshotUrl = route('admin.support.screenshot', ['path' => $ticket->screenshot_path]);
                        @endphp
                        <div class="space-y-4">
                            <div class="relative group cursor-pointer border border-slate-200 rounded-xl overflow-hidden aspect-video bg-slate-900" @click="openPreview('{{ $screenshotUrl }}', 'Screenshot Attachment', false)">
                                <img src="{{ $screenshotUrl }}" alt="Screenshot Attachment" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200 opacity-90 group-hover:opacity-100">
                                <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                    <span class="rounded-full bg-white/90 p-2 text-slate-800 shadow-md">
                                        <i data-lucide="maximize-2" class="w-4 h-4"></i>
                                    </span>
                                </div>
                            </div>
                            <button type="button" class="w-full inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50 cursor-pointer" @click="openPreview('{{ $screenshotUrl }}', 'Screenshot Attachment', false)">
                                <i data-lucide="zoom-in" class="h-4 w-4"></i>
                                View Enlarge Screenshot
                            </button>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-6 text-slate-400">
                            <i data-lucide="image-off" class="w-10 h-10 text-slate-350 mb-2"></i>
                            <span class="text-xs font-bold uppercase tracking-wider">No attachment</span>
                            <span class="text-[11px] text-slate-400 mt-1">No screenshot was uploaded.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Preview Modal Element -->
        <template x-teleport="body">
            <div x-show="preview" class="preview-modal" x-cloak>
                <button type="button" class="preview-backdrop" @click="closePreview()"></button>
                <div class="preview-panel">
                    <div class="preview-head gap-3">
                        <strong x-text="label"></strong>
                        <div class="ml-auto flex items-center gap-2">
                            <div class="flex items-center gap-2" x-show="!pdf">
                                <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-100" @click="zoomOut()">-</button>
                                <span class="min-w-14 rounded-full bg-slate-100 px-3 py-1 text-center text-xs font-black text-slate-700" x-text="Math.round(zoom * 100) + '%'"></span>
                                <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-100" @click="zoomIn()">+</button>
                                <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-slate-500 shadow-sm transition hover:bg-slate-100" @click="resetZoom()">Reset</button>
                            </div>
                            <button id="download-pdf-btn" type="button" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-emerald-700 shadow-sm transition hover:bg-emerald-100 flex items-center gap-1 cursor-pointer" @click="downloadPdf()">
                                <i data-lucide="download" class="h-3.5 w-3.5"></i> Download image
                            </button>
                            <button type="button" class="text-2xl leading-none text-slate-500" @click="closePreview()">&times;</button>
                        </div>
                    </div>
                    <div class="preview-body cursor-grab select-none overflow-auto"
                         @mousedown="startPan($event)"
                         @mousemove="movePan($event)"
                         @mouseleave="stopPan()"
                         @touchstart.passive="startPan($event)"
                         @touchmove="movePan($event)">
                        <template x-if="!pdf">
                            <img :src="src" :alt="label" class="transition-all duration-150" :style="'max-width: none; width: ' + (zoom * 100) + '%; height: auto;'">
                        </template>
                        <template x-if="pdf"><iframe :src="src"></iframe></template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-admin-layout>
