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

            $studentName   = trim($app->student_name_english ?? ($s->student_name_english ?? '---'));
            $fatherName    = trim($app->father_name_english ?? ($s->father_name ?? '---'));
            $motherName    = trim($app->mother_name_english ?? ($s->mother_name ?? '---'));
            $className     = trim($app->class_name ?? ($s->class_name ?? '---'));
            $regNo         = trim($app->assigned_roll ?? ($s->assigned_roll ?? '---'));
            $instituteName = trim($app->institute_name ?? ($s->institute_name));
            $examName      = trim($s->exam->name ?? '');
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

            $pdf->SetXY($sessionX, $sessionY + 3);
            $pdf->Cell(100, 6, "Serial: {$session}", 0, 0, 'L');

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

            $pdf->SetXY(20, 65);
            $pdf->Cell(257, 6, "CERTFICATE", 0, 0, 'C');

            $pdf->SetTextColor(0, 0, 0);


            /*
    |--------------------------------------------------------------------------
    | Main Content
    |--------------------------------------------------------------------------
    */

            $leftMargin   = 20;
            $contentWidth = 257;

            $pdf->SetXY($leftMargin, 80);

            // Line 1 - intro
            $pdf->SetFont("CutiveMono-Regular", "", 14);
            $pdf->Cell($contentWidth, 6, "This is to certify that", 0, 1, 'C');

            // Line 2 - student name
            $pdf->SetFont('Sunshine', '', 28);
            $studentName = trim($studentName);
            $pdf->Cell(0, 10, $studentName, 0, 1, 'C');

            // Dotted underline
            $nameWidth = $pdf->GetStringWidth($studentName);
            $pageWidth = $pdf->GetPageWidth();
            $extra     = 6;
            $underlineWidth = $nameWidth + $extra;

            $nameX = ($pageWidth - $underlineWidth) / 2;
            $nameY = $pdf->GetY() - 1.5;

            $pdf->SetDrawColor(150, 150, 150);
            $pdf->SetLineWidth(0.3);

            $dotLength = 1;
            $gapLength = 1;

            $currentX = $nameX;

            while ($currentX < $nameX + $underlineWidth) {
                $pdf->Line($currentX, $nameY, $currentX + $dotLength, $nameY);
                $currentX += ($dotLength + $gapLength);
            }

            // Parents
            $pdf->SetFont("CutiveMono-Regular", "", 14);
            $pdf->Ln(2);
            $pdf->Cell(0, 6, "son/daughter of Mr. {$fatherName} and Mrs. {$motherName}", 0, 1, 'C');

            // Class + Reg
            $pdf->Ln(2);
            $pdf->SetX($leftMargin);
            $pdf->MultiCell($contentWidth, 6, "Class: {$className} | Registration No.: {$regNo}, is a student of {$instituteName}", 0, 'C');

            // Institute
            // $pdf->Ln(2);
            // $pdf->SetX($leftMargin);
            // $pdf->MultiCell($contentWidth, 6, "is a student of {$instituteName}", 0, 'C');

            // Result
            $pdf->Ln(2);
            $pdf->SetX($leftMargin);
            $pdf->MultiCell($contentWidth, 6, "He/She appeared at the {$examName} Examination and obtained {$obtainedGrade} Grade", 0, 'C');

            // Closing wish
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
