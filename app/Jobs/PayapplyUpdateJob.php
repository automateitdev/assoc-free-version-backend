<?php

namespace App\Jobs;

use App\Models\PayApply;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class PayapplyUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payUpdate;
    /**
     * Create a new job instance.
     */
    public function __construct($payUpdate)
    {
        $this->payUpdate = $payUpdate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payApplies = PayApply::find($this->payUpdate['id'])
                                ->update(
                                    [
                                        'payable' => $this->payUpdate['payable'],
                                        'total_amount' => $this->payUpdate['total_amount'],
                                        'fine' => $this->payUpdate['fine_amount']
                                    ]
                                );
    }
}
