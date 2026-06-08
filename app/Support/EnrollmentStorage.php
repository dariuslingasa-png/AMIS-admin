<?php

namespace App\Support;

class EnrollmentStorage
{
    public static function url(?string $path, ?string $size = null): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = ltrim((string) $path, '/');

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if ($size && str_contains($path, 'optimized/')) {
            $path = str_replace('optimized/', "thumbnails/{$size}/", $path);
        }

        $baseUrl = trim((string) config('services.enrollment_storage_url'));

        if ($baseUrl !== '') {
            return rtrim($baseUrl, '/').'/'.$path;
        }

        return asset('storage/'.$path);
    }
}
