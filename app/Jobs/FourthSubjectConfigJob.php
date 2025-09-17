<?php

namespace App\Jobs;

use App\Models\StudentAssign;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class FourthSubjectConfigJob implements ShouldQueue
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
    public function handle(): void
    {
        // dd($this->studentData);
        $findStudent = StudentAssign::where('id', $this->studentData['studentID'])
                            ->where('institute_details_id', $this->studentData['institute_details_id'])
                            ->first();

        $existingData = json_decode($findStudent->subjects, true);
      
        $searchedSubjectIds = array_map('intval', $this->studentData['subject_id']);
        // Iterate over existingData and update "type" for matching "subject_id"
        foreach ($existingData as &$subject) {
            if (in_array($subject['subject_id'], $searchedSubjectIds)) {
                $subject['type'] = $this->studentData['subject_type'];
            }
        }
    
        // Encode the updated data back to JSON
        $updatedSubjects = json_encode($existingData);
    
        // Save the updated data back to the database
        $findStudent->subjects = $updatedSubjects;
        $findStudent->save();

    }
}
