<x-admin-layout
    title="Student Documents"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Student Documents', 'href' => null],
    ]"
>
    <div class="space-y-6" x-data="{
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
             const url = this.src;
             const filename = (this.label || 'document').replace(/[^a-zA-Z0-9]/g, '_') + '.pdf';
             if (this.pdf) {
                 const link = document.createElement('a');
                 link.href = url;
                 link.download = filename;
                 document.body.appendChild(link);
                 link.click();
                 document.body.removeChild(link);
                 return;
             }
             try {
                 const btn = document.getElementById('download-pdf-btn');
                 const originalText = btn.innerHTML;
                 btn.innerHTML = '<i data-lucide=\'loader-2\' class=\'h-3.5 w-3.5 animate-spin\'></i> Converting...';
                 if (window.lucide) window.lucide.createIcons();
                 const { jsPDF } = window.jspdf;
                 const img = new Image();
                 img.crossOrigin = 'Anonymous';
                 img.src = url;
                 img.onload = () => {
                     const pdf = new jsPDF({
                         orientation: img.width > img.height ? 'landscape' : 'portrait',
                         unit: 'px',
                         format: [img.width, img.height]
                     });
                     pdf.addImage(img, 'JPEG', 0, 0, img.width, img.height);
                     pdf.save(filename);
                     btn.innerHTML = originalText;
                     if (window.lucide) window.lucide.createIcons();
                 };
                 img.onerror = () => {
                     const link = document.createElement('a');
                     link.href = url;
                     link.download = this.label || 'image';
                     document.body.appendChild(link);
                     link.click();
                     document.body.removeChild(link);
                     btn.innerHTML = originalText;
                     if (window.lucide) window.lucide.createIcons();
                 };
             } catch (e) {
                 console.error(e);
                 window.open(url, '_blank');
             }
         }
     }"
     x-effect="document.body.classList.toggle('overflow-hidden', preview)"
    >
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-emerald-700/30 bg-gradient-to-br from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Students Workspace</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Student Documents</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-100">
                        Manage required registration files, birth certificates, report cards, and good moral clearances.
                    </p>
                </div>
            </div>
        </section>

        <!-- Main Card -->
        <x-card title="Registration Documents Directory" subtitle="Review uploaded student credentials and certificates">
            <!-- Search Filter -->
            <div class="px-4 py-4 sm:px-6 border-b border-slate-200/60 bg-slate-50/50">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div></div>
                    <div class="flex items-center gap-2">
                        <form method="GET" action="{{ route('admin.students.documents') }}" class="flex items-center gap-2" onsubmit="showTableSkeleton()">
                            <div class="relative w-full sm:w-[320px]">
                                <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-3.5 h-4 w-4 text-slate-400"></i>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search student or ID..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-xs font-semibold text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-450 focus:ring-4 focus:ring-emerald-100">
                                @if(request()->filled('search'))
                                    <a href="{{ route('admin.students.documents') }}" class="absolute right-3 top-3.5 text-slate-400 hover:text-slate-600 transition" title="Clear search">
                                        <i data-lucide="x" class="h-4 w-4"></i>
                                    </a>
                                @endif
                            </div>
                            <button type="submit" class="inline-flex h-11 items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 cursor-pointer">
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Table Loading Skeleton -->
            <div id="tableSkeleton" class="hidden">
                <div class="animate-pulse space-y-4">
                    @for ($i = 0; $i < 5; $i++)
                        <div class="flex items-center justify-between py-4 border-b border-slate-100 px-5">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded bg-slate-100"></div>
                                <div class="space-y-2">
                                    <div class="h-4 w-32 rounded bg-slate-100"></div>
                                    <div class="h-3 w-20 rounded bg-slate-50"></div>
                                </div>
                            </div>
                            <div class="h-4 w-24 rounded bg-slate-150"></div>
                            <div class="h-4 w-20 rounded bg-slate-100"></div>
                            <div class="h-8 w-16 rounded bg-slate-50"></div>
                        </div>
                    @endfor
                </div>
            </div>

            <!-- Table Container -->
            <div id="tableContainer">
                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm min-w-[850px]">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                                <th class="px-5 py-3">Student</th>
                                <th class="px-5 py-3">Grade Level</th>
                                <th class="px-5 py-3">Birth Certificate</th>
                                <th class="px-5 py-3">Report Card (SF9)</th>
                                <th class="px-5 py-3">Good Moral Certificate</th>
                                <th class="px-5 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($students as $student)
                                @php
                                    $applicant = $student->applicant;
                                    $fullName = $applicant ? html_entity_decode(trim(($applicant->first_name ?? '').' '.($applicant->middle_name ?? '').' '.($applicant->last_name ?? '')), ENT_QUOTES, 'UTF-8') : 'Unknown Student';
                                @endphp
                                <tr class="align-middle">
                                    <td class="px-5 py-4">
                                        <span class="font-extrabold text-slate-900 block uppercase">{{ $fullName }}</span>
                                        <span class="text-xs font-bold text-slate-400 mt-1 block">{{ $student->student_number }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-xs font-bold text-slate-600">
                                        {{ $student->grade_level }}
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($applicant && $applicant->birth_cert_url)
                                            @php
                                                $birthCertUrl = \App\Support\EnrollmentStorage::url($applicant->birth_cert_url);
                                                $isPdf = str_ends_with(strtolower($applicant->birth_cert_url), '.pdf');
                                            @endphp
                                            <a href="{{ $birthCertUrl }}" target="_blank" @click.prevent="openPreview('{{ $birthCertUrl }}', 'Birth Certificate - {{ addslashes($fullName) }}', {{ $isPdf ? 'true' : 'false' }})" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition cursor-zoom-in">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                                View Birth Cert
                                            </a>
                                        @else
                                            <span class="text-xs font-bold text-slate-450 italic">Not Uploaded</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($applicant && $applicant->report_card_url)
                                            @php
                                                $reportCardUrl = \App\Support\EnrollmentStorage::url($applicant->report_card_url);
                                                $isPdf = str_ends_with(strtolower($applicant->report_card_url), '.pdf');
                                            @endphp
                                            <a href="{{ $reportCardUrl }}" target="_blank" @click.prevent="openPreview('{{ $reportCardUrl }}', 'SF9/Report Card - {{ addslashes($fullName) }}', {{ $isPdf ? 'true' : 'false' }})" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition cursor-zoom-in">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                                View SF9
                                            </a>
                                        @else
                                            <span class="text-xs font-bold text-slate-450 italic">Not Uploaded</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($applicant && $applicant->good_moral_url)
                                            @php
                                                $goodMoralUrl = \App\Support\EnrollmentStorage::url($applicant->good_moral_url);
                                                $isPdf = str_ends_with(strtolower($applicant->good_moral_url), '.pdf');
                                            @endphp
                                            <a href="{{ $goodMoralUrl }}" target="_blank" @click.prevent="openPreview('{{ $goodMoralUrl }}', 'Good Moral Certificate - {{ addslashes($fullName) }}', {{ $isPdf ? 'true' : 'false' }})" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition cursor-zoom-in">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                                View Certificate
                                            </a>
                                        @else
                                            <span class="text-xs font-bold text-slate-450 italic">Not Uploaded</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.students.show', $student) }}" class="inline-flex h-9 items-center justify-center gap-1 rounded-xl bg-slate-50 border border-slate-200 px-3 text-xs font-bold text-slate-700 hover:bg-slate-100 transition">
                                            <i data-lucide="folder-open" class="w-3.5 h-3.5"></i>
                                            Verify Files
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-sm font-bold text-slate-400">No student documents found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-4 py-4 sm:px-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/30">
                    <p class="text-xs font-bold text-slate-500">
                        Showing {{ $students->firstItem() ?? 0 }}-{{ $students->lastItem() ?? 0 }} of {{ $students->total() }} students
                    </p>
                    <div class="w-full sm:w-auto">{{ $students->links() }}</div>
                </div>
            </div>
        </x-card>
        <!-- Preview Modal Portal (Identical to original modal previews for consistency) -->
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
                                <i data-lucide="download" class="h-3.5 w-3.5"></i> Download PDF
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        function showTableSkeleton() {
            document.getElementById('tableContainer').classList.add('hidden');
            document.getElementById('tableSkeleton').classList.remove('hidden');
        }

        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (link && !link.getAttribute('target') && !link.getAttribute('download')) {
                const href = link.getAttribute('href');
                if (href && href !== '#' && !href.startsWith('javascript:')) {
                    showTableSkeleton();
                }
            }
        });
    </script>
</x-admin-layout>
