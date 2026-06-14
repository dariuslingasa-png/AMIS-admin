<div x-show="activeTab === 'documents'" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-cloak>
    <div class="upload-grid">
        @php
            $docs = [
                ['label' => '2x2 Photo ID', 'url' => $student->applicant->photo_2x2_url],
                ['label' => 'Birth Certificate', 'url' => $student->applicant->birth_cert_url],
                ['label' => 'Report Card / Form 138', 'url' => $student->applicant->report_card_url],
                ['label' => 'Marriage Contract', 'url' => $student->applicant->marriage_contract_url],
                ['label' => 'Medical History Records', 'url' => $student->applicant->medical_record_url],
                ['label' => 'Temporary Proof (Affidavit)', 'url' => $student->applicant->affidavit_url]
            ];
        @endphp

        @foreach($docs as $doc)
            @php
                $assetUrl = \App\Support\EnrollmentStorage::url($doc['url']);
                $isPdf = $doc['url'] && strtolower(pathinfo($doc['url'], PATHINFO_EXTENSION)) === 'pdf';
            @endphp
            <article class="upload-card {{ $doc['url'] ? '' : 'upload-card-missing' }}">
                <button type="button" class="upload-preview" @if ($assetUrl) @click="openPreview('{{ $assetUrl }}', '{{ $doc['label'] }}', {{ $isPdf ? 'true' : 'false' }})" @endif @disabled(!$assetUrl)>
                    @if ($assetUrl && !$isPdf)
                        <x-smart-preview-image :src="$assetUrl" :alt="$doc['label']" />
                    @elseif ($assetUrl && $isPdf)
                        <span class="upload-pdf"><i data-lucide="file-text" class="h-9 w-9"></i>PDF Receipt</span>
                    @else
                        <span class="upload-empty"><i data-lucide="upload-cloud" class="h-8 w-8"></i>No document</span>
                    @endif
                </button>
                <div class="upload-body">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-xs font-bold text-slate-950">{{ $doc['label'] }}</h3>
                        <x-badge color="{{ $doc['url'] ? 'green' : 'gray' }}">
                            {{ $doc['url'] ? 'Verified' : 'Missing' }}
                        </x-badge>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</div>
