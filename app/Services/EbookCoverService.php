<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EbookCoverService
{
    public function generateFromPdf(string $pdfAbsolutePath): ?string
    {
        if (! file_exists($pdfAbsolutePath)) {
            return null;
        }

        $uuid = Str::uuid();
        $tempPngPrefix = sys_get_temp_dir()."/ebook_cover_{$uuid}";
        $coversDir = $this->getCoversDir();

        if (! $coversDir) {
            Log::warning('Cannot determine ebook public covers directory for cover generation.');

            return null;
        }

        $webpFilename = "{$uuid}.webp";
        $webpAbsolutePath = "{$coversDir}/{$webpFilename}";

        $pdftoppmCmd = sprintf(
            'pdftoppm -f 1 -l 1 -r 150 -png %s %s 2>&1',
            escapeshellarg($pdfAbsolutePath),
            escapeshellarg($tempPngPrefix)
        );

        exec($pdftoppmCmd, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::warning('pdftoppm failed for cover generation: '.implode("\n", $output));

            return null;
        }

        $tempPngPath = $this->firstGeneratedPng($tempPngPrefix);

        if (! $tempPngPath) {
            return null;
        }

        if ($this->convertPngToWebp($tempPngPath, $webpAbsolutePath)) {
            @unlink($tempPngPath);

            return "covers/{$webpFilename}";
        }

        $pngFilename = "{$uuid}.png";
        $pngAbsolutePath = "{$coversDir}/{$pngFilename}";
        copy($tempPngPath, $pngAbsolutePath);
        @unlink($tempPngPath);

        return file_exists($pngAbsolutePath) ? "covers/{$pngFilename}" : null;
    }

    public function delete(?string $coverPath): void
    {
        if (! $coverPath) {
            return;
        }

        $coversDir = $this->getCoversDir();
        if (! $coversDir) {
            return;
        }

        $coverFile = dirname($coversDir).'/'.$coverPath;
        if (file_exists($coverFile)) {
            @unlink($coverFile);
        }
    }

    private function firstGeneratedPng(string $prefix): ?string
    {
        foreach (glob("{$prefix}*.png") as $file) {
            if (file_exists($file)) {
                return $file;
            }
        }

        return null;
    }

    private function convertPngToWebp(string $pngPath, string $webpPath): bool
    {
        $cwebpCmd = sprintf(
            'cwebp -q 60 -resize 400 0 %s -o %s 2>&1',
            escapeshellarg($pngPath),
            escapeshellarg($webpPath)
        );
        exec($cwebpCmd, $cwebpOutput, $cwebpReturn);

        if ($cwebpReturn === 0 && file_exists($webpPath)) {
            return true;
        }

        $convertCmd = sprintf(
            'convert %s -resize 400x -quality 60 %s 2>&1',
            escapeshellarg($pngPath),
            escapeshellarg($webpPath)
        );
        exec($convertCmd, $convertOutput, $convertReturn);

        return $convertReturn === 0 && file_exists($webpPath);
    }

    private function getCoversDir(): ?string
    {
        $privateRoot = Storage::disk('ebook_private')->path('');
        $storageAppDir = dirname(rtrim($privateRoot, '/'));
        $coversDir = "{$storageAppDir}/public/covers";

        if (! is_dir($coversDir)) {
            mkdir($coversDir, 0755, true);
        }

        return $coversDir;
    }
}
