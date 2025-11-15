<?php

namespace App\Jobs;

use App\Models\AdmissionApplied;
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

class ExamMarkExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;
    public int $exam_id;
    public string $fileName;
    public string $exportId;

    public array $dtParams;
    public array $searchableColumns;

    public function __construct(
        int $userId,
        int $exam_id,
        string $fileName,
        array $dtParams = [],
        array $searchableColumns = [],
        ?string $exportId = null,
    ) {
        $this->userId = $userId;
        $this->exam_id = $exam_id;
        $this->fileName = $fileName;
        $this->dtParams = $dtParams;
        $this->searchableColumns = $searchableColumns;
        $this->exportId = $exportId ?? (string) Str::uuid();

        Log::channel('exports_log')->info("📄 Starting Exam CSV Export [{$this->exportId}] exam_id={$this->exam_id}");
    }

    public function handle()
    {
        $progressKey = "export_progress_{$this->userId}_{$this->exportId}";
        $readyKey = "export_ready_{$this->userId}_{$this->exportId}";
        $errorKey = "export_error_{$this->userId}_{$this->exportId}";

        try {
            $query = AdmissionApplied::query()
                ->with(['examMark' => function ($q) {
                    $q->where('exam_id', $this->exam_id);
                }])
                ->select(
                    'id',
                    'unique_number',
                    'student_name_english',
                    'class_name',
                    'institute_name',
                    'assigned_roll'
                );

            // Apply PrimeVue filters if available
            if (!empty($this->dtParams)) {
                try {
                    $datatable = new PrimevueDatatables();
                    $datatable->dtParams($this->dtParams)
                        ->searchableColumns($this->searchableColumns)
                        ->query($query)
                        ->make();
                } catch (Throwable $e) {
                    Log::channel('exports_log')->warning("⚠ PrimeVue filter error: " . $e->getMessage());
                }
            }

            // Sorting
            if (!empty($this->dtParams['sortField'])) {
                $query->orderBy(
                    $this->dtParams['sortField'],
                    $this->dtParams['sortOrder'] == 1 ? 'asc' : 'desc'
                );
            }

            $rows = $query->get();
            $total = $rows->count();

            // Initialize progress
            Cache::put($progressKey, 0, now()->addHours(1));

            // Prepare CSV
            $csv = fopen('php://temp', 'r+');
            fputcsv($csv, [
                "Applicant ID",
                "Name",
                "Class",
                "Institute",
                "Assigned Roll",
                "Total Mark",
                "Obtained Mark",
                "Grade",
                "Grade Point"
            ]);

            foreach ($rows as $index => $row) {

                $mark = $row->examMark;

                fputcsv($csv, [
                    $row->unique_number,
                    $row->student_name_english,
                    $row->class_name,
                    $row->institute_name,
                    $row->assigned_roll,
                    optional($mark)->total_mark,
                    optional($mark)->obtained_mark,
                    optional($mark)->grade,
                    optional($mark)->grade_point,
                ]);

                // ✅ Update integer progress (matching SeatCardGenerateJob)
                $progress = intval((($index + 1) / max(1, $total)) * 100);
                Cache::put($progressKey, $progress, now()->addHours(1));
            }

            // Save CSV
            rewind($csv);
            $csvContent = stream_get_contents($csv);
            $relativePath = "exports/exam-csv/{$this->userId}/{$this->exportId}/{$this->fileName}.csv";
            Storage::disk('public')->put($relativePath, $csvContent);

            // Completed
            Cache::put($readyKey, $relativePath, now()->addHours(1));
            Cache::put($progressKey, 100, now()->addHours(1));

            Log::channel('exports_log')->info("✅ CSV Export Completed: {$relativePath}");
        } catch (Throwable $e) {

            Cache::put($progressKey, -1, now()->addHours(1));
            Cache::put($errorKey, $e->getMessage(), now()->addHours(1));

            Log::channel('exports_log')->error("❌ CSV export failed: " . $e->getMessage());

            throw $e;
        }
    }
}
