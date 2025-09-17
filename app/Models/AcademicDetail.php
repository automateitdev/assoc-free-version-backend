<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ClassDetailsInstituteClassMap;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademicDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'student_id',
        'combinations_pivot_id',
        'admission_date',
        'academic_session',
        'academic_year',
        'category',
        'class_roll',
        'mashine_id',
        'custom_student_id',
        'student_type',
        'residential_type'
    ];

    public function academicyear()
    {
        return $this->belongsTo(CoreInstituteConfig::class, 'academic_year');
    }
    public function academicsession()
    {
        return $this->belongsTo(CoreInstituteConfig::class, 'academic_session');
    }
    public function categories()
    {
        return $this->belongsTo(CoreInstituteConfig::class, 'category');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id','id');
    }
    public function studentDetails()
    {
        return $this->belongsTo(StudentDetail::class, 'student_id', 'student_id');
    }


    // extra relationship for primevue serverside datatable
    public function student_details()
    {
        return $this->belongsTo(StudentDetail::class, 'student_id', 'student_id');
    }

    public function guardianDetails()
    {
        return $this->belongsTo(GuardianDetail::class, 'student_id', 'student_id');
    }

    public function combination()
    {
        return $this->belongsTo(ClassDetailsInstituteClassMap::class, 'combinations_pivot_id')->with('class_details.groups');
    }
  
}
