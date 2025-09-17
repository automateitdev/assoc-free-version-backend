<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\PayApply;
use App\Models\FeeAmount;
use App\Models\FeeMapping;
use App\Models\StudentDetail;
use Illuminate\Bus\Queueable;
use App\Models\AcademicDetail;
use App\Models\GuardianDetail;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ExcelEnrollment implements ShouldQueue
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
        Log::info("Before saving excel enrolment");
        $file = $this->studentData['file'];
        $tempFilePath = storage_path('app/' . $file);

        $data = Excel::toArray([], $tempFilePath);

        $studentData = [];
        foreach ($data as $key => $row) {

            foreach ($row[0] as $rowKey => $value) {
                if ($rowKey == 0) {
                    $studentData[$value] = [];
                }
            }
        }

        Log::info($studentData);

        foreach ($data as $key => $row) {

            foreach ($row as $rKey => $record) {
                if ($rKey > 0) {
                    $studentData["Student ID"][] = $record[1];
                    $studentData["Roll"][] = $record[2];
                    $studentData["Name"][] = $record[3];
                    $studentData["Gender"][] = $record[4];
                    $studentData["Religion"][] = $record[5];
                    $studentData["Father Name"][] = $record[6];
                    $studentData["Mother Name"][] = $record[7];
                    $studentData["Mobile No."][] = $record[8];
                }
            }
        }

        $skippedStudentIds = [];
        $increment = 0;
        foreach ($studentData as $studentKey => $stdValue) {

            $studentIdCount = count($studentData["Student ID"]);
            while ($increment < $studentIdCount) {

                if ($increment === $studentIdCount - 1) {
                    break; // Stop the loop when $increment reaches $studentIdCount
                }
                $existsAcademicDetails = AcademicDetail::where('institute_details_id', $this->studentData['institute_details_id'])
                    ->where('custom_student_id', $studentData["Student ID"][$increment])
                    ->where('academic_year', $this->studentData['academic_year'])
                    ->first();

                if ($existsAcademicDetails) {
                    Log::warning("Custom student ID '{$studentData["Student ID"][$increment]}' already exists for institute {$this->studentData['institute_details_id']}");
                    $skippedStudentIds[] = $studentData["Student ID"][$increment];
                    continue; // Skip updating the current student and move to the next iteration
                }

                $student = new Student();
                $student->institute_details_id = $this->studentData['institute_details_id'];
                $student->save();

                $studentId = $student->id;
                // if($student->save()){
                Log::info("Student $student->id saved successfully");

                $check_feeamounts = FeeAmount::where('institute_details_id', $this->studentData['institute_details_id'])
                    ->where('class_id', $this->studentData['class_id'])
                    ->where('group_id', $this->studentData['group_id'])
                    ->where('academic_year_id', $this->studentData['academic_year'])
                    ->where('student_category_id', $this->studentData['category'])
                    ->get();
                if ($check_feeamounts) {
                    foreach ($check_feeamounts as $feeamouts) {
                        $feesubhead = FeeMapping::where('institute_details_id', $this->studentData['institute_details_id'])
                            ->where('fee_head_id', $feeamouts->fee_head_id)
                            ->pluck('fee_subhead_id');
                        foreach ($feesubhead as $fee_subhead_id) {
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
                $insert->custom_student_id = $studentData["Student ID"][$increment];
                $insert->class_roll = $studentData["Roll"][$increment];
                $insert->academic_year = $this->studentData['academic_year'];
                $insert->academic_session = $this->studentData['academic_session'];
                $insert->category = $this->studentData['category'];
                $insert->combinations_pivot_id = $this->studentData['assign_id'];
                $insert->institute_details_id = $this->studentData['institute_details_id'];
                $insert->student_id = $studentId;

                if ($insert->save()) {
                    Log::info('AcademicDetail');
                    $student->academic_details_id = $insert->id;
                    // $student->save();
                    if ($student->save()) {

                        Log::info("Academic Details for $student->id saved successfully in student table");


                        $studentDetail = new StudentDetail();
                        $studentDetail->student_name = $studentData["Name"][$increment];
                        $studentDetail->student_gender = $studentData["Gender"][$increment];
                        $studentDetail->student_religion = $studentData["Religion"][$increment];
                        $studentDetail->student_id = $studentId;
                        $studentDetail->save();
                        // Associate StudentDetail with Student
                        $student->student_details_id = $studentDetail->id;
                        $student->save();

                        Log::info("StudentDetail for $student->id saved successfully");

                        $guardianInput = new GuardianDetail();
                        $guardianInput->father_name = $studentData["Father Name"][$increment];
                        $guardianInput->mother_name = $studentData["Mother Name"][$increment];
                        $guardianInput->father_mobile = $studentData["Mobile No."][$increment];
                        $guardianInput->student_id = $studentId;
                        $guardianInput->save();

                        // Associate GuardianDetail with Student
                        $student->guardian_details_id = $guardianInput->id;
                        $student->save();
                        Log::info("GuardianDetail for $student->id saved successfully");
                    }
                }

                $increment++; // Increment the counter

            }
        }

        // Check if any student IDs were skipped
        if (!empty($skippedStudentIds)) {
            $skippedStudentIdsString = implode(', ', $skippedStudentIds);
            // Provide a user-friendly message with the skipped IDs
            return response()->json([
                'message' => 'Some student IDs were not processed due to existing academic details.',
                'skipped_student_ids' => $skippedStudentIdsString,
            ]);
        } else {
            // Provide a success message if all student IDs were processed successfully
            return response()->json([
                'message' => 'All student IDs processed successfully.',
            ]);
        }
    }
}
