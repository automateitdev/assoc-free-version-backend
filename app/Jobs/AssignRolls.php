<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\AdmissionApplied;
use App\Models\AdmissionPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AssignRolls implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }


    public function handle()
    {
        // Fetch all AdmissionPayment records
        $admissionPayments = AdmissionPayment::all();
        foreach ($admissionPayments as $admissionPayment) {
            DB::transaction(function () use ($admissionPayment) {
                // Lock the AdmissionApplied table rows for the current AdmissionPayment criteria
                $applications = AdmissionApplied::where([
                    ['institute_details_id', $admissionPayment->institute_details_id],
                    ['academic_year', trim($admissionPayment->academic_year)],
                    ['class', trim($admissionPayment->class)],
                    ['shift', trim($admissionPayment->shift)],
                    ['group', trim($admissionPayment->group)],
                    ['approval_status', 'Success'],
                    ['assigned_roll', null]
                ])->orderBy('id')
                    ->lockForUpdate() // Lock the rows for update
                    ->get();

                // Get the highest assigned roll for the current AdmissionPayment criteria and lock the row
                $maxAssignedRoll = DB::table('admission_applieds')
                ->where([
                    ['institute_details_id', $admissionPayment->institute_details_id],
                    ['academic_year', trim($admissionPayment->academic_year)],
                    ['class', trim($admissionPayment->class)],
                    ['shift', trim($admissionPayment->shift)],
                    ['group', trim($admissionPayment->group)]
                ])->lockForUpdate() // Lock the row for update
                    ->max('assigned_roll');

                // If no roll has been assigned yet, start from the roll_start value
                $startRoll = $maxAssignedRoll ? $maxAssignedRoll + 1 : $admissionPayment->roll_start;

                // Assign rolls starting from startRoll
                $roll = $startRoll;
                foreach ($applications as $application) {
                    $application->update(['assigned_roll' => $roll]);
                    $roll++;
                }

                // Update the admissionPayment roll_start value to the last assigned roll + 1
                // $admissionPayment->update(['roll_start' => $roll]);
            }, 5); // Retry the transaction up to 5 times in case of a deadlock
        }
    }
}
