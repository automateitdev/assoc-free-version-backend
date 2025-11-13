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
use Savannabits\PrimevueDatatables\PrimevueDatatables;
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

    public string $associationLogo;
    public string $associationName;
    public string $associationAddress;

    public ?array $dtParams;
    public ?array $searchableColumns;

    public int $timeout = 7200; // seconds (2 hours) - adjust if you want shorter auto-release
    public int $tries = 2;
    protected int $bufferSize = 500; // number of rows to buffer before writing
    protected int $countLimitForEstimate = 1000000; // up to this many rows we'll try to get an exact count

    public function __construct(
        int $userId,
        int $instituteDetailsId,
        int $academic_year_id,
        int $class_id,
        array $centers,
        string $examName,
        string $fileName,
        ?string $exportId = null,
        ?array $dtParams = [],
        ?array $searchableColumns = []
    ) {
        $this->userId = $userId;
        $this->instituteDetailsId = $instituteDetailsId;
        $this->academic_year_id = $academic_year_id;
        $this->class_id = $class_id;
        $this->centers = $centers;
        $this->examName = $examName;
        $this->fileName = $fileName;
        $this->exportId = $exportId ?? (string) Str::uuid();
        $this->dtParams = $dtParams;
        $this->searchableColumns = $searchableColumns;

        Log::channel('exports_log')->info("🧾 Initializing Seat Card PDF export for {$this->examName} [{$this->exportId}] for user {$this->userId}");
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
        $pdf->SetFont('Arial', '', 7);

        // Page & layout setup
        $pageWidth = 210;
        $pageHeight = 297;
        $marginX = 10;
        $marginY = 10;
        $columns = 2;
        $rows = 5;

        // Calculate card sizes (2x5 grid)
        $availableWidth = $pageWidth - ($marginX * ($columns + 1));
        $availableHeight = $pageHeight - ($marginY * ($rows + 1));
        $cardWidth = $availableWidth / $columns;
        $cardHeight = $availableHeight / $rows;

        $pdf->AddPage();

        $x = $marginX;
        $y = $marginY;
        $totalStudents = count($students);

        foreach ($students as $index => $student) {

            // Draw border
            $pdf->Rect($x, $y, $cardWidth, $cardHeight);

            // === HEADER ===
            // Logo box
            $logoX = $x + 5;
            $logoY = $y + 12;
            $logoW = 12;
            $logoH = 12;

            $pdf->Rect($logoX, $logoY, $logoW, $logoH);

            // Insert student photo if exists
            if (!empty($this->associationLogo)) {
                $logoPath = Storage::disk('public')->path("{$this->associationLogo}");
                if (file_exists($logoPath)) {
                    $pdf->Image($logoPath, $logoX, $logoY, $logoW, $logoH);
                }
            }


            $pdf->SetXY($x + 5, $y + 35);
            $pdf->SetFont('Arial', '', 7);
            $pdf->Cell(15, 4, '', 0, 'C');

            // Header center text (tighter to top)
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetXY($x + 25, $y + 2); // moved up from +5 to +2
            $pdf->Cell($cardWidth - 50, 5, $this->associationName ?? 'Association Name', 0, 1, 'C');

            $pdf->SetFont('Arial', '', 7);
            $pdf->SetX($x + 25);
            $pdf->Cell($cardWidth - 50, 4, $this->associationAddress ?? 'Association Address', 0, 1, 'C');

            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetFillColor(200, 200, 200);
            $pdf->SetX($x + 25);
            $pdf->Cell($cardWidth - 50, 5, 'Seat Card', 0, 1, 'C', true);

            $pdf->SetFont('Arial', '', 7);
            $pdf->SetX($x + 25);
            $pdf->Cell($cardWidth - 50, 4, $this->examName ?? 'Scholarship', 0, 1, 'C');

            // Photo (symmetrical)
            $photoW = 12;
            $photoH = 12;
            $photoX = $x + $cardWidth - 5 - $photoW; // 5 mm margin from right
            $photoY = $y + 12;
            $pdf->Rect($photoX, $photoY, $photoW, $photoH);
            if (!empty($student->student_pic)) {
                $photoPath = Storage::disk('public')->path($student->student_pic);
                if (file_exists($photoPath)) {
                    $pdf->Image($photoPath, $photoX, $photoY, $photoW, $photoH);
                }
            }
            $pdf->SetXY($x + $cardWidth - 22, $y + 35);
            $pdf->SetFont('Arial', '', 7);
            $pdf->Cell(15, 4, '', 0, 'C');

            // === STUDENT INFO ===
            $pdf->SetFont('Arial', '', 7);
            $pdf->SetXY($x + 8, $y + 26);
            $pdf->MultiCell(
                $cardWidth - 16,
                3,
                "Name: {$student->student_name_english}\n" .
                    "Unique ID: " . ($student->unique_number ?? '---') . "\n" .
                    "Year/Session: " . ($student->academic_year) . "\n" .
                    "Institute: {$student->institute_name}\n" .
                    "Class: " . ($student->class_name ?? '---') . "\n" .
                    "Center: " . ($student->center_name ?? '---'),
                0,
                'L'
            );

            // === ROLL NUMBER (keep exact position as before) ===
            $pdf->SetFont('Arial', 'B', 8);

            // Horizontal position and width stay exactly the same
            $rollX = $x + 10;
            $rollY = $y + 30; // vertical position
            $cellWidth = $pdf->GetStringWidth("Roll No: " . $student->assigned_roll) + 4; // small padding
            $cellHeight = 6;

            // Draw a rectangle around the text
            $pdf->Rect($rollX - 2, $rollY - 1, $cellWidth, $cellHeight);

            // Print the roll number inside the box
            $pdf->SetXY($rollX, $rollY);
            $pdf->Cell($cellWidth, $cellHeight, "Roll No: " . (string)$student->assigned_roll, 0, 1, 'C');



            // === Move to next position ===
            if (($index + 1) % $columns === 0) {
                // next row
                $x = $marginX;
                $y += $cardHeight + $marginY;
            } else {
                // next column
                $x += $cardWidth + $marginX;
            }

            // New page after every 10 cards
            if (($index + 1) % ($columns * $rows) === 0 && $index + 1 < $totalStudents) {
                $pdf->AddPage();
                $x = $marginX;
                $y = $marginY;
            }

            // Update progress
            $progress = (int)(($index + 1) / max(1, $totalStudents) * 100);
            Cache::put($progressKey, $progress, now()->addHours(1));
        }

        // Save
        $relativeDir = "exports/user_{$this->userId}/" . now()->format('Ymd_His') . "/{$this->exportId}";
        Storage::disk('public')->makeDirectory($relativeDir);
        $finalFile = "{$relativeDir}/{$this->fileName}.pdf";
        $pdf->Output(Storage::disk('public')->path($finalFile), 'F');

        return $finalFile;
    }


    private function getStudents()
    {
        $assoc = InstituteDetail::find($this->instituteDetailsId);
        if ($assoc) {
            $this->associationLogo = $assoc->logo;
            $this->associationName = $assoc->institute_name;
            $this->associationAddress = $assoc->institute_address;
        }

        $query = AdmissionApplied::query()
            ->select(
            'student_name_english',
            'institute_name',
            'academic_year',
            'class_name',
            'center_name',
            'unique_number',
            'assigned_roll',
            'student_pic'
        )
            ->where('institute_details_id', $this->instituteDetailsId)
            ->whereIn('center_id', $this->centers)
            ->where('academic_year_id', $this->academic_year_id)
            ->where('class_id', $this->class_id)
            ->whereNotNull('assigned_roll')
            ->where('approval_status', 'Success');

        // Apply PrimeVue filters / sorting
        if (!empty($this->dtParams)) {
            try {
                $datatable = new PrimevueDatatables();
                $datatable->dtParams($this->dtParams)
                    ->searchableColumns($this->searchableColumns ?? [])
                    ->query($query)
                    ->make();
            } catch (Throwable $pvEx) {
                Log::channel('exports_log')->warning("⚠️ PrimeVue filter failed for export [{$this->exportId}]: " . $pvEx->getMessage());
            }
        }

        // Sorting
        if (!empty($this->dtParams['sortField']) && !empty($this->dtParams['sortOrder'])) {
            $orderDir = $this->dtParams['sortOrder'] == 1 ? 'asc' : 'desc';
            $query->orderBy($this->dtParams['sortField'], $orderDir);
        } else {
            $query->orderBy('id');
        }

        // Fetch results and add exam_name
        $students = $query->get()->map(function ($student) {
            $student->exam_name = $this->examName;
            return $student;
        });

        return $students;
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
