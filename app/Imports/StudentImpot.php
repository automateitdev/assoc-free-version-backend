<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\StudentDetail;
use App\Models\AcademicDetail;
use App\Models\GuardianDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentImpot implements ToModel, WithHeadingRow
{

    private $institute_details_id;
    private $academic_year;
    private $academic_year_start_date;
    private $academic_year_end_date;
    private $academic_session;
    private $category;
    private $institute_class_map_id;

    public function __construct(
        $institute_details_id,
        $academic_year,
        $academic_year_start_date,
        $academic_year_end_date,
        $academic_session,
        $category,
        $institute_class_map_id
    ) {
        // dd($institute_details_id);
        $this->institute_details_id = $institute_details_id;
        $this->academic_year = $academic_year;
        $this->academic_year_start_date = $academic_year_start_date;
        $this->academic_year_end_date = $academic_year_end_date;
        $this->academic_session = $academic_session;
        $this->category = $category;
        $this->institute_class_map_id = $institute_class_map_id;
    }
    
    public function model(array $row)
    {
        Log::info('Asci');
        
            $student = new Student();
            $student->institute_details_id = $this->institute_details_id;

            if($student->save()){
                Log::info('Student');

                $insert = new AcademicDetail();  
                $insert->custom_student_id = $row['student_id'];
                $insert->class_roll = $row['roll'];
                $insert->category = $this->category;
                $insert->academic_session = $this->academic_session;
                $insert->institute_class_map_id = $this->institute_class_map_id;
                $insert->institute_details_id = $this->institute_details_id;
                $insert->academic_year = $this->academic_year;
                $insert->academic_year_start_date = $this->academic_year_start_date;
                $insert->academic_year_end_date = $this->academic_year_end_date;
                $insert->student_id = $student->id;
                
                if($insert->save())
                {
                    Log::info('AcademicDetail');

                    // Associate StudentDetail with Student
                    $student->academic_details_id = $insert->id;
                    
                    if($student->save())
                    {
                        $studentDetail = new StudentDetail();
                        $studentDetail->student_name = $row['name'];
                        $studentDetail->student_gender = $row['gender'];
                        $studentDetail->student_religion = $row['religion'];
                        $studentDetail->student_id = $student->id;
                        $studentDetail->save();
                        Log::info('StudentDetail');
    
                        // Associate StudentDetail with Student
                        $student->student_details_id = $studentDetail->id;
                        $student->save();
    
                        $guardianInput = new GuardianDetail();
                        $guardianInput->father_name = $row['father_name'];
                        $guardianInput->mother_name = $row['mother_name'];
                        $guardianInput->father_mobile = $row['mobile_no'];
                        $guardianInput->student_id = $student->id;
                        $guardianInput->save();
    
                        // Associate GuardianDetail with Student
                        $student->guardian_details_id = $guardianInput->id;
                        $student->save();
                        Log::info('GuardianDetail');
                    }

                }
                 
            } else {
                Log::error('Failed to save data');
            }
    }
}
