<?php

namespace App\Http\Controllers;

use App\Models\Ebook;
use App\Models\EbookAccessLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminEbookController extends Controller
{
    private const STORAGE_DISK = 'ebook_private';

    private const GRADE_LEVELS = [
        'Kindergarten',
        'Kinder 1',
        'Kinder 2',
        'Grade 1',
        'Grade 2',
        'Grade 3',
        'Grade 4',
        'Grade 5',
        'Grade 6',
        'Grade 7',
        'Grade 8',
        'Grade 9',
        'Grade 10',
        'K11',
        'K12',
    ];

    public function index(Request $request): View
    {
        $books = Ebook::query()
            ->with('creator')
            ->withCount('readers')
            ->when($request->filled('grade'), fn ($query) => $query->where('grade_level', (string) $request->string('grade')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';

                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $totalBytes = 0;
        $groupedData = [];
        foreach (Ebook::all() as $book) {
            if ($book->file_path && Storage::disk(self::STORAGE_DISK)->exists($book->file_path)) {
                $sizeInBytes = Storage::disk(self::STORAGE_DISK)->size($book->file_path);
                $totalBytes += $sizeInBytes;

                $grade = $book->grade_level ?: 'Unassigned';
                if (! isset($groupedData[$grade])) {
                    $groupedData[$grade] = 0;
                }
                $groupedData[$grade] += $sizeInBytes;
            }
        }

        $chartData = [];
        foreach ($groupedData as $grade => $bytes) {
            $chartData[] = [
                'title' => $grade,
                'size' => round($bytes / 1024 / 1024, 2), // size in MB
            ];
        }

        $stats = [
            'total' => Ebook::count(),
            'published' => Ebook::where('status', 'published')->count(),
            'drafts' => Ebook::where('status', 'draft')->count(),
            'downloads_enabled' => Ebook::where('is_downloadable', true)->count(),
            'views' => Schema::hasTable('ebook_access_logs') ? EbookAccessLog::where('action', 'view')->count() : 0,
            'streams' => Schema::hasTable('ebook_access_logs') ? EbookAccessLog::where('action', 'stream')->count() : 0,
            'storage_used' => $this->formatBytes($totalBytes),
        ];

        $recentLogs = Schema::hasTable('ebook_access_logs')
            ? EbookAccessLog::with(['ebook', 'user'])->latest('created_at')->limit(8)->get()
            : collect();

        return view('admin.ebook.index', [
            'books' => $books,
            'gradeLevels' => self::GRADE_LEVELS,
            'stats' => $stats,
            'recentLogs' => $recentLogs,
            'chartData' => $chartData,
            'publicCatalogUrl' => rtrim((string) config('services.ebook.url'), '/').'/books',
        ]);
    }

    public function create(): View
    {
        return view('admin.ebook.create', [
            'book' => null,
            'gradeLevels' => self::GRADE_LEVELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request, requirePdf: true);
        $data['file_path'] = $this->storePdf($request);
        $data['created_by'] = Auth::id();

        // Allow manual cover upload to override auto-generated cover
        if ($request->hasFile('cover_image')) {
            $manualCover = $this->storeManualCover($request);
            if ($manualCover) {
                $data['cover_image_path'] = $manualCover;
            }
        }

        $ebook = Ebook::create($data);

        if (empty($data['cover_image_path'])) {
            $this->generateCoverInBackground($ebook);
        }

        return redirect()->route('admin.ebook.index')->with('success', 'eBook uploaded successfully.');
    }

    public function edit(Ebook $ebook): View
    {
        return view('admin.ebook.edit', [
            'book' => $ebook,
            'gradeLevels' => self::GRADE_LEVELS,
        ]);
    }

    public function update(Request $request, Ebook $ebook): RedirectResponse
    {
        $data = $this->validatedData($request, requirePdf: false);
        $shouldGenerateCover = false;

        if ($request->hasFile('pdf_file')) {
            $this->deletePdf($ebook);
            $this->deleteCover($ebook);
            $data['file_path'] = $this->storePdf($request);
            $data['cover_image_path'] = null;
            $shouldGenerateCover = true;
        }

        // Allow manual cover upload to override
        if ($request->hasFile('cover_image')) {
            $this->deleteCover($ebook);
            $manualCover = $this->storeManualCover($request);
            if ($manualCover) {
                $data['cover_image_path'] = $manualCover;
                $shouldGenerateCover = false;
            }
        }

        $ebook->update($data);

        if ($shouldGenerateCover) {
            $this->generateCoverInBackground($ebook->fresh());
        }

        return redirect()->route('admin.ebook.index')->with('success', 'eBook updated successfully.');
    }

    public function destroy(Ebook $ebook): RedirectResponse
    {
        // Delete cover image
        if ($ebook->cover_image_path) {
            $this->deleteCover($ebook);
        }

        $this->deletePdf($ebook);
        $ebook->delete();

        return redirect()->route('admin.ebook.index')->with('success', 'eBook deleted.');
    }

    private function validatedData(Request $request, bool $requirePdf): array
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'author' => ['nullable', 'string', 'max:255'],
            'grade_level' => ['required', 'string', Rule::in(self::GRADE_LEVELS)],
            'pdf_file' => [$requirePdf ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:1048576'],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'status' => ['required', 'string', Rule::in(['published', 'draft'])],
            'is_downloadable' => ['nullable', 'boolean'],
        ]);

        return [
            'title' => (string) $request->string('title')->trim(),
            'description' => $request->filled('description') ? (string) $request->string('description')->trim() : null,
            'author' => $request->filled('author') ? (string) $request->string('author')->trim() : null,
            'grade_level' => (string) $request->string('grade_level')->trim(),
            'status' => (string) $request->string('status'),
            'is_downloadable' => $request->boolean('is_downloadable'),
        ];
    }

    private function storePdf(Request $request): string
    {
        $file = $request->file('pdf_file');
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();

        $path = $file->storeAs('private/ebooks', $filename, self::STORAGE_DISK);

        try {
            $absolutePath = Storage::disk(self::STORAGE_DISK)->path($path);
            $this->optimizePdf($absolutePath);
        } catch (\Exception $e) {
            Log::error('Failed to optimize PDF: '.$e->getMessage());
        }

        return $path;
    }

    private function deletePdf(Ebook $ebook): void
    {
        if ($ebook->file_path) {
            Storage::disk(self::STORAGE_DISK)->delete($ebook->file_path);
        }
    }

    /**
     * Store a manually uploaded cover image as WebP in the ebook portal's public covers dir.
     */
    private function storeManualCover(Request $request): ?string
    {
        $file = $request->file('cover_image');
        if (! $file) {
            return null;
        }

        $uuid = Str::uuid();
        $webpFilename = "{$uuid}.webp";

        // Get the ebook portal's public covers directory
        $coversDir = $this->getEbookPublicCoversDir();
        if (! $coversDir) {
            return null;
        }

        $targetPath = "{$coversDir}/{$webpFilename}";
        $tempPath = $file->getRealPath();

        // 1. Try to resize and compress using GD (Pure PHP - highly compatible, works on Bluehost)
        if ($this->resizeAndCompressImageGD($tempPath, $targetPath, 400, 60)) {
            return "covers/{$webpFilename}";
        }

        // 2. Try to convert to WebP using ImageMagick convert (System binary fallback)
        $convertCmd = sprintf(
            'convert %s -resize 400x -quality 60 %s 2>&1',
            escapeshellarg($tempPath),
            escapeshellarg($targetPath)
        );
        exec($convertCmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($targetPath)) {
            return "covers/{$webpFilename}";
        }

        // 3. Fallback: store as-is with original extension
        $ext = $file->getClientOriginalExtension();
        $fallbackFilename = "{$uuid}.{$ext}";
        $file->move($coversDir, $fallbackFilename);

        return "covers/{$fallbackFilename}";
    }

    /**
     * Delete cover image from ebook portal's public storage.
     */
    private function deleteCover(Ebook $ebook): void
    {
        if (! $ebook->cover_image_path) {
            return;
        }

        $coversDir = $this->getEbookPublicCoversDir();
        if ($coversDir) {
            $coverFile = dirname($coversDir).'/'.$ebook->cover_image_path;
            if (file_exists($coverFile)) {
                @unlink($coverFile);
            }
        }
    }

    private function generateCoverInBackground(?Ebook $ebook): void
    {
        if (! $ebook?->id) {
            return;
        }

        $phpBinary = PHP_BINDIR.DIRECTORY_SEPARATOR.'php';
        $php = escapeshellarg(is_executable($phpBinary) ? $phpBinary : 'php');
        $artisan = escapeshellarg(base_path('artisan'));
        $ebookId = escapeshellarg((string) $ebook->id);
        $logFile = escapeshellarg(storage_path('logs/ebook-cover.log'));

        exec("nohup {$php} {$artisan} ebook:generate-cover {$ebookId} >> {$logFile} 2>&1 &");

        Log::info('eBook cover generation started in background.', [
            'ebook_id' => $ebook->id,
        ]);
    }

    /**
     * Get the absolute path to the ebook portal's public covers directory.
     */
    private function getEbookPublicCoversDir(): ?string
    {
        // The ebook_private disk root points to the ebook portal's storage/app/private
        // We need to go up to storage/app/public/covers
        $privateRoot = Storage::disk(self::STORAGE_DISK)->path('');
        // privateRoot = .../storage/app/private/
        $storageAppDir = dirname(rtrim($privateRoot, '/'));
        // storageAppDir = .../storage/app
        $coversDir = "{$storageAppDir}/public/covers";

        if (! is_dir($coversDir)) {
            mkdir($coversDir, 0755, true);
        }

        return $coversDir;
    }

    /**
     * Resize and compress an image using PHP's GD library.
     * Generates a WebP image by default, falling back to JPEG if WebP isn't supported.
     */
    private function resizeAndCompressImageGD(string $sourcePath, string $targetPath, int $targetWidth = 400, int $quality = 60): bool
    {
        // 1. Get image info
        $info = @getimagesize($sourcePath);
        if (! $info) {
            return false;
        }

        $mime = $info['mime'];

        // 2. Create image resource from source
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = @imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $sourceImage = @imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }

        if (! $sourceImage) {
            return false;
        }

        // 3. Calculate new dimensions preserving aspect ratio
        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        if ($origWidth > $targetWidth) {
            $targetHeight = (int) (($origHeight / $origWidth) * $targetWidth);
        } else {
            // No need to upscale if the source is already small
            $targetWidth = $origWidth;
            $targetHeight = $origHeight;
        }

        // 4. Create new true color image
        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
        if (! $targetImage) {
            imagedestroy($sourceImage);

            return false;
        }

        // Handle transparency for PNG/WebP
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
            imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        // 5. Resample the image
        if (! imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $origWidth, $origHeight)) {
            imagedestroy($sourceImage);
            imagedestroy($targetImage);

            return false;
        }

        // 6. Save as WebP if possible, otherwise save as JPEG
        $saved = false;
        if (function_exists('imagewebp')) {
            // Save as WebP
            $saved = @imagewebp($targetImage, $targetPath, $quality);
        } else {
            // Fallback to JPEG
            $saved = @imagejpeg($targetImage, $targetPath, $quality);
        }

        // 7. Free up memory
        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        return $saved;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Optimizes a PDF file for web viewing and eBook delivery.
     *
     * Runs Ghostscript + QPDF as a background process so the upload
     * completes immediately without waiting for optimization.
     * Large PDFs (599MB+) can take 10+ minutes to optimize.
     *
     * Targets: 150 DPI for color/grayscale images, 300 DPI for mono (text),
     * sRGB color space for screens, bicubic downsampling for quality.
     */
    private function optimizePdf(string $pdfAbsolutePath): void
    {
        if (! file_exists($pdfAbsolutePath)) {
            return;
        }

        $tempCompressedPath = escapeshellarg($pdfAbsolutePath.'.compressed');
        $tempLinearizedPath = escapeshellarg($pdfAbsolutePath.'.linearized');
        $escapedPdfPath = escapeshellarg($pdfAbsolutePath);
        $logFile = escapeshellarg(storage_path('logs/pdf-optimize.log'));

        // Build a self-contained shell script that runs in the background
        $script = <<<BASH
#!/bin/bash
ORIGINAL_SIZE=\$(stat -c%s {$escapedPdfPath} 2>/dev/null || stat -f%z {$escapedPdfPath} 2>/dev/null)

# Step 1: Ghostscript compression
gs -sDEVICE=pdfwrite \
   -dCompatibilityLevel=1.5 \
   -dPDFSETTINGS=/ebook \
   -dNOPAUSE -dQUIET -dBATCH \
   -dColorImageResolution=150 \
   -dGrayImageResolution=150 \
   -dMonoImageResolution=300 \
   -dDownsampleColorImages=true \
   -dDownsampleGrayImages=true \
   -dDownsampleMonoImages=true \
   -dColorImageDownsampleType=/Bicubic \
   -dGrayImageDownsampleType=/Bicubic \
   -dMonoImageDownsampleType=/Subsample \
   -dColorImageDownsampleThreshold=1.0 \
   -dGrayImageDownsampleThreshold=1.0 \
   -dAutoFilterColorImages=true \
   -dAutoFilterGrayImages=true \
   -dColorConversionStrategy=/sRGB \
   -dSubsetFonts=true \
   -dEmbedAllFonts=true \
   -dCompressFonts=true \
   -dCompressPages=true \
   -dDetectDuplicateImages=true \
   -dOptimize=true \
   -sOutputFile={$tempCompressedPath} \
   {$escapedPdfPath} >> {$logFile} 2>&1

if [ ! -f {$tempCompressedPath} ]; then
    echo "[$(date)] GS failed for {$escapedPdfPath}" >> {$logFile}
    exit 1
fi

# Step 2: QPDF linearization
qpdf --linearize --compress-streams=y --object-streams=generate \
     --recompress-flate --normalize-content=y \
     {$tempCompressedPath} {$tempLinearizedPath} >> {$logFile} 2>&1

# Pick the best result
FINAL=""
if [ -f {$tempLinearizedPath} ]; then
    FINAL={$tempLinearizedPath}
elif [ -f {$tempCompressedPath} ]; then
    FINAL={$tempCompressedPath}
fi

if [ -n "\$FINAL" ]; then
    OPT_SIZE=\$(stat -c%s "\$FINAL" 2>/dev/null || stat -f%z "\$FINAL" 2>/dev/null)
    if [ "\$OPT_SIZE" -lt "\$ORIGINAL_SIZE" ]; then
        cp "\$FINAL" {$escapedPdfPath}
        echo "[$(date)] Optimized: {$escapedPdfPath} (\$ORIGINAL_SIZE -> \$OPT_SIZE bytes)" >> {$logFile}
    else
        echo "[$(date)] Skipped (not smaller): {$escapedPdfPath} (\$ORIGINAL_SIZE vs \$OPT_SIZE)" >> {$logFile}
    fi
fi

# Cleanup
rm -f {$tempCompressedPath} {$tempLinearizedPath}
BASH;

        // Write script to temp file and run in background
        $scriptPath = sys_get_temp_dir().'/pdf_optimize_'.md5($pdfAbsolutePath).'.sh';
        file_put_contents($scriptPath, $script);
        chmod($scriptPath, 0755);

        // nohup + & = runs in background, doesn't block PHP
        exec(sprintf('nohup bash %s > /dev/null 2>&1 &', escapeshellarg($scriptPath)));

        Log::info('PDF optimization started in background: '.basename($pdfAbsolutePath));
    }

    public function getReaders(Ebook $ebook)
    {
        $readers = $ebook->readers()
            ->select('users.id', 'users.name', 'users.email')
            ->get()
            ->map(function ($user) use ($ebook) {
                // Fetch user access logs for this specific ebook
                $logs = EbookAccessLog::where('ebook_id', $ebook->id)
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->get();

                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'last_access' => $logs->first()?->created_at?->diffForHumans() ?? 'N/A',
                    'actions_count' => $logs->count(),
                ];
            });

        return response()->json($readers);
    }

    public function tracking(Request $request): View
    {
        $search = $request->string('search')->trim();

        // Get all published eBooks, optionally filtered by search
        $ebooks = Ebook::query()
            ->where('status', 'published')
            ->when($search->isNotEmpty(), function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%")
                        ->orWhere('grade_level', 'like', "%{$search}%");
                });
            })
            ->with('creator:id,name,email')
            ->get();

        // Mappings from db grade_level to display groups
        $getDisplayGrades = function ($grade) {
            $grade = strtolower(trim($grade));
            $grade = str_replace('-', ' ', $grade);

            if ($grade === 'kindergarten') {
                return ['Kinder 1', 'Kinder 2'];
            }
            if ($grade === 'kinder 1') {
                return ['Kinder 1'];
            }
            if ($grade === 'kinder 2') {
                return ['Kinder 2'];
            }
            if ($grade === 'k11' || $grade === 'grade 11') {
                return ['Grade 11'];
            }
            if ($grade === 'k12' || $grade === 'grade 12') {
                return ['Grade 12'];
            }
            // Mappings for Grade 1 to 10
            if (preg_match('/^(?:grade\s*)?([1-9]|10)$/', $grade, $match)) {
                return ['Grade '.$match[1]];
            }

            // fallback
            return [ucwords($grade)];
        };

        // Group ebooks by display grade level matching the student tabs
        $displayGrades = [
            'Kinder 1',
            'Kinder 2',
            'Grade 1',
            'Grade 2',
            'Grade 3',
            'Grade 4',
            'Grade 5',
            'Grade 6',
            'Grade 7',
            'Grade 8',
            'Grade 9',
            'Grade 10',
            'Grade 11',
            'Grade 12',
        ];

        $gradeGroups = collect($displayGrades)->mapWithKeys(function ($grade) use ($ebooks, $getDisplayGrades) {
            $books = $ebooks->filter(fn ($book) => in_array($grade, $getDisplayGrades($book->grade_level), true))->values();

            return [$grade => $books];
        });

        $totalBooksCount = Ebook::count();
        $publishedCount = Ebook::where('status', 'published')->count();
        $gradesWithBooks = $gradeGroups->filter(fn ($books) => $books->isNotEmpty())->count();

        if ($request->boolean('print')) {
            return view('admin.ebook.print', [
                'gradeGroups' => $gradeGroups,
                'gradeLevels' => $displayGrades,
                'totalBooksCount' => $totalBooksCount,
                'publishedCount' => $publishedCount,
                'gradesWithBooks' => $gradesWithBooks,
            ]);
        }

        return view('admin.ebook.tracking', [
            'gradeGroups' => $gradeGroups,
            'gradeLevels' => $displayGrades,
            'totalBooksCount' => $totalBooksCount,
            'publishedCount' => $publishedCount,
            'gradesWithBooks' => $gradesWithBooks,
        ]);
    }
}
