<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBatchDocumentExportJob;
use App\Models\DocumentExport;
use Illuminate\Console\Command;

class ProcessBatchExportCommand extends Command
{
    protected $signature = 'export:process {export_id}';
    protected $description = 'Process a pending document export job in the background';

    public function handle()
    {
        $exportId = $this->argument('export_id');
        $export = DocumentExport::find($exportId);

        if (!$export) {
            $this->error("Export #{$exportId} not found.");
            return 1;
        }

        $this->info("Starting document export #{$exportId}...");
        
        $job = new ProcessBatchDocumentExportJob($export);
        $job->handle();

        $this->info("Document export #{$exportId} finished.");
        return 0;
    }
}
