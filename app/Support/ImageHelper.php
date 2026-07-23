<?php

namespace App\Support;

class ImageHelper
{
    /**
     * Resolve the URL/path of a specific thumbnail size based on the optimized image path.
     *
     * @param  string|null  $path  The optimized image path.
     * @param  string  $size  'small', 'medium', or 'large'.
     * @return string|null Resolved path or null.
     */
    public static function thumb(?string $path, string $size = 'medium'): ?string
    {
        if (blank($path)) {
            return null;
        }

        // Check if it's already an absolute URL
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // If path contains '/optimized/', swap it with '/thumbnails/[size]/'
        if (str_contains($path, '/optimized/')) {
            return str_replace('/optimized/', "/thumbnails/{$size}/", $path);
        }

        // Fallback: if it's a legacy teacher photo path under images/teachers/ (e.g. "images/teachers/john_doe.jpg")
        if (str_contains($path, 'images/teachers/') && ! str_contains($path, '/original/') && ! str_contains($path, '/thumbnails/')) {
            $dir = dirname($path);
            $filename = pathinfo($path, PATHINFO_FILENAME);
            $thumbPath = "{$dir}/thumbnails/{$size}/{$filename}.webp";
            if (file_exists(public_path($thumbPath))) {
                return $thumbPath;
            }

            return $path;
        }

        return $path;
    }
}
