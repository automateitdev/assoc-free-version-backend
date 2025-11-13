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
        $pdf->SetFont('Arial', '', 12);

        $pageWidth = 210;
        $pageHeight = 297;
        $marginX = 10;
        $marginY = 10;
        $cardWidth = ($pageWidth - 3 * $marginX) / 2; // 2 cards horizontally
        $cardHeight = 70; // Adjust to fit vertically

        $x = $marginX;
        $y = $marginY;
        $cardCount = 0;
        $totalStudents = count($students);

        $pdf->AddPage();

        foreach ($students as $index => $student) {

            // Start new page if needed
            if ($y + $cardHeight > $pageHeight - $marginY) {
                $pdf->AddPage();
                $x = $marginX;
                $y = $marginY;
            }

            // Draw card border
            $pdf->Rect($x, $y, $cardWidth, $cardHeight);

            // Add student info
            $pdf->SetXY($x + 5, $y + 5);
            $pdf->MultiCell(
                $cardWidth - 10,
                6,
                "Name: {$student->student_name_english}\n" .
                    "Institute: {$student->institute_name}\n" .
                    "Class: {$student->class_name}\n" .
                    "Exam: {$student->exam_name}\n" .
                    "Roll: {$student->assigned_roll}"
            );

            // Move to next card horizontally
            if ($x + $cardWidth + $marginX * 2 <= $pageWidth) {
                $x += $cardWidth + $marginX;
            } else {
                $x = $marginX;
                $y += $cardHeight + $marginY;
            }

            // Update progress
            $progress = (int)(($index + 1) / max(1, $totalStudents) * 100);
            Cache::put($progressKey, $progress, now()->addHours(1));
        }

        // Save PDF
        $relativeDir = "exports/user_{$this->userId}/" . now()->format('Ymd_His') . "/{$this->exportId}";
        Storage::disk('public')->makeDirectory($relativeDir);
        $finalFile = "{$relativeDir}/{$this->fileName}.pdf";
        $pdf->Output(Storage::disk('public')->path($finalFile), 'F');

        return $finalFile;
    }

    private function getStudents()
    {
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
