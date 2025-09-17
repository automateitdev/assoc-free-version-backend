<?php

namespace App\Jobs;

use App\Models\PayApply;
use App\Models\PaymentInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class QuickCollectionPayJob implements ShouldQueue
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
        $payDetails = [
            "invoice" => $this->payUpdate['invoice'],
            "total" => $this->payUpdate['total'],
            "paid" => $this->payUpdate['paid'],
            "due" => $this->payUpdate['due'],
            "payment_date" => $this->payUpdate['payment_date'],
            "payment_type" => $this->payUpdate['payment_type'],
        ];

        // Retrieve existing pay_details and decode it from JSON
        $existingPayDetails = PayApply::find($this->payUpdate['id'])->pay_details;
        $existingPayDetailsArray = json_decode($existingPayDetails, true);

        // Check if pay_details is empty
        if (empty($existingPayDetailsArray)) {
            // If empty, initialize it with the new payDetails
            $existingPayDetailsArray = [$payDetails];
        } else {
            // If not empty, append the new payDetails
            $existingPayDetailsArray[] = $payDetails;
        }

        // Encode the updated pay_details back to JSON
        $updatedPayDetails = json_encode($existingPayDetailsArray);

        
        $payApplies = PayApply::find($this->payUpdate['id'])
                                ->update(
                                    [
                                        'due_amount' => $this->payUpdate['due'],
                                        'payment_state' => $this->payUpdate['payment_state'],
                                        'pay_details' => $updatedPayDetails,
                                    ]
                                );
        if($payApplies)
        {
            $exists = PaymentInvoice::where('payment_invoice', $this->payUpdate['invoice'])->exists();
            if(!$exists)
            {
                $payInvoice = new PaymentInvoice();
                $payInvoice->institute_details_id = $this->payUpdate['institute_details_id'];
                $payInvoice->academic_year_id = $this->payUpdate['academic_year_id'];
                $payInvoice->student_id = $this->payUpdate['student_id'];
                $payInvoice->payment_invoice = $this->payUpdate['invoice'];
                $payInvoice->transaction_date = $this->payUpdate['payment_date'];
                $payInvoice->status = $this->payUpdate['payment_state'];
                $payInvoice->amount = $this->payUpdate['totalPayment'];
                $payInvoice->hr_id = $this->payUpdate['hr_id'];
                $payInvoice->save();
            }
        }
    }
}
