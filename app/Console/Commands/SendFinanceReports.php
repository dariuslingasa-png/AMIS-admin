<?php

namespace App\Console\Commands;

use App\Http\Controllers\Traits\PaymentHelperTrait;
use App\Models\Payment;
use App\Support\EnrollmentStorage;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendFinanceReports extends Command
{
    use PaymentHelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:send-reports {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and email batch PDF finance reports of family payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Initializing finance reports generation for {$email}...");

        // Register custom PSR-4 autoloaders for Dompdf and its dependencies
        spl_autoload_register(function ($class) {
            $prefix = 'Dompdf\\';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }
            $relative_class = substr($class, $len);

            // Check src/ first, then fallback to lib/
            $file = base_path('vendor/dompdf/dompdf/src/').str_replace('\\', '/', $relative_class).'.php';
            if (! file_exists($file)) {
                $file = base_path('vendor/dompdf/dompdf/lib/').str_replace('\\', '/', $relative_class).'.php';
            }

            if (file_exists($file)) {
                require $file;
            }
        });

        spl_autoload_register(function ($class) {
            $prefix = 'Sabberworm\\CSS\\';
            $base_dir = base_path('vendor/sabberworm/php-css-parser/src/');
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }
            $relative_class = substr($class, $len);
            $file = $base_dir.str_replace('\\', '/', $relative_class).'.php';
            if (file_exists($file)) {
                require $file;
            }
        });

        spl_autoload_register(function ($class) {
            $prefix = 'Svg\\';
            $base_dir = base_path('vendor/dompdf/php-svg-lib/src/Svg/');
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }
            $relative_class = substr($class, $len);
            $file = $base_dir.str_replace('\\', '/', $relative_class).'.php';
            if (file_exists($file)) {
                require $file;
            }
        });

        spl_autoload_register(function ($class) {
            $prefix = 'FontLib\\';
            $base_dir = base_path('vendor/dompdf/php-font-lib/src/FontLib/');
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }
            $relative_class = substr($class, $len);
            $file = $base_dir.str_replace('\\', '/', $relative_class).'.php';
            if (file_exists($file)) {
                require $file;
            }
        });

        spl_autoload_register(function ($class) {
            $prefix = 'Masterminds\\';
            $base_dir = base_path('vendor/masterminds/html5/src/');
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }
            $relative_class = substr($class, $len);
            $file = $base_dir.str_replace('\\', '/', $relative_class).'.php';
            if (file_exists($file)) {
                require $file;
            }
        });

        // Fetch all verified/pending payments
        $query = Payment::with('applicant.user')->latest();
        $familyRows = $this->paymentFamilyRows($query->get());

        // Filter and sort families with payments
        $families = $familyRows->filter(function ($family) {
            return $family['payments']->isNotEmpty();
        })->sortBy('family_no')->values();

        $totalFamilies = $families->count();
        $this->info("Found {$totalFamilies} families with payment submissions.");

        if ($totalFamilies === 0) {
            $this->warn('No families with payment submissions found. Exiting.');

            return Command::SUCCESS;
        }

        // Chunk by 20 families per report PDF to match the screen pagination
        $perPage = 20;
        $chunks = $families->chunk($perPage);
        $totalParts = $chunks->count();

        $this->info("Chunked into {$totalParts} parts (max {$perPage} families per PDF).");

        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $index = 0;
        foreach ($chunks as $chunk) {
            $partNumber = $index + 1;
            $this->info("Processing Part {$partNumber} of {$totalParts}...");

            // Prepare compressed absolute local image paths for Dompdf rendering
            foreach ($chunk as $family) {
                foreach ($family['payments'] as $payment) {
                    if (! $payment->receipt_url) {
                        $payment->rendered_image_path = null;

                        continue;
                    }

                    $absPath = EnrollmentStorage::getAbsolutePath($payment->receipt_url);
                    if (! $absPath || ! is_file($absPath)) {
                        $payment->rendered_image_path = null;

                        continue;
                    }

                    $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
                    if ($ext === 'pdf') {
                        // PDF: Convert page 1 to JPG first, then compress
                        $pdfJpg = $this->convertPdfToJpg($absPath);
                        $payment->rendered_image_path = $pdfJpg ? $this->compressImage($pdfJpg) : null;
                    } else {
                        // Image: Compress directly
                        $payment->rendered_image_path = $this->compressImage($absPath);
                    }
                }
            }

            // Render view to HTML
            $html = view('admin.payments.pdf-report', [
                'families' => $chunk,
                'part' => $partNumber,
                'totalParts' => $totalParts,
            ])->render();

            // Generate PDF via Dompdf
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $pdfOutput = $dompdf->output();
            $tempPdfPath = storage_path("app/finance_report_{$partNumber}.pdf");
            file_put_contents($tempPdfPath, $pdfOutput);

            // Email the PDF report
            $this->info("Sending email with finance_report{$partNumber}.pdf to {$email}...");
            Mail::send([], [], function ($message) use ($email, $tempPdfPath, $partNumber, $totalParts) {
                $message->to($email)
                    ->subject("AMIS Finance Report - Part {$partNumber} of {$totalParts}")
                    ->html("Hello,<br><br>Attached is the AMIS Finance Payment Ledger - Part {$partNumber} of {$totalParts}.<br><br>Best regards,<br>AMIS Portal")
                    ->attach($tempPdfPath, [
                        'as' => "finance_report{$partNumber}.pdf",
                        'mime' => 'application/pdf',
                    ]);
            });

            // Cleanup local temporary PDF
            if (is_file($tempPdfPath)) {
                unlink($tempPdfPath);
            }

            $this->info("Part {$partNumber} sent successfully.");

            // Add a 2-second rate-limiting delay between emails to protect SMTP queue
            sleep(2);

            $index++;
        }

        // Clean up temporary converted JPG files
        $this->info('Cleaning up temporary receipt images...');
        $tmpDir = storage_path('app/tmp_receipts');
        if (is_dir($tmpDir)) {
            $files = glob($tmpDir.'/*.jpg');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            @rmdir($tmpDir);
        }

        $this->info('All finance reports sent successfully!');

        return Command::SUCCESS;
    }

    /**
     * Convert the first page of a PDF file to a JPG image using Ghostscript (optimized DPI).
     */
    private function convertPdfToJpg(string $absolutePdfPath): ?string
    {
        if (! is_file($absolutePdfPath)) {
            return null;
        }

        $tmpDir = storage_path('app/tmp_receipts');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $hash = md5($absolutePdfPath);
        $jpgPath = $tmpDir.'/'.$hash.'.jpg';

        if (is_file($jpgPath)) {
            return $jpgPath;
        }

        $escapedOut = escapeshellarg($jpgPath);
        $escapedIn = escapeshellarg($absolutePdfPath);

        // Command to render page 1 of PDF to JPG using Ghostscript at 90 DPI
        $cmd = "/usr/bin/gs -dNOPAUSE -sDEVICE=jpeg -r90 -sPageList=1 -sOutputFile={$escapedOut} {$escapedIn} -c quit 2>&1";

        exec($cmd, $output, $resultCode);

        if ($resultCode === 0 && is_file($jpgPath)) {
            return $jpgPath;
        }

        return null;
    }

    /**
     * Compress and resize receipt images using PHP GD library.
     * Downscales to 600px width and saves at 60% JPEG quality.
     */
    private function compressImage(string $sourcePath): ?string
    {
        if (! is_file($sourcePath)) {
            return null;
        }

        $tmpDir = storage_path('app/tmp_receipts');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $hash = md5($sourcePath.'_compressed');
        $destPath = $tmpDir.'/'.$hash.'.jpg';

        if (is_file($destPath)) {
            return $destPath;
        }

        $info = @getimagesize($sourcePath);
        if (! $info) {
            return null;
        }

        $mime = $info['mime'];
        $srcImg = null;

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $srcImg = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $srcImg = @imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $srcImg = @imagecreatefromwebp($sourcePath);
                break;
            case 'image/gif':
                $srcImg = @imagecreatefromgif($sourcePath);
                break;
        }

        if (! $srcImg) {
            return null;
        }

        $width = imagesx($srcImg);
        $height = imagesy($srcImg);

        // Resize if wider than 600px
        $newWidth = 600;
        if ($width > $newWidth) {
            $newHeight = (int) (($height / $width) * $newWidth);
            $tmpImg = imagecreatetruecolor($newWidth, $newHeight);

            // White background for transparent images
            $white = imagecolorallocate($tmpImg, 255, 255, 255);
            imagefill($tmpImg, 0, 0, $white);

            imagecopyresampled($tmpImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($srcImg);
            $srcImg = $tmpImg;
        }

        // Save as compressed JPG (60% quality)
        $success = imagejpeg($srcImg, $destPath, 60);
        imagedestroy($srcImg);

        return $success && is_file($destPath) ? $destPath : null;
    }
}
