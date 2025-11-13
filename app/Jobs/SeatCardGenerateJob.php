<?php

namespace App\Jobs;

use App\Models\AdmissionApplied;
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
use Illuminate\Support\Str;
use Throwable;

class SeatCardGenerateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;
    public int $instituteDetailsId;
    public int $academic_year_id;
    public int $class_id;
    public array $centers;
    public string $examName;
    public string $fileName;
    public string $exportId;

    public string $associationName;
    public string $associationAddress;
    public int $timeout = 7200;
    public int $tries = 2;

    public function __construct(
        int $userId,
        int $instituteDetailsId,
        int $academic_year_id,
        int $class_id,
        array $centers,
        string $examName,
        string $fileName,
        ?string $exportId = null
    ) {
        $this->userId = $userId;
        $this->instituteDetailsId = $instituteDetailsId;
        $this->academic_year_id = $academic_year_id;
        $this->class_id = $class_id;
        $this->centers = $centers;
        $this->examName = $examName;
        $this->fileName = $fileName;
        $this->exportId = $exportId ?? (string) Str::uuid();

        Log::channel('exports_log')->info("🧾 Initializing Seat Card PDF export [{$this->exportId}] for user {$this->userId}");
    }

    public function handle(): void
    {
        $lockKey = "seat_card_export_lock_{$this->userId}_{$this->exportId}";
        $progressKey = "export_progress_{$this->userId}_{$this->exportId}";
        $readyKey = "export_ready_{$this->userId}_{$this->exportId}";

        $lock = Cache::lock($lockKey, $this->timeout);

        try {
            if ($lock->get()) {
                Log::channel('exports_log')->info("🔒 Lock acquired for export [{$this->exportId}] user {$this->userId}");

                // Generate seat cards PDF
                $finalPath = $this->generateSeatCardsPdf($progressKey);

                Cache::put($readyKey, $finalPath, now()->addHours(1));
                Cache::put($progressKey, 100, now()->addHours(1));

                Log::channel('exports_log')->info("✅ PDF export [{$this->exportId}] completed. file={$finalPath}");

                $lock->release();
            } else {
                Log::channel('exports_log')->warning("⚠️ Could not acquire lock for export [{$this->exportId}] user {$this->userId}");
            }
        } catch (Throwable $e) {
            if (isset($lock) && $lock->owner()) {
                $lock->release();
            }
            $this->failed($e);
            throw $e;
        }
    }

    private function generateSeatCardsPdf(string $progressKey): string
    {
        $students = $this->getStudents();

        $pdf = new Fpdi('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetFont('Arial', '', 9);

        // A4 measurements and layout
        $pageWidth = 210;
        $pageHeight = 297;
        $marginX = 10;
        $marginY = 10;
        $columns = 2;
        $rows = 5;

        // Calculate card size
        $availableWidth = $pageWidth - ($marginX * ($columns + 1));
        $availableHeight = $pageHeight - ($marginY * ($rows + 1));
        $cardWidth = $availableWidth / $columns;
        $cardHeight = $availableHeight / $rows;

        $totalStudents = count($students);
        $pdf->AddPage();

        $x = $marginX;
        $y = $marginY;

        foreach ($students as $index => $student) {

            // Draw card border
            $pdf->Rect($x, $y, $cardWidth, $cardHeight);

            // === TOP ROW: Logo + Header + Photo ===
            $pdf->SetFont('Arial', 'B', 10);

            // Logo box (left)
            $pdf->Rect($x + 4, $y + 4, 15, 15);
            $pdf->SetXY($x + 4, $y + 19);
            $pdf->SetFont('Arial', '', 7);
            $pdf->Cell(15, 4, 'Logo', 0, 0, 'C');

            // Header (center)
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetXY($x + 22, $y + 5);
            $pdf->Cell($cardWidth - 44, 5, $this->associationName ?? 'Association Name', 0, 1, 'C');

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetX($x + 22);
            $pdf->Cell($cardWidth - 44, 4, $this->associationAddress ?? 'Address', 0, 1, 'C');

            $pdf->SetFillColor(200, 200, 200);
            $pdf->SetX($x + 22);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell($cardWidth - 44, 5, 'Exam Seat Card', 0, 1, 'C', true);

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetX($x + 22);
            $pdf->Cell($cardWidth - 44, 4, $this->examName ?? 'Scholarship', 0, 1, 'C');

            // Photo box (right)
            $pdf->Rect($x + $cardWidth - 19, $y + 4, 15, 15);
            $pdf->SetXY($x + $cardWidth - 19, $y + 19);
            $pdf->SetFont('Arial', '', 7);
            $pdf->Cell(15, 4, 'Photo', 0, 0, 'C');

            // Roll box (below photo)
            $pdf->Rect($x + $cardWidth - 19, $y + 21, 15, 7);
            $pdf->SetXY($x + $cardWidth - 19, $y + 21);
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->Cell(15, 3, 'Roll', 0, 2, 'C');
            $pdf->SetFont('Arial', '', 7);
            $pdf->Cell(15, 3, (string) $student->assigned_roll, 0, 0, 'C');

            // === STUDENT INFO SECTION ===
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetXY($x + 6, $y + 28);
            $pdf->MultiCell(
                $cardWidth - 25,
                4,
                "Name: {$student->student_name_english}\n" .
                    "Unique ID: " . ($student->unique_id ?? '---') . "\n" .
                    "Year/Session: " . ($student->academic_year ?? '2025') . "\n" .
                    "Institute: {$student->institute_name}\n" .
                    "Center: " . ($student->center_name ?? '---')
            );

            // Move to next position
            if (($index + 1) % $columns === 0) {
                // Move to next row
                $x = $marginX;
                $y += $cardHeight + $marginY;
            } else {
                // Next column
                $x += $cardWidth + $marginX;
            }

            // New page after 10 cards
            if (($index + 1) % ($columns * $rows) === 0 && $index + 1 < $totalStudents) {
                $pdf->AddPage();
                $x = $marginX;
                $y = $marginY;
            }

            // Progress tracking
            $progress = (int)(($index + 1) / max(1, $totalStudents) * 100);
            Cache::put($progressKey, $progress, now()->addHours(1));
        }

        // Save file
        $relativeDir = "exports/user_{$this->userId}/" . now()->format('Ymd_His') . "/{$this->exportId}";
        Storage::disk('public')->makeDirectory($relativeDir);
        $finalFile = "{$relativeDir}/{$this->fileName}.pdf";
        $pdf->Output(Storage::disk('public')->path($finalFile), 'F');

        return $finalFile;
    }




    private function getStudents()
    {

        $assoc = InstituteDetail::find($this->instituteDetailsId);

        $this->associationName = $assoc->institute_name;
        $this->associationAddress = $assoc->institute_address;

        return AdmissionApplied::select(
            'student_name_english',
            'institute_name',
            'class_name',
            'assigned_roll'
        )
            ->where('institute_details_id', $this->instituteDetailsId)
            ->whereIn('center_id', $this->centers)
            ->where('academic_year_id', $this->academic_year_id)
            ->where('class_id', $this->class_id)
            ->whereNotNull('assigned_roll')
            ->where('approval_status', 'Success')
            ->orderBy('center_name')
            ->orderBy('assigned_roll')
            ->get()
            ->map(function ($student) {
                // Exam name can be dynamic; adjust here
                $student->exam_name = $this->examName;
                return $student;
            });
    }

    public function failed(Throwable $exception): void
    {
        $progressKey = "export_progress_{$this->userId}_{$this->exportId}";
        $errorKey = "export_error_{$this->userId}_{$this->exportId}";
        Cache::put($progressKey, -1, now()->addMinutes(30));
        Cache::put($errorKey, $exception->getMessage(), now()->addHours(2));

        Log::channel('exports_log')->error("❗PDF export [{$this->exportId}] failed permanently: {$exception->getMessage()}", [
            'exception' => $exception,
        ]);
    }
}
