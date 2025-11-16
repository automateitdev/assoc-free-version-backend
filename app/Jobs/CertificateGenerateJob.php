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

    /**
     * Create a new job instance.
     *
     * @param int $userId
     * @param int $instituteDetailsId
     * @param int $exam_id
     * @param string $fileName
     * @param string|null $exportId
     * @param array|null $dtParams
     * @param array|null $searchableColumns
     */
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


    /**
     * Generate certificates PDF using FPDI with the provided PNG background.
     *
     * @param string $progressKey
     * @return string relative path saved to storage disk 'public'
     * @throws \setasign\Fpdi\FpdiException
     */
    private function generateCertificatesPdf(string $progressKey): string
    {
        $students = $this->getStudents();

        // FPDI in landscape A4
        $pdf = new Fpdi('L', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);

        // Background image path (put your PNG into public/certificates/pssb_certificate.png)
        // You can also change to public_path("certificate_bg.png") or Storage::disk('public')->path(...)
        $bgPath = public_path("certificates/pssb_certificate.png");

        // Make sure institute info (optional)
        $assoc = InstituteDetail::find($this->instituteDetailsId);
        if ($assoc) {
            $this->associationName = $assoc->institute_name ?? '';
            $this->associationAddress = $assoc->institute_address ?? '';
        }

        $total = max(1, count($students));

        foreach ($students as $index => $s) {
            // Each certificate one page
            $pdf->AddPage();

            // Draw background image to fill A4 landscape (297 x 210 mm)
            if (file_exists($bgPath)) {
                $pdf->Image($bgPath, 0, 0, 297, 210);
            }

            // Candidate data: support two shapes of $s:
            // - $s is an ExamMark with relation applicant (preferred)
            // - or $s itself contains required fields
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

            // All positioning is in mm. These numbers were tuned to match the provided PNG.
            // You can adjust X/Y to get pixel-perfect alignment on your server.
            // Title (already part of the background image) - we won't draw it here.

            // "This is to certify that ...................................... son/daughter of"
            // We'll place "This is to certify that" and then the student's name centered in the dotted area.
            $pdf->SetFont("Times", "", 12);
            $pdf->SetTextColor(30, 30, 30);

            // Line: "This is to certify that" — left text
            $pdf->SetXY(24, 82);
            $pdf->Cell(120, 6, "This is to certify that", 0, 0, 'L');

            // Student name (on the dotted line) — long centred area
            // The dotted line spans center of the page; use a centered cell within main area
            $pdf->SetFont("Times", "B", 14);
            // Width chosen to roughly match dotted length in template
            $pdf->SetXY(70, 82);
            $pdf->Cell(155, 6, $studentName, 0, 0, 'C');

            // "son/daughter of" (right side)
            $pdf->SetFont("Times", "", 12);
            $pdf->SetXY(232, 82);
            $pdf->Cell(50, 6, "son/daughter of", 0, 0, 'L');

            // Next line: "Mr. ... and ..."
            $pdf->SetFont("Times", "", 12);
            $pdf->SetXY(24, 95);
            // We'll format "Mr. FATHER and MOTHER"
            $line = "Mr. {$fatherName} and {$motherName}";
            $pdf->Cell(260, 6, $line, 0, 0, 'L');

            // Class and Registration No.
            $pdf->SetFont("Times", "", 12);
            $pdf->SetXY(24, 108);
            $pdf->Cell(40, 6, "Class:", 0, 0, 'L');

            $pdf->SetFont("Times", "B", 12);
            $pdf->SetXY(40, 108);
            // Print class on dotted area
            $pdf->Cell(80, 6, $className, 0, 0, 'L');

            $pdf->SetFont("Times", "", 12);
            $pdf->SetXY(135, 108);
            $pdf->Cell(60, 6, "Registration. No.:", 0, 0, 'L');

            $pdf->SetFont("Times", "B", 12);
            $pdf->SetXY(170, 108);
            $pdf->Cell(60, 6, $regNo, 0, 0, 'L');

            // "is a student of [Institute]" (next dotted line)
            $pdf->SetFont("Times", "", 12);
            $pdf->SetXY(24, 121);
            $pdf->Cell(260, 6, "is a student of {$instituteName}", 0, 0, 'L');

            // "He/She appeared at the Talent Scholarship Examination ... and obtained ... Grade."
            $pdf->SetFont("Times", "", 12);
            $pdf->SetXY(24, 134);
            $examLine = "He/She appeared at the Talent Scholarship Examination {$examName} and obtained {$obtainedMark}";
            if (!empty($grade) && $grade !== '---') {
                $examLine .= " (Grade: {$grade})";
            }
            $pdf->Cell(260, 6, $examLine, 0, 0, 'L');

            // Footer message: "We wish him/her all the success and well being in life."
            $pdf->SetFont("Times", "I", 11);
            $pdf->SetXY(24, 152);
            $pdf->Cell(260, 6, "We wish him/her all the success and well being in life.", 0, 0, 'L');

            // Session on left side under header: "Session: 2024"
            $pdf->SetFont("Times", "", 10);
            $pdf->SetXY(24, 46);
            $pdf->Cell(60, 5, "Session: {$session}", 0, 0, 'L');

            // Sl. No. area (right of Session)
            // we don't have serial, leave blank or use index+1
            $serial = $index + 1;
            $pdf->SetXY(260, 46);
            $pdf->Cell(30, 5, "Sl. No. {$serial}", 0, 0, 'R');

            // Signature lines: place approximate labels above signature images in footer
            $pdf->SetFont("Times", "", 9);
            // Left signature label
            $pdf->SetXY(36, 174);
            $pdf->Cell(80, 5, "Controller of Examination", 0, 0, 'C');
            $pdf->SetXY(36, 179);
            $pdf->Cell(80, 5, "Private School Society of Bangladesh", 0, 0, 'C');

            // Middle signature label
            $pdf->SetXY(140, 174);
            $pdf->Cell(80, 5, "General Secretary", 0, 0, 'C');
            $pdf->SetXY(140, 179);
            $pdf->Cell(80, 5, "Private School Society of Bangladesh", 0, 0, 'C');

            // Right signature label
            $pdf->SetXY(244, 174);
            $pdf->Cell(80, 5, "Chairman", 0, 0, 'C');
            $pdf->SetXY(244, 179);
            $pdf->Cell(80, 5, "Private School Society of Bangladesh", 0, 0, 'C');

            // Update progress
            $progress = intval((($index + 1) / $total) * 100);
            Cache::put($progressKey, $progress, now()->addHours(1));
        }

        // Save output to public disk
        $dir = "exports/user_{$this->userId}/certificates/{$this->exportId}";
        Storage::disk('public')->makeDirectory($dir);

        $file = "{$dir}/{$this->fileName}.pdf";
        $absPath = Storage::disk('public')->path($file);

        // Save file
        $pdf->Output($absPath, 'F');

        return $file;
    }


    /**
     * Get students for this export. Uses ExamMark with applicant relation.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getStudents()
    {
        $query = ExamMark::with('applicant')
            ->where('exam_id', $this->exam_id)
            ->orderByDesc('obtained_mark');

        // If datatable params applied (optional) you can process them here (kept simple)
        return $query->get();
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
