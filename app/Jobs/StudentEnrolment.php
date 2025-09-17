<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\PayApply;
use App\Models\FeeAmount;
use App\Models\FeeMapping;
use App\Models\StudentAssign;
use App\Models\StudentDetail;
use App\Models\SubjectConfig;
use Illuminate\Bus\Queueable;
use App\Models\AcademicDetail;
use App\Models\GuardianDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class StudentEnrolment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $studentData;
    /**
     * Create a new job instance.
     */
    public function __construct($studentData)
    {
        $this->studentData = $studentData;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info("Before saving check duplicate in Academic details table");
        
        $checkCustomId = AcademicDetail::where('institute_details_id', $this->studentData['institute_details_id'])
        ->where('custom_student_id', $this->studentData['custom_student_id'])
        ->where('academic_year', $this->studentData['academic_year'])
        ->first();

        if(!$checkCustomId)
        {
            Log::info("Before saving student enrolment");
            $student = new Student();
            $student->institute_details_id = $this->studentData['institute_details_id'];
    
            if ($student->save()) {
                Log::info('Student added manually successfully');
    
                $check_feeamounts = FeeAmount::where('institute_details_id', $this->studentData['institute_details_id'])
                                            ->where('class_id', $this->studentData['class_id'])
                                            ->where('group_id', $this->studentData['group_id'])
                                            ->where('academic_year_id', $this->studentData['academic_year'])
                                            ->where('student_category_id', $this->studentData['category'])
                                            ->get();
                if($check_feeamounts)
                {
                    foreach($check_feeamounts as $feeamouts)
                    {
                        $feesubhead = FeeMapping::where('institute_details_id', $this->studentData['institute_details_id'])
                        ->where('fee_head_id', $feeamouts->fee_head_id)
                        ->pluck('fee_subhead_id');
                        foreach($feesubhead as $fee_subhead_id)
                        {
                            $payApplies = new PayApply();
                            $payApplies->institute_details_id = $this->studentData['institute_details_id'];
                            $payApplies->combinations_pivot_id = $this->studentData['assign_id'];
                            $payApplies->student_id = $student->id;
                            $payApplies->academic_year_id = $this->studentData['academic_year'];
                            $payApplies->fee_head_id = $feeamouts->fee_head_id;
                            $payApplies->fee_subhead_id = $fee_subhead_id;
                            $payApplies->payable = $feeamouts->fee_amount;
                            $payApplies->total_amount = $feeamouts->fee_amount;
                            $payApplies->fine = $feeamouts->fine_amount;
                            $payApplies->save();
                        }
                    }
                }
    
                $insert = new AcademicDetail();
                $insert->custom_student_id = $this->studentData['custom_student_id'];
                $insert->class_roll = $this->studentData['class_roll'];
                $insert->category = $this->studentData['category'];
                $insert->academic_session = $this->studentData['academic_session'];
                $insert->combinations_pivot_id = $this->studentData['assign_id'];
                $insert->institute_details_id = $this->studentData['institute_details_id'];
                $insert->academic_year = $this->studentData['academic_year'];
                $insert->student_id = $student->id;
    
                if ($insert->save()) {
                    // Associate StudentDetail with Student
                    $student->academic_details_id = $insert->id;
    
                    if ($student->save()) {
                        $studentDetail = new StudentDetail();
                        $studentDetail->student_name = $this->studentData['student_name'];
                        $studentDetail->student_gender = $this->studentData['student_gender'];
                        $studentDetail->student_religion = $this->studentData['student_religion'];
                        $studentDetail->student_id = $student->id;
                        $studentDetail->save();
    
                        // Associate StudentDetail with Student
                        $student->student_details_id = $studentDetail->id;
                        $student->save();
    
                        $guardianInput = new GuardianDetail();
                        $guardianInput->father_name = $this->studentData['father_name'];
                        $guardianInput->mother_name = $this->studentData['mother_name'];
                        $guardianInput->father_mobile = $this->studentData['father_mobile'];
                        $guardianInput->student_id = $student->id;
                        $guardianInput->save();
    
                        // Associate GuardianDetail with Student
                        $student->guardian_details_id = $guardianInput->id;
                        $student->save();
                    }
                }
            } else {
                Log::error('Failed to save data');
            }
        }
    }
}
