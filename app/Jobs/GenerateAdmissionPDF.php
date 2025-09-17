<?php

namespace App\Jobs;

use Exception;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Barryvdh\Snappy\Facades\SnappyPdf;


class GenerateAdmissionPDF implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // protected $configs;
    // protected $applieds;
    protected $data;
    protected $institute_name;
    protected $institute_address;
    protected $instituteId;
    protected $filePath;
    protected $cacheName;
    public $tries = 3;
    /**
     * Create a new job instance.
     */
    public function __construct($data, $institute_name, $institute_address, $instituteId, $filePath, $cacheName)
    {

        // $this->configs = $configs;
        // $this->applieds = $applieds;
        $this->data = $data;
        $this->institute_name = $institute_name;
        $this->institute_address = $institute_address;
        $this->instituteId = $instituteId;
        $this->filePath = $filePath;
        $this->cacheName = $cacheName;
        // $this->class = $class;

    }

    public function handle()
    {
        $html = View::make('pdf.admission', [
            'data' => $this->data,
            'institute_name' => $this->institute_name,
            'institute_address' => $this->institute_address,
        ])->render();

        $filePath = storage_path('app/public/' . $this->filePath);

        if (file_exists($filePath)) {
            unlink($filePath); // Delete the existing file
        }

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('legal')
            ->setOption('orientation', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10)
            ->setOption('enable-local-file-access', true)
            ->setOption('disable-smart-shrinking', true);



        $filePath = storage_path('app/public/' . $this->filePath);
        $pdf->save($filePath);

        Cache::put("$this->cacheName", 'completed');
        Log::info("$this->cacheName cache completed");
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception)
    {
        Log::error("Job failed: {$exception->getMessage()}");

        if ($this->attempts() >= $this->tries) {
            // Clear the queue
            Queue::purge();
        }
    }
}
