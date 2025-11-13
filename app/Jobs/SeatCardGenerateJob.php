<?php

namespace App\Jobs;

use App\Models\AdmissionApplied;
use App\Models\InstituteDetail;
use setasign\Fpdi\Fpdi;
use Barryvdh\Snappy\Facades\SnappyPdf;
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
    public string $fileName;
    public string $exportId;
    public string $report_title;

    public ?array $dtParams;
    public ?array $searchableColumns;

    public int $timeout = 7200;
    public int $tries = 2;
    protected int $bufferSize = 500;
    protected int $countLimitForEstimate = 1000000;

    public function __construct(
        int $userId,
        int $instituteDetailsId,
        int $academic_year_id,
        int $class_id,
        array $centers,
        string $fileName,
        ?string $exportId = null,
        ?array $dtParams = [],
        ?array $searchableColumns = []
    ) {
        $this->report_title = 'Seat Card';
        $this->userId = $userId;
        $this->instituteDetailsId = $instituteDetailsId;
        $this->academic_year_id = $academic_year_id;
        $this->class_id = $class_id;
        $this->centers = $centers;
        $this->fileName = $fileName;
        $this->exportId = $exportId ?? (string) Str::uuid();
        $this->dtParams = $dtParams;
        $this->searchableColumns = $searchableColumns;

        Log::channel('exports_log')->info("🧾 Initializing Seat Card PDF export [{$this->exportId}] for user {$this->userId}");
    }

    public function handle(): void
    {
        $scopeHash = md5("{$this->userId}_{$this->instituteDetailsId}");
        $lockKey = "seat_card_export_lock_{$scopeHash}";
        $progressKey = "export_progress_{$this->userId}_{$this->exportId}";

        $lock = Cache::lock($lockKey, $this->timeout);
        $lastProgress = 0;
        $lastUpdateTime = microtime(true);

        try {
            if ($lock->get()) {
                Log::channel('exports_log')->info("🔒 Lock acquired for export [{$this->exportId}] user {$this->userId}");
                try {
                    $this->processPdfExport($progressKey, $lastProgress, $lastUpdateTime);
                } catch (Throwable $e) {
                    $this->updateProgress($progressKey, -1, 1, $lastProgress, $lastUpdateTime, 3600);
                    Log::channel('exports_log')->error("❗ Export [{$this->exportId}] failed: " . $e->getMessage(), ['exception' => $e]);
                    throw $e;
                } finally {
                    try {
                        $lock->release();
                        Log::channel('exports_log')->info("🔓 Lock released for export [{$this->exportId}] user {$this->userId}");
                    } catch (Throwable $releaseEx) {
                        Log::channel('exports_log')->warning("⚠️ Could not release lock (export {$this->exportId}): " . $releaseEx->getMessage());
                    }
                }
            } else {
                Log::channel('exports_log')->warning("⚠️ Could not acquire lock for export [{$this->exportId}] user {$this->userId}. Another job is still running.");
                $this->updateProgress($progressKey, -1, 1, $lastProgress, $lastUpdateTime, 3600);
                return;
            }
        } catch (Throwable $e) {
            try {
                if (isset($lock) && $lock->owner()) {
                    $lock->release();
                }
            } catch (Throwable $_) {
            }
            throw $e;
        }
    }

    private function processPdfExport(string $progressKey, int &$lastProgress, float &$lastUpdateTime): void
    {
        $readyKey = "export_ready_{$this->userId}_{$this->exportId}";
        $errorKey = "export_error_{$this->userId}_{$this->exportId}";
        $expiry = now()->addHours(1)->timestamp;

        Cache::put($progressKey, 0, $expiry);
        Cache::put($readyKey, null, $expiry);
        Cache::put($errorKey, null, $expiry);

        $relativeDir = "exports/user_{$this->userId}/" . now()->format('Ymd_His') . "/{$this->exportId}";
        Storage::disk('public')->makeDirectory($relativeDir);

        $query = $this->buildBaseQuery();

        try {
            $totalRows = (clone $query)->limit($this->countLimitForEstimate)->count();
        } catch (Throwable $e) {
            $totalRows = 0;
            Log::channel('exports_log')->warning("⚠️ Could not count rows for PDF export: " . $e->getMessage());
        }

        $totalChunks = $totalRows ? (int) ceil($totalRows / $this->bufferSize) : 1;
        $batchFiles = [];
        $chunkIndex = 0;
        $columns = $this->getColumnsByType();

        $query->chunk($this->bufferSize, function ($rows) use (
            &$chunkIndex,
            &$batchFiles,
            $totalChunks,
            $progressKey,
            $expiry,
            &$lastProgress,
            &$lastUpdateTime,
            $relativeDir,
            $columns
        ) {
            $chunkIndex++;

            // HTML Table
            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>table {width: 100%; border-collapse: collapse;font-size: 10px} th { background: #31bd9c; color: white; padding: 5px; text-align: center; border: 1px solid #ccc } td { padding: 4px 6px; border: 1px solid #ddd} tr:nth-child(even) {background-color: #f9f9f9}  body, table, th, td {font-family: Courier New, monospace; font-size: 11px} </style></head><body><table><thead><tr>';
            foreach ($columns as $col) $html .= "<th>{$col}</th>";
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach (array_keys($columns) as $field) {
                    $html .= "<td>{$row->$field}</td>";
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table></body></html>';

            $batchFile = "{$relativeDir}/part_{$chunkIndex}.pdf";

            $instituteDetail = InstituteDetail::find($this->instituteDetailsId);

            $headerHtml = view('exports.pdf.header', [
                'institute_name' => $instituteDetail->institute_name,
                'institute_type' => $instituteDetail->institute_type,
                'report_title' => $this->report_title,
                'showLogo' => $chunkIndex === 1
            ])->render();


            // $footerHtml = view('exports.pdf.footer', [
            //     'generatedBy' => optional($merchant)->username ?? 'System',
            // ])->render();

            SnappyPdf::loadHTML($html)
                ->setPaper('a4')
                ->setOrientation('landscape')
                ->setOption('enable-local-file-access', true) // ✅ this is the key fix
                ->setOption('header-html', $this->storeTempHtml($headerHtml, "header_{$chunkIndex}.html"))
                // ->setOption('footer-html', $this->storeTempHtml($footerHtml, "footer.html"))
                ->setOption('footer-line', true)
                ->setOption('footer-spacing', 5)
                ->setOption('margin-top', 35)
                ->setOption('margin-bottom', 25)
                ->setOption('encoding', 'UTF-8')
                ->save(Storage::disk('public')->path($batchFile));


            $batchFiles[] = Storage::disk('public')->path($batchFile);

            unset($html);

            $this->updateProgress($progressKey, $chunkIndex, $totalChunks, $lastProgress, $lastUpdateTime, $expiry);
        });



        // Merge PDFs
        $finalFileName = "{$this->fileName}.pdf";
        $finalPath = "{$relativeDir}/{$finalFileName}";
        $this->mergePdfs($batchFiles, Storage::disk('public')->path($finalPath), 'System');

        Cache::put($progressKey, 100, $expiry);
        Cache::put($readyKey, $finalPath, $expiry);

        foreach ($batchFiles as $file) {
            @unlink($file);
        }

        Log::channel('exports_log')->info("✅ PDF export [{$this->exportId}] completed. file={$finalPath}");
    }

    private function storeTempHtml(string $content, string $filename): string
    {
        $tempDir = storage_path('app/temp_pdfs');
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
        $path = "{$tempDir}/{$filename}";
        file_put_contents($path, $content);
        return $path;
    }

    private function buildBaseQuery()
    {
        $query = $query = AdmissionApplied::select(
            'id',
            'unique_number',
            'student_name_english',
            'institute_details_id',
            'institute_id',
            'institute_name',
            'guardian_mobile',
            'class_id',
            'class_name',
            'academic_year_id',
            'academic_year',
            'center_id',
            'center_name',
            'approval_status',
            'status',
            'assigned_roll'
        )
            ->where('institute_details_id', $this->instituteDetailsId)
            ->whereIn('center_id', $this->centers)
            ->where('academic_year_id', $this->academic_year_id)
            ->where('class_id', $this->class_id)
            ->whereNotNull('assigned_roll')
            ->where('approval_status', 'Success')
            ->orderBy('center_name')
            ->orderBy('assigned_roll');

        if (!empty($this->dtParams)) {
            try {
                $datatable = new PrimevueDatatables();
                $datatable->dtParams($this->dtParams)
                    ->searchableColumns($this->searchableColumns ?? [])
                    ->query($query)
                    ->make();
            } catch (Throwable $pvEx) {
                Log::channel('exports_log')->warning("⚠️ PrimeVue filter failed [{$this->exportId}]: " . $pvEx->getMessage());
            }
        }

        $query->orderBy('id');
        return $query;
    }

    private function mergePdfs(array $files, string $outputPath, string $generatedBy): void
    {
        $pdf = new Fpdi();

        // First, calculate total pages across all PDFs
        $totalPages = 0;
        $pageCounts = [];
        foreach ($files as $file) {
            $count = $pdf->setSourceFile($file);
            $pageCounts[] = $count;
            $totalPages += $count;
        }

        $currentPage = 1;

        foreach ($files as $index => $file) {
            $pageCount = $pageCounts[$index];
            $sourceFile = $pdf->setSourceFile($file);

            for ($i = 1; $i <= $pageCount; $i++) {
                $tpl = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tpl);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl);

                // Footer overlay
                $pdf->SetFont('Courier', '', 10);

                $marginBottom = 15; // mm from bottom
                $pdf->SetY(-$marginBottom);

                $pageWidth = $size['width'];

                // Left
                $pdf->SetX(10);
                $pdf->Cell(0, 10, "Generated by: {$generatedBy}", 0, 0, 'L');

                // Center
                $pdf->SetX(0);
                $pdf->Cell(0, 10, "Page {$currentPage} of {$totalPages}", 0, 0, 'C');

                // Right
                $pdf->SetX(-10);
                $pdf->Cell(0, 10, "Generated on: " . now()->format('M d, Y, h:i A'), 0, 0, 'R');

                $currentPage++;
            }
        }

        $pdf->Output($outputPath, 'F');
    }


    private function updateProgress(string $progressKey, int $processed, int $totalRows, int &$lastProgress, float &$lastUpdateTime, int $expiry): void
    {
        if ($processed < 0) {
            $progress = -1;
        } else {
            $progress = $totalRows > 0
                ? (int) round(($processed / max(1, $totalRows)) * 100)
                : (int) min(99, round($processed / max(1, $processed + 1000) * 100));
        }

        if ($progress === -1 || $progress > $lastProgress || microtime(true) - $lastUpdateTime >= 1) {
            Cache::put($progressKey, $progress, now()->addSeconds($expiry));
            if ($progress === -1) {
                Log::channel('exports_log')->error("❌ Export [{$this->exportId}] user {$this->userId} marked as failed");
            } else {
                Log::channel('exports_log')->info("📡 Export progress at job [{$this->exportId}] user {$this->userId}: {$progress}%");
            }
            $lastProgress = $progress;
            $lastUpdateTime = microtime(true);
        }
    }

    private function getColumnsByType(): array
    {
        $columns = [
            'unique_number' => 'Applicant ID',
            'student_name_english' => 'Student Name',
            'assigned_roll' => 'Roll Number',
            'institute_name' => 'Institute Name',
            'center_name' => 'Center',
            'class_name' => 'Class',
        ];

        return $columns;
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
