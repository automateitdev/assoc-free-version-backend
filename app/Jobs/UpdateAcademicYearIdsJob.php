<?php

namespace App\Jobs;

use App\Models\AdmissionApplied;
use App\Models\AdmissionPayment;
use App\Models\CoreSubcategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateAcademicYearIdsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Optional: Set the max number of times the job can be attempted
    public $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Update AdmissionApplied
        AdmissionApplied::chunk(100, function ($applieds) {
            foreach ($applieds as $applied) {
                $coreSubCat = CoreSubcategory::where('core_subcategory_name', $applied->academic_year)->first();
                if ($coreSubCat) {
                    $applied->academic_year_id = $coreSubCat->id;
                    $applied->save();
                }
            }
        });

        // Update AdmissionPayment
        AdmissionPayment::chunk(100, function ($payments) {
            foreach ($payments as $payment) {
                $coreSubCat = CoreSubcategory::where('core_subcategory_name', $payment->academic_year)->first();
                if ($coreSubCat) {
                    $payment->academic_year_id = $coreSubCat->id;
                    $payment->save();
                }
            }
        });
    }
}
