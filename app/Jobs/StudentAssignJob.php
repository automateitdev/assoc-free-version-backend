<?php

namespace App\Jobs;

use App\Models\StudentAssign;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class StudentAssignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $studentAssign;
    /**
     * Create a new job instance.
     */
    public function __construct($studentAssign)
    {
        $this->studentAssign = $studentAssign;
    }


    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('student assign subject update');
        $update_student_assign = StudentAssign::where('institute_details_id', $this->studentAssign['institute_details_id'])
                                            ->where('academic_year_id', $this->studentAssign['academic_year_id'])
                                            ->where('combinations_pivot_id', $this->studentAssign['combinations_pivot_id'])
                                            ->update(
                                                [
                                                    'subjects' => $this->studentAssign['subjects']
                                                ]
                                                );
    }
}
