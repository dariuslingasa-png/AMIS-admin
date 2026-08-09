<?php

namespace App\Support;

class EnrollmentStorage
{
    /**
     * Normalize stored DB paths (removes prefixes like 'storage/', 'public/', '/storage/', leading slashes, full domain URLs).
     */
    public static function normalizePath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $path = trim((string) $path);

        // If full HTTP URL containing /storage/, extract relative path after /storage/
        if (str_contains($path, '/storage/')) {
            $parts = explode('/storage/', $path);
            $path = end($parts);
        }

        // Strip prefixes
        $path = ltrim($path, '/');
        $prefixes = ['public/storage/', 'public/', 'storage/', 'app/public/'];
        foreach ($prefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }
        }

        return ltrim($path, '/');
    }

    /**
     * Generate secure display URL for a document or payment proof path.
     */
    public static function url(?string $path, ?string $size = null): ?string
    {
        if (blank($path)) {
            return null;
        }

        $rawPath = trim((string) $path);
        if (str_starts_with($rawPath, 'http://') || str_starts_with($rawPath, 'https://')) {
            return $rawPath;
        }

        $normalized = self::normalizePath($rawPath);
        $variant = self::existingVariant($normalized, $size) ?? $normalized;

        if (app()->has('router')) {
            return route('admin.payments.receipt-file', ['path' => $variant]);
        }

        return asset('storage/' . $variant);
    }

    /**
     * Generate download URL for forcing browser attachment download.
     */
    public static function downloadUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $normalized = self::normalizePath($path);
        if (app()->has('router')) {
            return route('admin.payments.receipt-file', ['path' => $normalized, 'download' => 1]);
        }

        return asset('storage/' . $normalized);
    }

    /**
     * Check if a file physically exists on disk across all storage roots.
     */
    public static function fileExists(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        $rawPath = trim((string) $path);
        if (str_starts_with($rawPath, 'http://') || str_starts_with($rawPath, 'https://')) {
            return true;
        }

        $normalized = self::normalizePath($rawPath);
        return self::exists($normalized);
    }

    /**
     * Comprehensive file inspection helper for Blade templates (handles DB vs Disk state).
     */
    public static function getFileState(?string $path): array
    {
        $hasDbRecord = !blank($path);
        $normalized = self::normalizePath($path);
        $absolute = self::getAbsolutePath($normalized);
        $exists = !empty($absolute) && is_file($absolute) && filesize($absolute) > 0;

        $ext = strtolower(pathinfo($normalized ?? '', PATHINFO_EXTENSION));
        $isPdf = $ext === 'pdf';
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);

        return [
            'has_db_record' => $hasDbRecord,
            'normalized_path' => $normalized,
            'exists_on_disk' => $exists,
            'absolute_path' => $absolute,
            'url' => $exists ? self::url($normalized) : null,
            'download_url' => $exists ? self::downloadUrl($normalized) : null,
            'extension' => $ext,
            'is_pdf' => $isPdf,
            'is_image' => $isImage,
        ];
    }

    /**
     * Resolve absolute disk path across configured storage roots.
     */
    public static function getAbsolutePath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $normalized = self::normalizePath($path);

        foreach (self::roots() as $root) {
            $absolute = rtrim($root, '/') . '/' . $normalized;
            if (is_file($absolute) && filesize($absolute) > 0) {
                return $absolute;
            }
        }

        return null;
    }

    private static function existingVariant(string $path, ?string $size = null): ?string
    {
        $candidates = collect();

        if ($size && str_contains($path, 'optimized/')) {
            $candidates->push(str_replace('optimized/', "thumbnails/{$size}/", $path));
        }

        if (str_contains($path, 'optimized/')) {
            $originalDirectory = dirname(str_replace('optimized/', 'original/', $path));
            $filename = pathinfo($path, PATHINFO_FILENAME);

            foreach (self::roots() as $root) {
                $directory = rtrim($root, '/') . '/' . $originalDirectory;
                if (!is_dir($directory)) {
                    continue;
                }

                foreach (glob($directory . '/' . $filename . '.*') ?: [] as $file) {
                    $relative = self::relativeToRoot($file, $root);
                    if ($relative) {
                        $candidates->push($relative);
                    }
                }
            }
        }

        $candidates = $candidates
            ->merge([
                str_replace('optimized/', 'thumbnails/large/', $path),
                str_replace('optimized/', 'thumbnails/medium/', $path),
                $path,
            ])
            ->filter()
            ->unique()
            ->values();

        foreach ($candidates as $candidate) {
            if (self::exists($candidate)) {
                return ltrim($candidate, '/');
            }
        }

        return null;
    }

    private static function exists(string $path): bool
    {
        foreach (self::roots() as $root) {
            $absolute = rtrim($root, '/') . '/' . ltrim($path, '/');
            if (is_file($absolute) && filesize($absolute) > 0) {
                return true;
            }
        }

        return false;
    }

    private static function relativeToRoot(string $file, string $root): ?string
    {
        $root = rtrim($root, '/') . '/';
        if (!str_starts_with($file, $root)) {
            return null;
        }

        return ltrim(substr($file, strlen($root)), '/');
    }

    private static function roots(): array
    {
        return [
            '/home2/amisdavc/aes.amis.edu.ph/storage/app/public',
            '/home2/amisdavc/afps.amis.edu.ph/storage/app/public',
            base_path('../aes.amis.edu.ph/storage/app/public'),
            base_path('../afps.amis.edu.ph/storage/app/public'),
            base_path('../amis_enrollment/storage/app/public'),
            base_path('../amis_enrollment/public/storage'),
            base_path('../enrollment/storage/app/public'),
            base_path('../enrollment/public/storage'),
            base_path('../../amis_enrollment/storage/app/public'),
            base_path('../../public_html/amis_enrollment/storage/app/public'),
            base_path('../../public_html/storage'),
            storage_path('app/public'),
            public_path('storage'),
            public_path(),
        ];
    }
}
