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
        array $searchableColumns = []
    ) {
        $this->userId = $userId;
        $this->exam_id = $exam_id;
        $this->fileName = $fileName;
        $this->dtParams = $dtParams;
        $this->searchableColumns = $searchableColumns;
        $this->exportId = (string) Str::uuid();

        Log::channel('exports_log')->info("📄 Starting Exam CSV Export [{$this->exportId}] exam_id={$this->exam_id}");
    }

    public function handle()
    {
        $progressKey = "export_progress_{$this->userId}_{$this->exportId}";
        $readyKey = "export_ready_{$this->userId}_{$this->exportId}";

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

            // Apply filters if needed
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

            if (!empty($this->dtParams['sortField'])) {
                $query->orderBy(
                    $this->dtParams['sortField'],
                    $this->dtParams['sortOrder'] == 1 ? 'asc' : 'desc'
                );
            }

            $rows = $query->get();
            $total = $rows->count();

            // Initialize progress in cache
            Cache::put($progressKey, [
                'status' => 'processing',
                'total' => $total,
                'processed' => 0,
                'percentage' => 0
            ], now()->addMinutes(30));

            // CSV setup
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

                // Update progress
                $processed = $index + 1;
                $percentage = intval(($processed / max(1, $total)) * 100);
                Cache::put($progressKey, [
                    'status' => 'processing',
                    'total' => $total,
                    'processed' => $processed,
                    'percentage' => $percentage
                ], now()->addMinutes(30));
            }

            // Save CSV
            rewind($csv);
            $csvContent = stream_get_contents($csv);
            $relativePath = "exports/exam-csv/{$this->userId}/{$this->exportId}/{$this->fileName}.csv";
            Storage::disk('public')->put($relativePath, $csvContent);

            // Mark as completed
            Cache::put($readyKey, $relativePath, now()->addHours(1));
            Cache::put($progressKey, [
                'status' => 'completed',
                'total' => $total,
                'processed' => $total,
                'percentage' => 100
            ], now()->addHours(1));

            Log::channel('exports_log')->info("✅ CSV Export Completed: {$relativePath}");
        } catch (Throwable $e) {
            // Mark as failed
            Cache::put($progressKey, [
                'status' => 'failed',
                'total' => $total ?? 0,
                'processed' => $index ?? 0,
                'percentage' => -1,
                'error' => $e->getMessage()
            ], now()->addHours(1));

            Log::channel('exports_log')->error("❌ CSV export failed: " . $e->getMessage());
            throw $e;
        }
    }
}
