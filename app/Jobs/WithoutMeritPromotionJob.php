<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\PayApply;
use App\Models\FeeAmount;
use App\Models\FeeMapping;
use App\Models\StudentAssign;
use App\Models\SubjectConfig;
use Illuminate\Bus\Queueable;
use App\Models\AcademicDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class WithoutMeritPromotionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $studentPromotion;
    /**
     * Create a new job instance.
     */
    public function __construct($studentPromotion)
    {
        $this->studentPromotion = $studentPromotion;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Without Merit Promotion Begin');
        $academicDetail = new AcademicDetail();
        $academicDetail->institute_details_id = $this->studentPromotion['institute_details_id'];
        $academicDetail->student_id = $this->studentPromotion['student_id'];
        $academicDetail->combinations_pivot_id = $this->studentPromotion['combinations_pivot_id'];
        $academicDetail->admission_date = $this->studentPromotion['admission_date'];
        $academicDetail->academic_session = $this->studentPromotion['academic_session_id'];
        $academicDetail->academic_year = $this->studentPromotion['academic_year_id'];
        $academicDetail->category = $this->studentPromotion['category'];
        $academicDetail->class_roll = $this->studentPromotion['new_roll'];
        $academicDetail->custom_student_id = $this->studentPromotion['custom_student_id'];

        if ($academicDetail->save()) {
            $student = Student::where('id', $this->studentPromotion['student_id'])
                ->update(
                    [
                        'academic_details_id' => $academicDetail->id
                    ]
                );

            $check_feeamounts = FeeAmount::where('institute_details_id', $this->studentPromotion['institute_details_id'])
                ->where('class_id', $this->studentPromotion['class_id'])
                ->where('group_id', $this->studentPromotion['group_id'])
                ->where('academic_year_id', $this->studentPromotion['academic_year_id'])
                ->where('student_category_id', $this->studentPromotion['category'])
                ->get();
            if ($check_feeamounts) {
                foreach ($check_feeamounts as $feeamouts) {
                    $feesubhead = FeeMapping::where('institute_details_id', $this->studentPromotion['institute_details_id'])
                        ->where('fee_head_id', $feeamouts->fee_head_id)
                        ->pluck('fee_subhead_id');
                    foreach ($feesubhead as $fee_subhead_id) {
                        $payApplies = new PayApply();
                        $payApplies->institute_details_id = $this->studentPromotion['institute_details_id'];
                        $payApplies->combinations_pivot_id = $this->studentPromotion['combinations_pivot_id'];
                        $payApplies->student_id = $this->studentPromotion['student_id'];
                        $payApplies->academic_year_id = $this->studentPromotion['academic_year_id'];
                        $payApplies->fee_head_id = $feeamouts->fee_head_id;
                        $payApplies->fee_subhead_id = $fee_subhead_id;
                        $payApplies->payable = $feeamouts->fee_amount;
                        $payApplies->total_amount = $feeamouts->fee_amount;
                        $payApplies->fine = $feeamouts->fine_amount;
                        $payApplies->save();
                    }
                }
            }
        }
    }
}
