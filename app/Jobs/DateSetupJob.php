<?php

namespace App\Jobs;

use App\Models\PayApply;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class DateSetupJob implements ShouldQueue
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
        // dd($this->payUpdate);
        $payApplies = PayApply::find($this->payUpdate['id'])
                    ->update(
                        [
                            'fee_payable_date' => $this->payUpdate['fee_payable_date'],
                            'fine_active_date' => $this->payUpdate['fine_active_date'],
                            'fee_expire_date' => $this->payUpdate['fee_expire_date']
                        ]
                    );
    }
}
