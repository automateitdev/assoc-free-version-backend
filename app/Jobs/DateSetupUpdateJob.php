<?php

namespace App\Jobs;

use App\Models\PayApply;
use App\Models\DateSetup;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class DateSetupUpdateJob implements ShouldQueue
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
        $update = DateSetup::where('id', $this->payUpdate['date_setup_id'])
                                ->update(
                                    [
                                        'fee_payable_date' => $this->payUpdate['fee_payable_date'],
                                        'fine_active_date' => $this->payUpdate['fine_active_date'],
                                        'fee_expire_date' => $this->payUpdate['fee_expire_date']
                                    ]
                                );
        $payApplies = PayApply::find($this->payUpdate['pay_id'])
                ->update(
                    [
                        'fee_payable_date' => $this->payUpdate['fee_payable_date'],
                        'fine_active_date' => $this->payUpdate['fine_active_date'],
                        'fee_expire_date' => $this->payUpdate['fee_expire_date']
                    ]
                );
    }
}
