<?php

namespace App\Jobs;

use App\Models\PayApply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StudentWiseFeeAmountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $paymentData;
    /**
     * Create a new job instance.
     */
    public function __construct($paymentData)
    {
        $this->paymentData = $paymentData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach($this->paymentData['fee_subhead_id'] as $fee_subhead_id)
        {
            $payApplies = new PayApply();
            $payApplies->institute_details_id = $this->paymentData['institute_details_id'];
            $payApplies->combinations_pivot_id = $this->paymentData['combinations_pivot_id'];
            $payApplies->student_id = $this->paymentData['student_id'];
            $payApplies->academic_year_id = $this->paymentData['academic_year_id'];
            $payApplies->fee_head_id = $this->paymentData['fee_head_id'];
            $payApplies->fee_subhead_id = $fee_subhead_id;
            $payApplies->payable = $this->paymentData['payable'];
            $payApplies->total_amount = $this->paymentData['total_amount'];
            $payApplies->fine = $this->paymentData['fine_amount'];
            $payApplies->save();
        }
    }
}
