<?php

namespace App\Jobs;

use App\Models\ExamMark;
use App\Models\InstituteDetail;
use setasign\Fpdi\Fpdi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\Support\Str;

class CertificateGenerateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;
    public int $instituteDetailsId;
    public int $exam_id;
    public string $fileName;
    public string $exportId;

    public string $associationName;
    public string $associationLogo;
    public string $associationAddress;

    public ?array $dtParams;
    public ?array $searchableColumns;

    public int $timeout = 7200;
    public int $tries = 2;

    public function __construct(
        int $userId,
        int $instituteDetailsId,
        int $exam_id,
        string $fileName,
        ?string $exportId = null,
        ?array $dtParams = [],
        ?array $searchableColumns = []
    ) {
        $this->userId = $userId;
        $this->instituteDetailsId = $instituteDetailsId;
        $this->exam_id = $exam_id;
        $this->fileName = $fileName;
        $this->exportId = $exportId ?? (string) Str::uuid();
        $this->dtParams = $dtParams;
        $this->searchableColumns = $searchableColumns;

        Log::channel('exports_log')->info("📝 Initializing Certificate PDF export [{$this->exportId}] for user {$this->userId}");
    }

    public function handle(): void
    {
        $lockKey = "cert_export_lock_{$this->userId}_{$this->exportId}";
        $progressKey = "export_progress_{$this->userId}_{$this->exportId}";
        $readyKey = "export_ready_{$this->userId}_{$this->exportId}";

        $lock = Cache::lock($lockKey, $this->timeout);

        try {
            if ($lock->get()) {
                Log::channel('exports_log')->info("🔒 Lock acquired for Certificate export [{$this->exportId}] user {$this->userId}");

                $finalFile = $this->generateCertificatesPdf($progressKey);

                Cache::put($readyKey, $finalFile, now()->addHours(1));
                Cache::put($progressKey, 100, now()->addHours(1));

                Log::channel('exports_log')->info("✅ Certificate PDF export completed [{$this->exportId}]: {$finalFile}");

                $lock->release();
            }
        } catch (Throwable $e) {
            if (isset($lock) && $lock->owner()) {
                $lock->release();
            }
            $this->failed($e);
            throw $e;
        }
    }

    private function generateCertificatesPdf(string $progressKey): string
    {
        $students = $this->getStudents();
        $total = max(1, count($students));

        // Use new certificate background
        $bgPath = public_path("certificates/pssb_certificate.png");

        $pdf = new Fpdi('L', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);

        // Load association info
        $assoc = InstituteDetail::find($this->instituteDetailsId);
        if ($assoc) {
            $this->associationName = $assoc->institute_name ?? '';
            $this->associationAddress = $assoc->institute_address ?? '';
            $this->associationLogo = $assoc->logo ?? '';
        }

        foreach ($students as $index => $s) {
            $pdf->AddPage();
            // ✔ Register all required custom fonts BEFORE any SetFont() calls
            $pdf->AddFont('CutiveMono-Regular', '', 'CutiveMono-Regular.php');
            $pdf->AddFont('OldEnglishFive', '', 'OldEnglishFive.php');
            $pdf->AddFont('Rye-Regular', '', 'Rye-Regular.php');
            $pdf->AddFont('Sunshine', '', 'Sunshine.php');

            // Background
            if (file_exists($bgPath)) {
                $pdf->Image($bgPath, 0, 0, 297, 210);
            }

            $app = $s->applicant ?? null;

            $studentName   = ucwords(trim($app->student_name_english ?? ($s->student_name_english ?? '---')));
            $fatherName    = ucwords(trim($app->father_name_english ?? ($s->father_name ?? '---')));
            $motherName    = ucwords(trim($app->mother_name_english ?? ($s->mother_name ?? '---')));
            $className     = ucwords(trim($app->class_name ?? ($s->class_name ?? '---')));
            $regNo         = strtoupper(trim($app->assigned_roll ?? ($s->assigned_roll ?? '---'))); // usually roll numbers are uppercase
            $instituteName = ucwords(trim($app->institute_name ?? ($s->institute_name ?? '---')));
            $examName      = ucwords(trim($s->exam->name ?? ''));

            $session       = trim($app->academic_year ?? '---');
            $obtainedMark  = trim($s->obtained_mark ?? '---');
            $obtainedGrade = trim($s->obtained_grade ?? '---');


            /*
    |--------------------------------------------------------------------------
    | Header Section
    |--------------------------------------------------------------------------
    */

            $pdf->SetFont("Helvetica", "", 12);
            $sessionX = 40;
            $sessionY = 60;

            $pdf->SetXY($sessionX, $sessionY);
            $pdf->Cell(100, 6, "Session: {$session}", 0, 0, 'L');

            // Get current date in YYYY-MM-DD format
            $slNo = $index + 1;
            $today = date('Y-m-d');

            // Combine date with session serial
            $serial = "{$today}-{$slNo}";

            // Set the PDF cell
            $pdf->SetXY($sessionX, $sessionY + 5);
            $pdf->Cell(100, 6, "Serial: {$serial}", 0, 0, 'L');

            // Logo above session
            $logoW = 20;
            $logoH = 20;
            $logoX = $sessionX + 3;
            $logoY = $sessionY - ($logoH + 5);

            $pdf->Rect($logoX, $logoY, $logoW, $logoH);

            if (!empty($this->associationLogo)) {
                $logoPath = Storage::disk('public')->path("{$this->associationLogo}");
                if (file_exists($logoPath)) {
                    $pdf->Image($logoPath, $logoX, $logoY, $logoW, $logoH);
                }
            }

            // Exam title
            $pdf->SetFont('OldEnglishFive', '', 28);
            $pdf->SetTextColor(0, 51, 102);

            $pdf->SetXY(20, 38);
            $pdf->Cell(257, 12, "{$examName}", 0, 0, 'C');

            $pdf->SetTextColor(0, 0, 0);

            $pdf->SetFont("Helvetica", "", 18);
            $pdf->SetXY(20, 50);
            $pdf->Cell(257, 6, "{$this->associationName}", 0, 0, 'C');

            $pdf->SetFont("Helvetica", "", 14);
            $pdf->SetXY(20, 56);
            $pdf->Cell(257, 6, "{$this->associationAddress}", 0, 0, 'C');


            /*
    |--------------------------------------------------------------------------
    | Certificate Title
    |--------------------------------------------------------------------------
    */

            $pdf->SetFont('Rye-Regular', '', 28);
            $pdf->SetTextColor(163, 0, 22);

            $pdf->SetXY(20, 68);
            $pdf->Cell(257, 6, "CERTFICATE", 0, 0, 'C');

            $pdf->SetTextColor(0, 0, 0);


            /*
            |--------------------------------------------------------------------------
            | Main Content
            |--------------------------------------------------------------------------
            */

            $contentWidth = 270;
            $leftMargin   = 20;

            // Intro
            $pdf->SetFont("Helvetica", "", 14);

            $introText   = "This is to certify that ";
            $studentName = trim($studentName);

            // Calculate widths
            $introWidth = $pdf->GetStringWidth($introText);
            $pdf->SetFont('Sunshine', '', 28);
            $nameWidth  = $pdf->GetStringWidth($studentName);
            $extra      = 6;
            $underlineWidth = $nameWidth + $extra;

            // Total width of whole line
            $totalWidth = $introWidth + $nameWidth;

            // Centered X
            $pageWidth = $pdf->GetPageWidth();
            $startX    = ($pageWidth - $totalWidth) / 2;
            $y         = 80;

            // Draw intro text
            $pdf->SetXY($startX, $y);
            $pdf->SetFont("Helvetica", "", 14);
            $pdf->Cell($introWidth, 10, $introText, 0, 0, 'L');

            // Draw student name
            $pdf->SetFont('Sunshine', '', 28);
            $pdf->Cell($nameWidth, 10, $studentName, 0, 1, 'L');

            // Underline
            $nameX = $startX + $introWidth - 3;
            $nameY = $y + 8;

            $pdf->SetDrawColor(150, 150, 150);
            $pdf->SetLineWidth(0.3);

            $dotLength = 1;
            $gapLength = 1;
            $currentX  = $nameX;

            while ($currentX < $nameX + $underlineWidth) {
                $pdf->Line($currentX, $nameY, $currentX + $dotLength, $nameY);
                $currentX += ($dotLength + $gapLength);
            }


            /*
            |--------------------------------------------------------------------------
            | Parents Line (Centered + Bold placeholders)
            |--------------------------------------------------------------------------
            */

            $pdf->SetFont("Helvetica", "", 14);
            $pdf->Ln(2);

            // Build full line for centering
            $line  = "son/daughter of Mr. " . $fatherName . " and Mrs. " . $motherName . ".";

            // Centering calculation
            $fullWidth = $pdf->GetStringWidth($line);
            $centerX   = ($pageWidth - $fullWidth) / 2;
            $pdf->SetX($centerX);

            // Write inline with bold placeholders
            $pdf->SetFont("Helvetica", "", 14);
            $pdf->Write(6, "son/daughter of Mr. ");

            $pdf->SetFont("Helvetica", "B", 14);
            $pdf->Write(6, $fatherName);

            $pdf->SetFont("Helvetica", "", 14);
            $pdf->Write(6, " and Mrs. ");

            $pdf->SetFont("Helvetica", "B", 14);
            $pdf->Write(6, $motherName . ".");

            $pdf->Ln(8);


            /*
            |--------------------------------------------------------------------------
            | Class + Reg (Centered + Bold placeholders)
            |--------------------------------------------------------------------------
            */

            $pdf->Ln(2);

            // Build full line
            $line  = "Class: " . $className;
            $line .= ", Reg. No: " . $regNo;
            $line .= ", is a student of: ";

            // Centering
            $fullWidth = $pdf->GetStringWidth($line);
            $centerX   = ($pageWidth - $fullWidth) / 2;
            $pdf->SetX($centerX);

            // Write inline
            $pdf->SetFont("Helvetica", "", 14);
            $pdf->Write(6, "Class: ");

            $pdf->SetFont("Helvetica", "B", 14);
            $pdf->Write(6, $className);

            $pdf->SetFont("Helvetica", "", 14);
            $pdf->Write(6, ", Reg. No: ");

            $pdf->SetFont("Helvetica", "B", 14);
            $pdf->Write(6, $regNo);

            $pdf->SetFont("Helvetica", "", 14);
            $pdf->Write(6, ", is a student of: ");

            $pdf->Ln(8);


            /*
            |--------------------------------------------------------------------------
            | Institute (Bold + Centered)
            |--------------------------------------------------------------------------
            */

            $pdf->Ln(2);
            $pdf->SetFont("Helvetica", "B", 14);
            $pdf->MultiCell($contentWidth, 6, $instituteName, 0, 'C');


            /*
            |--------------------------------------------------------------------------
            | Result Line (Centered + Bold placeholders)
            |--------------------------------------------------------------------------
            */

            $pdf->Ln(2);

            // Build line
            $line  = "He/She appeared at the " . $examName;
            $line .= " Examination and obtained " . $obtainedGrade . " Grade";

            // Centering
            $fullWidth = $pdf->GetStringWidth($line);
            $centerX   = ($pageWidth - $fullWidth) / 2;
            $pdf->SetX($centerX);

            // Inline write
            $pdf->SetFont("Helvetica", "", 14);
            $pdf->Write(6, "He/She appeared at the ");

            $pdf->SetFont("Helvetica", "B", 14);
            $pdf->Write(6, $examName);

            $pdf->SetFont("Helvetica", "", 14);
            $pdf->Write(6, " Examination and obtained ");

            $pdf->SetFont("Helvetica", "B", 14);
            $pdf->Write(6, $obtainedGrade);

            $pdf->SetFont("Helvetica", "", 14);
            $pdf->Write(6, " Grade");

            $pdf->Ln(10);


            /*
            |--------------------------------------------------------------------------
            | Closing Wish
            |--------------------------------------------------------------------------
            */

            $pdf->SetFont("Helvetica", "", 14);
            $pdf->Ln(2);
            $pdf->SetX($leftMargin);
            $pdf->MultiCell($contentWidth, 6, "We wish him/her all the success and well-being in life.", 0, 'C');



            /*
            |--------------------------------------------------------------------------
            | Signature Row
            |--------------------------------------------------------------------------
            */

            $pdf->SetFont("Helvetica", "", 10);

            // Left
            $pdf->SetXY(30, 160);
            $pdf->Cell(80, 5, "Controller of Examination", 0, 0, 'C');
            $pdf->SetXY(30, 165);
            $pdf->Cell(80, 5, "{$this->associationName}", 0, 0, 'C');

            // Middle
            $pdf->SetXY(108.5, 160);
            $pdf->Cell(80, 5, "General Secretary", 0, 0, 'C');
            $pdf->SetXY(108.5, 165);
            $pdf->Cell(80, 5, "{$this->associationName}", 0, 0, 'C');

            // Right
            $pdf->SetXY(187, 160);
            $pdf->Cell(80, 5, "Chairman", 0, 0, 'C');
            $pdf->SetXY(187, 165);
            $pdf->Cell(80, 5, "{$this->associationName}", 0, 0, 'C');

            /*
            |--------------------------------------------------------------------------
            | Progress Updating
            |--------------------------------------------------------------------------
            */

            $progress = (int)((($index + 1) / $total) * 100);
            Cache::put($progressKey, $progress, now()->addHours(1));
        }


        // Save generated PDF
        $dir = "exports/user_{$this->userId}/certificates/{$this->exportId}";
        Storage::disk('public')->makeDirectory($dir);

        $file = "{$dir}/{$this->fileName}.pdf";
        $abs = Storage::disk('public')->path($file);
        $pdf->Output($abs, 'F');

        return $file;
    }

    private function getStudents()
    {
        return ExamMark::with('applicant', 'exam')
            ->where('exam_id', $this->exam_id)
            ->orderByDesc('obtained_mark')
            ->get();
    }

    public function failed(Throwable $e): void
    {
        $progressKey = "export_progress_{$this->userId}_{$this->exportId}";
        Cache::put($progressKey, -1, now()->addMinutes(30));

        Log::channel('exports_log')->error("❗Certificate export failed [{$this->exportId}]: " . $e->getMessage(), [
            'exception' => $e,
        ]);
    }
}
