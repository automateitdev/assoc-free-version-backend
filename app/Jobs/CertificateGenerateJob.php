<?php

namespace App\Jobs;

use App\Models\AdmissionApplied;
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
    public int $academic_year_id;
    public int $class_id;
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
        int $academic_year_id,
        int $class_id,
        int $exam_id,
        string $fileName,
        ?string $exportId = null,
        ?array $dtParams = [],
        ?array $searchableColumns = []
    ) {
        $this->userId = $userId;
        $this->instituteDetailsId = $instituteDetailsId;
        $this->academic_year_id = $academic_year_id;
        $this->class_id = $class_id;
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

                $file = $this->generateCertificatesPdf($progressKey);

                Cache::put($readyKey, $file, now()->addHours(1));
                Cache::put($progressKey, 100, now()->addHours(1));

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

        $pdf = new Fpdi('L', 'mm', 'A4'); // landscape
        $pdf->SetAutoPageBreak(false);

        $bgPath = public_path("pssb_certificate.png");

        $total = count($students);

        foreach ($students as $index => $s) {

            $pdf->AddPage();

            // Add background image
            if (file_exists($bgPath)) {
                $pdf->Image($bgPath, 0, 0, 297, 210);
            }

            $pdf->SetFont("Arial", "", 16);

            // Positioning — adjust to match your uploaded certificate
            $pdf->SetXY(40, 80);
            $pdf->Cell(200, 10, "This is to certify that {$s->student_name_english}", 0, 1, 'C');

            $pdf->SetXY(40, 95);
            $pdf->Cell(200, 10, "Institute: {$s->institute_name}", 0, 1, 'C');

            $pdf->SetXY(40, 110);
            $pdf->Cell(200, 10, "Class: {$s->class_name}", 0, 1, 'C');

            $pdf->SetXY(40, 125);
            $pdf->Cell(200, 10, "Unique ID: {$s->unique_number}", 0, 1, 'C');

            $pdf->SetXY(40, 140);
            $pdf->Cell(200, 10, "Obtained Mark: {$s->obtained_mark}", 0, 1, 'C');


            // Update progress
            $progress = intval((($index + 1) / $total) * 100);
            Cache::put($progressKey, $progress, now()->addHours(1));
        }

        // Save output
        $dir = "exports/user_{$this->userId}/certificates/{$this->exportId}";
        Storage::disk('public')->makeDirectory($dir);

        $file = "{$dir}/{$this->fileName}.pdf";
        $pdf->Output(Storage::disk('public')->path($file), 'F');

        return $file;
    }


    private function getStudents()
    {
        return ExamMark::with('applicant')
        ->where('exam_id', $this->exam_id)
        ->orderBy('obtained_mark')
        ->get();
    }


    public function failed(Throwable $e): void
    {
        $progressKey = "export_progress_{$this->userId}_{$this->exportId}";
        Cache::put($progressKey, -1, now()->addMinutes(30));

        Log::error("Certificate export failed: " . $e->getMessage());
    }
}
