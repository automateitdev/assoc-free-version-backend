<?php

namespace App\Jobs;

use App\Models\ExamMark;
use App\Models\InstituteDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
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
    }

    public function handle()
    {
        $lockKey = "cert_export_lock_{$this->userId}_{$this->exportId}";
        $progressKey = "export_progress_{$this->userId}_{$this->exportId}";
        $readyKey = "export_ready_{$this->userId}_{$this->exportId}";

        $lock = Cache::lock($lockKey, $this->timeout);

        try {
            if ($lock->get()) {
                Log::channel('exports_log')->info("Certificate export started: {$this->exportId} by user {$this->userId}");

                $file = $this->generateCertificatesPdf($progressKey);

                Cache::put($readyKey, $file, now()->addHours(1));
                Cache::put($progressKey, 100, now()->addHours(1));

                Log::channel('exports_log')->info("Certificate export finished: {$this->exportId} file={$file}");

                $lock->release();
            } else {
                Log::channel('exports_log')->warning("Could not acquire lock for certificate export: {$this->exportId}");
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
        $totalStudents = ExamMark::where('exam_id', $this->exam_id)->count();
        $totalStudents = max(1, $totalStudents);

        $pdf = new Fpdi('L', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);

        $bgPath = public_path("certificates/pssb_certificate.png");

        $assoc = InstituteDetail::find($this->instituteDetailsId);
        if ($assoc) {
            $this->associationName = $assoc->institute_name ?? '';
            $this->associationAddress = $assoc->institute_address ?? '';
        }

        $processed = 0;

        // Process students in chunks to prevent memory issues
        ExamMark::with('applicant')
            ->where('exam_id', $this->exam_id)
            ->orderByDesc('obtained_mark')
            ->chunk(50, function ($studentsChunk) use ($pdf, $bgPath, &$processed, $totalStudents, $progressKey) {
                foreach ($studentsChunk as $index => $s) {
                    $app = $s->applicant ?? null;

                $studentName = $app->student_name_english ?? ($s->student_name_english ?? '---');
                $fatherName = $app->father_name ?? ($s->father_name ?? '---');
                $motherName = $app->mother_name ?? ($s->mother_name ?? '---');
                $className = $app->class_name ?? ($s->class_name ?? '---');
                $regNo = $app->unique_number ?? ($s->unique_number ?? '---');
                $instituteName = $app->institute_name ?? ($s->institute_name ?? $this->associationName ?? '---');
                $examName = $s->exam_name ?? ($this->associationName ?? 'Talent');
                $session = $app->academic_year ?? ($s->academic_year ?? '2024');
                $obtainedMark = $s->obtained_mark ?? ($s->mark ?? '---');
                $grade = $s->grade ?? ($s->obtained_grade ?? '---');

                $pdf->AddPage();
                if (file_exists($bgPath)) {
                    $pdf->Image($bgPath, 0, 0, 297, 210);
                }

                $pdf->SetFont("Times", "", 12);
                $pdf->SetTextColor(30, 30, 30);

                $pdf->SetXY(24, 82);
                $pdf->Cell(120, 6, "This is to certify that", 0, 0, 'L');

                $pdf->SetFont("Times", "B", 14);
                $pdf->SetXY(70, 82);
                $pdf->Cell(155, 6, $studentName, 0, 0, 'C');

                $pdf->SetFont("Times", "", 12);
                $pdf->SetXY(232, 82);
                $pdf->Cell(50, 6, "son/daughter of", 0, 0, 'L');

                $pdf->SetXY(24, 95);
                $pdf->Cell(260, 6, "Mr. {$fatherName} and {$motherName}", 0, 0, 'L');

                $pdf->SetXY(24, 108);
                $pdf->Cell(40, 6, "Class:", 0, 0, 'L');

                $pdf->SetFont("Times", "B", 12);
                $pdf->SetXY(40, 108);
                $pdf->Cell(80, 6, $className, 0, 0, 'L');

                $pdf->SetFont("Times", "", 12);
                $pdf->SetXY(135, 108);
                $pdf->Cell(60, 6, "Registration. No.:", 0, 0, 'L');

                $pdf->SetFont("Times", "B", 12);
                $pdf->SetXY(170, 108);
                $pdf->Cell(60, 6, $regNo, 0, 0, 'L');

                $pdf->SetFont("Times", "", 12);
                $pdf->SetXY(24, 121);
                $pdf->Cell(260, 6, "is a student of {$instituteName}", 0, 0, 'L');

                $pdf->SetXY(24, 134);
                $examLine = "He/She appeared at the Talent Scholarship Examination {$examName} and obtained {$obtainedMark}";
                if (!empty($grade) && $grade !== '---') {
                    $examLine .= " (Grade: {$grade})";
                }
                $pdf->Cell(260, 6, $examLine, 0, 0, 'L');

                $pdf->SetFont("Times", "I", 11);
                $pdf->SetXY(24, 152);
                $pdf->Cell(260, 6, "We wish him/her all the success and well being in life.", 0, 0, 'L');

                $pdf->SetFont("Times", "", 10);
                $pdf->SetXY(24, 46);
                $pdf->Cell(60, 5, "Session: {$session}", 0, 0, 'L');

                $serial = $processed + 1;
                $pdf->SetXY(260, 46);
                $pdf->Cell(30, 5, "Sl. No. {$serial}", 0, 0, 'R');

                // Signatures
                $pdf->SetFont("Times", "", 9);
                $pdf->SetXY(36, 174);
                $pdf->Cell(80, 5, "Controller of Examination", 0, 0, 'C');
                $pdf->SetXY(36, 179);
                $pdf->Cell(80, 5, "Private School Society of Bangladesh", 0, 0, 'C');

                $pdf->SetXY(140, 174);
                $pdf->Cell(80, 5, "General Secretary", 0, 0, 'C');
                $pdf->SetXY(140, 179);
                $pdf->Cell(80, 5, "Private School Society of Bangladesh", 0, 0, 'C');

                $pdf->SetXY(244, 174);
                $pdf->Cell(80, 5, "Chairman", 0, 0, 'C');
                $pdf->SetXY(244, 179);
                $pdf->Cell(80, 5, "Private School Society of Bangladesh", 0, 0, 'C');

                // Update progress
                $processed++;
                $progress = intval(($processed / $totalStudents) * 100);
                Cache::put($progressKey, $progress, now()->addHours(1));
                Log::channel('exports_log')->info("Progress: {$progress}% ({$processed}/{$totalStudents})");
            }
            });

        // Save output to public disk
        $dir = "exports/user_{$this->userId}/certificates/{$this->exportId}";
        Storage::disk('public')->makeDirectory($dir);

        $file = "{$dir}/{$this->fileName}.pdf";
        $absPath = Storage::disk('public')->path($file);

        $pdf->Output($absPath, 'F');

        return $file;
    }

    public function failed(Throwable $e): void
    {
        $progressKey = "export_progress_{$this->userId}_{$this->exportId}";
        Cache::put($progressKey, -1, now()->addMinutes(30));

        Log::channel('exports_log')->error("Certificate export failed: " . $e->getMessage(), [
            'exportId' => $this->exportId,
            'exception' => $e,
        ]);
    }
}
