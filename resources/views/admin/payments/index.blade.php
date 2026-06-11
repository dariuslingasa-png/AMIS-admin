<x-admin-layout title="Enrollment Payment Approval">
    @php
        $sortLink = function (string $key) use ($sort, $direction) {
            $nextDirection = $sort === $key && $direction === 'asc' ? 'desc' : 'asc';

            return request()->fullUrlWithQuery([
                'sort' => $key,
                'direction' => $nextDirection,
                'page' => null,
            ]);
        };

        $sortIcon = fn (string $key) => $sort === $key
            ? ($direction === 'asc' ? 'arrow-up' : 'arrow-down')
            : 'arrow-up-down';
    @endphp

    <div x-data="{
        proofOpen: false,
        proofSrc: '',
        proofLabel: '',
        proofIsPdf: false,
        proofZoom: 1,
        panning: false,
        panEl: null,
        panX: 0,
        panY: 0,
        panLeft: 0,
        panTop: 0,
        openProof(url, label, isPdf) {
            this.proofSrc = url;
            this.proofLabel = label;
            this.proofIsPdf = isPdf;
            this.proofZoom = 1;
            this.proofOpen = true;
        },
        closeProof() {
            this.proofOpen = false;
            this.proofZoom = 1;
            this.stopPan();
        },
        zoomIn() { this.proofZoom = Math.min(3, Number((this.proofZoom + 0.1).toFixed(2))); },
        zoomOut() { this.proofZoom = Math.max(0.1, Number((this.proofZoom - 0.1).toFixed(2))); },
        resetZoom() { this.proofZoom = 1; },
        startPan(event) {
            if (this.proofIsPdf) return;
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
        }
    }"
    x-effect="document.body.classList.toggle('overflow-hidden', proofOpen)"
    @keydown.escape.window="closeProof()"
    @mouseup.window="stopPan()"
    @touchend.window="stopPan()">

    <x-card title="Enrollment Payment Approval" subtitle="Finance Management by {{ config('services.school.finance_reviewer_name', 'Finance Office') }}">
        <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4">
            <form method="GET" class="grid gap-3 xl:grid-cols-[minmax(280px,1fr)_180px_150px_120px_auto]">
                <label class="relative block">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search family, child, OR, reference, method..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                </label>

                <select name="status" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    <option value="">All statuses</option>
                    @foreach (['pending', 'verified', 'rejected'] as $statusOption)
                        <option value="{{ $statusOption }}" @selected(request('status') === $statusOption)>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>

                <select name="sort" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    @foreach (['updated' => 'Latest update', 'family' => 'Family', 'children' => 'Children', 'amount' => 'Amount', 'method' => 'Method', 'status' => 'Status'] as $sortValue => $sortLabel)
                        <option value="{{ $sortValue }}" @selected($sort === $sortValue)>{{ $sortLabel }}</option>
                    @endforeach
                </select>

                <select name="per_page" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    @foreach ([10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} rows</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-700 px-4 text-sm font-black text-white shadow-sm transition hover:bg-emerald-800">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Filter
                    </button>
                    @if (request()->hasAny(['search', 'status', 'sort', 'direction', 'per_page']))
                        <a href="{{ route('admin.payments.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-600 transition hover:bg-slate-50">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-2 xl:grid-cols-6">
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400">Families</div>
                <div class="mt-1 text-xl font-black text-slate-950">{{ number_format($paymentSummary['families']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400">Children</div>
                <div class="mt-1 text-xl font-black text-slate-950">{{ number_format($paymentSummary['children']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400">Amount</div>
                <div class="mt-1 text-xl font-black text-slate-950">{{ number_format((float) $paymentSummary['amount'], 2) }}</div>
            </div>
            <div class="rounded-xl border border-amber-100 bg-amber-50 p-3">
                <div class="text-[10px] font-black uppercase tracking-wider text-amber-500">Pending</div>
                <div class="mt-1 text-xl font-black text-amber-700">{{ number_format($paymentSummary['pending']) }}</div>
            </div>
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-3">
                <div class="text-[10px] font-black uppercase tracking-wider text-emerald-500">Verified</div>
                <div class="mt-1 text-xl font-black text-emerald-700">{{ number_format($paymentSummary['verified']) }}</div>
            </div>
            <div class="rounded-xl border border-rose-100 bg-rose-50 p-3">
                <div class="text-[10px] font-black uppercase tracking-wider text-rose-500">Rejected</div>
                <div class="mt-1 text-xl font-black text-rose-700">{{ number_format($paymentSummary['rejected']) }}</div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead class="bg-white text-[11px] font-black uppercase tracking-widest text-slate-400">
                    <tr class="border-b border-slate-100">
                        @foreach ([
                            'family' => 'Family / Applicant',
                            'children' => 'Children',
                            'grade' => 'Grade',
                            'amount' => 'Amount',
                            'method' => 'Method',
                            'status' => 'Status',
                            'updated' => 'Updated',
                        ] as $key => $label)
                            <th class="px-4 py-3">
                                <a href="{{ $sortLink($key) }}" class="inline-flex items-center gap-1.5 transition hover:text-emerald-700">
                                    {{ $label }}
                                    <i data-lucide="{{ $sortIcon($key) }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($paymentFamilies as $family)
                        @php
                            $payment = $family['payment'];
                            $children = $family['children'];
                            $familyNo = $family['family_no'];
                            $familyLabel = $family['family_label'];
                            $familyStatus = $family['status'];
                            $statusColor = $familyStatus === 'verified' ? 'green' : ($familyStatus === 'rejected' ? 'red' : 'yellow');

                            // Find the first payment with a receipt_url for the View Proof button
                            $proofPayment = $family['payments']->first(fn ($p) => filled($p->receipt_url));
                            $proofUrl = $proofPayment ? \App\Support\EnrollmentStorage::url($proofPayment->receipt_url) : null;
                            $proofIsPdf = $proofPayment && $proofPayment->receipt_url && strtolower(pathinfo($proofPayment->receipt_url, PATHINFO_EXTENSION)) === 'pdf';
                        @endphp
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="px-4 py-4 align-top">
                                <div class="font-black text-slate-950">{{ $familyLabel }}</div>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-slate-400">
                                    <span>Family #{{ str_pad((string) $familyNo, 4, '0', STR_PAD_LEFT) }}</span>
                                    @if ($children->count() > 1)
                                        <span>&middot; {{ $children->count() }} children</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="flex max-w-xl flex-wrap gap-1.5">
                                    @forelse ($children as $child)
                                        @php
                                            $childStatus = strtolower((string) ($child->payment?->status ?? 'missing'));
                                            $childChip = match ($childStatus) {
                                                'verified' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                                                'rejected' => 'bg-rose-50 text-rose-700 ring-rose-100',
                                                'pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
                                                default => 'bg-slate-100 text-slate-600 ring-slate-200',
                                            };
                                        @endphp
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide ring-1 {{ $childChip }}">
                                            {{ $child->full_name ?: 'Applicant' }}
                                        </span>
                                    @empty
                                        <span class="text-xs font-semibold text-slate-400">No child record</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="flex max-w-xs flex-wrap gap-1.5">
                                    @forelse ($children as $child)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-slate-600 ring-1 ring-slate-200">
                                            {{ $child->grade_level ?: 'N/A' }}
                                        </span>
                                    @empty
                                        <span class="text-xs font-semibold text-slate-400">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top font-semibold tabular-nums text-slate-700">{{ number_format((float) $family['amount'], 2) }}</td>
                            <td class="px-4 py-4 align-top font-semibold text-slate-700">{{ $family['methods']->isNotEmpty() ? $family['methods']->join(', ') : '-' }}</td>
                            <td class="px-4 py-4 align-top"><x-badge color="{{ $statusColor }}">{{ Str::upper($familyStatus) }}</x-badge></td>
                            <td class="px-4 py-4 align-top font-semibold text-slate-500">{{ optional($family['updated_at'])->format('M d, Y') }}</td>
                            <td class="px-4 py-4 text-right align-top">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($proofUrl)
                                        <button type="button"
                                                @click="openProof('{{ $proofUrl }}', '{{ addslashes($familyLabel) }}', {{ $proofIsPdf ? 'true' : 'false' }})"
                                                class="inline-flex items-center gap-1.5 rounded-xl bg-sky-50 px-3 py-2 text-xs font-black uppercase tracking-wider text-sky-700 ring-1 ring-sky-200 transition hover:bg-sky-100 hover:text-sky-800 cursor-pointer">
                                            <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                            View Proof
                                        </button>
                                    @endif
                                    @if ($payment->applicant)
                                        <a href="{{ route('admin.payments.show', $payment) }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3.5 py-2 text-xs font-black uppercase tracking-wider text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700">
                                            Review
                                            <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <i data-lucide="search-x" class="h-6 w-6"></i>
                                    </span>
                                    <div class="mt-3 text-sm font-black text-slate-700">No payment families found</div>
                                    <div class="mt-1 text-xs font-semibold text-slate-400">Adjust the search or filters to see more records.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-5 py-4">{{ $paymentFamilies->links() }}</div>
    </x-card>

    {{-- Payment Proof Preview Modal --}}
    <div x-show="proofOpen" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
        <div class="relative max-h-[92vh] w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <h2 class="font-black text-slate-950" x-text="proofLabel"></h2>
                <div class="ml-auto flex items-center gap-2">
                    <div class="flex items-center gap-2" x-show="!proofIsPdf">
                        <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-100 cursor-pointer" @click="zoomOut()">-</button>
                        <span class="min-w-14 rounded-full bg-slate-100 px-3 py-1 text-center text-xs font-black text-slate-700" x-text="Math.round(proofZoom * 100) + '%'"></span>
                        <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-100 cursor-pointer" @click="zoomIn()">+</button>
                        <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-slate-500 shadow-sm transition hover:bg-slate-100 cursor-pointer" @click="resetZoom()">Reset</button>
                    </div>
                    <a :href="proofSrc" target="_blank" rel="noopener noreferrer" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-emerald-700 shadow-sm transition hover:bg-emerald-100 flex items-center gap-1 cursor-pointer">
                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i> Open Full
                    </a>
                    <button type="button" class="rounded-full bg-slate-100 p-2 text-slate-500 hover:bg-slate-200 cursor-pointer" @click="closeProof()">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
            </div>
            <div class="max-h-[78vh] cursor-grab select-none overflow-auto bg-slate-50 p-4"
                 @mousedown="startPan($event)"
                 @mousemove="movePan($event)"
                 @mouseleave="stopPan()"
                 @touchstart.passive="startPan($event)"
                 @touchmove="movePan($event)">
                <template x-if="proofIsPdf">
                    <iframe :src="proofSrc" class="h-[72vh] w-full rounded-2xl bg-white"></iframe>
                </template>
                <template x-if="!proofIsPdf">
                    <img :src="proofSrc" :alt="proofLabel" class="mx-auto rounded-2xl object-contain transition-all duration-150" :style="'max-width: none; width: ' + (proofZoom * 100) + '%; height: auto;'">
                </template>
            </div>
        </div>
    </div>

    </div>{{-- close x-data --}}
</x-admin-layout>
