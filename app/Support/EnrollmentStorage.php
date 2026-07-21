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

        $path = self::existingVariant($path, $size) ?? $path;

        // If file exists in local admin storage, return local route
        if (is_file(storage_path('app/public/' . $path)) || is_file(public_path('storage/' . $path))) {
            return app()->has('router')
                ? route('admin.payments.receipt-file', ['path' => $path])
                : asset('storage/'.$path);
        }

        // Use direct storage URL if configured to bypass admin permission/CORS checks for non-finance users
        $storageUrl = config('services.enrollment_storage_url');
        if ($storageUrl) {
            return rtrim($storageUrl, '/') . '/' . $path;
        }

        // Return the secure local proxy route to bypass CORS/ORB/symlink blocks
        return app()->has('router')
            ? route('admin.payments.receipt-file', ['path' => $path])
            : asset('storage/'.$path);
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
                $directory = rtrim($root, '/').'/'.$originalDirectory;
                if (! is_dir($directory)) {
                    continue;
                }

                foreach (glob($directory.'/'.$filename.'.*') ?: [] as $file) {
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
            $absolute = rtrim($root, '/').'/'.ltrim($path, '/');

            if (is_file($absolute) && filesize($absolute) > 0) {
                return true;
            }
        }

        return false;
    }

    private static function relativeToRoot(string $file, string $root): ?string
    {
        $root = rtrim($root, '/').'/';

        if (! str_starts_with($file, $root)) {
            return null;
        }

        return ltrim(substr($file, strlen($root)), '/');
    }

    public static function getAbsolutePath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }
        $path = ltrim((string) $path, '/');
        
        foreach (self::roots() as $root) {
            $absolute = rtrim($root, '/').'/'.$path;
            if (is_file($absolute) && filesize($absolute) > 0) {
                return $absolute;
            }
        }
        return null;
    }

    private static function roots(): array
    {
        return [
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
