<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\StudentAssign;
use Illuminate\Bus\Queueable;
use App\Models\AcademicDetail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class MigrationPushbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $studentPushback;
    /**
     * Create a new job instance.
     */
    public function __construct($studentPushback)
    {
        $this->studentPushback = $studentPushback;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $academicDetail = AcademicDetail::find($this->studentPushback['delete_id']);
        if(!empty($academicDetail))
        {
            $academicDetail->delete();
        }
        
        $academicDetail = AcademicDetail::where('institute_details_id', $this->studentPushback['institute_details_id'])
                                        ->where('student_id',  $this->studentPushback['student_id'])
                                        ->where('academic_year',  $this->studentPushback['academic_year_id'])
                                        ->first();

        $students = Student::where('id', $this->studentPushback['student_id'])
                                    ->update(
                                        [
                                            'academic_details_id' => $academicDetail->id
                                        ]
                                        );
    }
}
