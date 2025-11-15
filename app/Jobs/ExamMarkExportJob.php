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

        Log::info("📄 Starting Exam CSV Export [{$this->exportId}] exam_id={$this->exam_id}");
    }

    public function handle()
    {
        $progressKey = "export_progress_{$this->userId}_{$this->exportId}";
        $readyKey = "export_ready_{$this->userId}_{$this->exportId}";

        try {

            /*
            |--------------------------------------------------------------------------
            | 1. Build main query using Eloquent relation
            |--------------------------------------------------------------------------
            */
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

            /*
            |--------------------------------------------------------------------------
            | 2. Apply PrimeVue filters (search, pagination, etc.)
            |--------------------------------------------------------------------------
            */
            if (!empty($this->dtParams)) {
                try {
                    $datatable = new PrimevueDatatables();
                    $datatable->dtParams($this->dtParams)
                        ->searchableColumns($this->searchableColumns)
                        ->query($query)
                        ->make();
                } catch (Throwable $e) {
                    Log::warning("⚠ PrimeVue filter error: " . $e->getMessage());
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Sorting
            |--------------------------------------------------------------------------
            */
            if (!empty($this->dtParams['sortField'])) {
                $query->orderBy(
                    $this->dtParams['sortField'],
                    $this->dtParams['sortOrder'] == 1 ? 'asc' : 'desc'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 4. Get data
            |--------------------------------------------------------------------------
            */
            $rows = $query->get();
            $total = $rows->count();

            /*
            |--------------------------------------------------------------------------
            | 5. Build CSV
            |--------------------------------------------------------------------------
            */
            $csv = fopen('php://temp', 'r+');

            // CSV HEADER
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

            /*
            |--------------------------------------------------------------------------
            | 6. Write each row
            |--------------------------------------------------------------------------
            */
            foreach ($rows as $index => $row) {

                $mark = $row->examMark; // hasOne relation

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

                // progress update
                $percent = intval((($index + 1) / max(1, $total)) * 100);
                Cache::put($progressKey, $percent, now()->addMinutes(30));
            }

            /*
            |--------------------------------------------------------------------------
            | 7. Save file
            |--------------------------------------------------------------------------
            */
            rewind($csv);
            $csvContent = stream_get_contents($csv);

            $relativePath = "exports/exam-csv/{$this->userId}/{$this->exportId}/{$this->fileName}.csv";
            Storage::disk('public')->put($relativePath, $csvContent);

            Cache::put($readyKey, $relativePath, now()->addHours(1));
            Cache::put($progressKey, 100, now()->addHours(1));

            Log::info("✅ CSV Export Completed: {$relativePath}");
        } catch (Throwable $e) {

            Cache::put($progressKey, -1, now()->addHours(1));
            Log::error("❌ CSV export failed: " . $e->getMessage());
            throw $e;
        }
    }
}
