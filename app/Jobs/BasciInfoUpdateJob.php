<?php

namespace App\Jobs;

use App\Models\StudentDetail;
use Illuminate\Bus\Queueable;
use App\Models\AcademicDetail;
use App\Models\GuardianDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class BasciInfoUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $basicUpdate;
    /**
     * Create a new job instance.
     */
    public function __construct($basicUpdate)
    {
        $this->basicUpdate = $basicUpdate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // dd($this->basicUpdate);
            $academicDetailsUpdate = [];
            $studentDetailsUpdate = [];
            $guardianDetailsUpdate = [];

            // Check if the fields are not null before including them in the update array    

            if ($this->basicUpdate['class_roll'] !== null) {
                $academicDetailsUpdate['class_roll'] = $this->basicUpdate['class_roll'];
            }
            if ($this->basicUpdate['admission_date'] !== null) {
                $academicDetailsUpdate['admission_date'] = $this->basicUpdate['admission_date'];
            }

            if ($this->basicUpdate['custom_student_id'] !== null) {
                $checkCustomId = AcademicDetail::where('institute_details_id', $this->basicUpdate['institute_details_id'])
                                                ->where('custom_student_id', $this->basicUpdate['custom_student_id'])
                                                ->first();
                
                if ($checkCustomId) {
                    Log::warning("Custom student ID '{$this->basicUpdate['custom_student_id']}' already exists for institute {$this->basicUpdate['institute_details_id']}");
                }else{
                    $academicDetailsUpdate['custom_student_id'] = $this->basicUpdate['custom_student_id'];
                }
            }

            if ($this->basicUpdate['student_name'] !== null) {
                $studentDetailsUpdate['student_name'] = $this->basicUpdate['student_name'];
            }

            if ($this->basicUpdate['student_gender'] !== null) {
                $studentDetailsUpdate['student_gender'] = $this->basicUpdate['student_gender'];
            }

            if ($this->basicUpdate['student_religion'] !== null) {
                $studentDetailsUpdate['student_religion'] = $this->basicUpdate['student_religion'];
            }

            if ($this->basicUpdate['father_name'] !== null) {
                $guardianDetailsUpdate['father_name'] = $this->basicUpdate['father_name'];
            }

            if ($this->basicUpdate['mother_name'] !== null) {
                $guardianDetailsUpdate['mother_name'] = $this->basicUpdate['mother_name'];
            }

            if ($this->basicUpdate['father_mobile'] !== null) {
                $guardianDetailsUpdate['father_mobile'] = $this->basicUpdate['father_mobile'];
            }

            if ($this->basicUpdate['blood_group'] !== null) {
                $studentDetailsUpdate['blood_group'] = $this->basicUpdate['blood_group'];
            }
            if ($this->basicUpdate['student_dob'] !== null) {
                $studentDetailsUpdate['student_dob'] = $this->basicUpdate['student_dob'];
            }

            // Update the records with non-null values

            if (!empty($academicDetailsUpdate)) {
                AcademicDetail::where('institute_details_id', $this->basicUpdate['institute_details_id'])
                    ->where('academic_year', $this->basicUpdate['academic_year'])
                    ->where('student_id', $this->basicUpdate['student_id'])
                    ->update($academicDetailsUpdate);
            }

            if (!empty($studentDetailsUpdate)) {
                StudentDetail::where('student_id', $this->basicUpdate['student_id'])
                    ->update($studentDetailsUpdate);
            }

            if (!empty($guardianDetailsUpdate)) {
                GuardianDetail::where('student_id', $this->basicUpdate['student_id'])
                    ->update($guardianDetailsUpdate);
            }
            Log::info('Basic information updated successfully.', $this->basicUpdate);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error updating basic information', [
                'basic_update' => $this->basicUpdate,
                'error_message' => $e->getMessage(),
            ]);

            // Throw the exception again to mark the job as failed
            throw $e;
        }
    }

}
