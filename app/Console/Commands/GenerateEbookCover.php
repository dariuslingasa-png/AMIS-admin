<?php

namespace App\Console\Commands;

use App\Models\Ebook;
use App\Services\EbookCoverService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateEbookCover extends Command
{
    protected $signature = 'ebook:generate-cover {ebook} {--force}';

    protected $description = 'Generate an eBook cover image from the first page of its PDF';

    public function handle(EbookCoverService $covers): int
    {
        $ebook = Ebook::find($this->argument('ebook'));

        if (! $ebook) {
            $this->error('eBook not found.');

            return self::FAILURE;
        }

        if ($ebook->cover_image_path && ! $this->option('force')) {
            $this->info('eBook already has a cover.');

            return self::SUCCESS;
        }

        if (! $ebook->file_path || ! Storage::disk('ebook_private')->exists($ebook->file_path)) {
            $this->error('eBook PDF file is missing.');

            return self::FAILURE;
        }

        $pdfPath = Storage::disk('ebook_private')->path($ebook->file_path);
        $coverPath = $covers->generateFromPdf($pdfPath);

        if (! $coverPath) {
            $this->error('Cover generation failed.');

            return self::FAILURE;
        }

        if ($ebook->cover_image_path && $this->option('force')) {
            $covers->delete($ebook->cover_image_path);
        }

        $ebook->update(['cover_image_path' => $coverPath]);
        $this->info("Cover generated: {$coverPath}");

        return self::SUCCESS;
    }
}
