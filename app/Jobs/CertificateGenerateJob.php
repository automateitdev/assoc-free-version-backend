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
        }

        foreach ($students as $index => $s) {

            $pdf->AddPage();

            if (file_exists($bgPath)) {
                $pdf->Image($bgPath, 0, 0, 297, 210);
            }

            $app = $s->applicant ?? null;

            $studentName = $app->student_name_english ?? ($s->student_name_english ?? '---');
            $fatherName = $app->father_name_english ?? ($s->father_name ?? '---');
            $motherName = $app->mother_name_english ?? ($s->mother_name ?? '---');
            $className = $app->class_name ?? ($s->class_name ?? '---');
            $regNo = $app->unique_number ?? ($s->unique_number ?? '---');
            $instituteName = $app->institute_name ?? ($s->institute_name);
            $examName = $s->exam->name ?? '';
            $session = $app->academic_year ?? '---';
            $obtainedMark = $s->obtained_mark ?? '---';
            $obtainedGrade = $s->obtained_grade ?? '---';

            // 📌 START DRAWING (Compact layout inside ornate border)

            // --- Header Section ---
            $pdf->SetFont("Times", "", 13);
            $pdf->SetXY(35, 50);
            $pdf->Cell(100, 6, "Session: {$session}", 0, 0, 'L');

            $pdf->SetFont("Times", "B", 20);
            $pdf->SetXY(20, 35);
            $pdf->Cell(257, 8, "{$examName}", 0, 0, 'C');

            $pdf->SetFont("Times", "", 18);
            $pdf->SetXY(20, 48);
            $pdf->Cell(257, 6, "{$this->associationName}", 0, 0, 'C');

            $pdf->SetFont("Times", "", 14);
            $pdf->SetXY(20, 54);
            $pdf->Cell(257, 6, "{$this->associationAddress}", 0, 0, 'C');

            // --- Main Content ---
            $pdf->SetFont("Times", "", 14);
            $pdf->SetXY(20, 80);
            $pdf->MultiCell(257, 5, "This is to certify that {$studentName} son/daughter of", 0, 'C');

            $pdf->SetXY(20, 88);
            $pdf->MultiCell(257, 5, "Mr. {$fatherName} and Mrs. {$motherName}", 0, 'C');

            $pdf->SetXY(20, 96);
            $pdf->MultiCell(257, 5, "Class: {$className}      |      Registration No.: {$regNo}", 0, 'C');

            $pdf->SetXY(20, 104);
            $pdf->MultiCell(257, 5, "is a student of {$instituteName}", 0, 'C');

            $pdf->SetXY(20, 112);
            $pdf->MultiCell(257, 5, "He/She appeared at the {$examName} Examination and obtained {$obtainedGrade} Grade", 0, 'C');

            $pdf->SetFont("Times", "I", 14);
            $pdf->SetXY(20, 125);
            $pdf->MultiCell(257, 5, "We wish him/her all the success and well-being in life.", 0, 'C');


            // --- Signatures Row ---
            $pdf->SetFont("Times", "", 10);

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

            // Progress update
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
