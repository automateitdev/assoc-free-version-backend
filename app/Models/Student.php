<?php

namespace App\Models;

use App\Models\StudentDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'student_details_id',
        'guardian_details_id',
        'academic_details_id',
        'address_details_id',
        'status'
    ];

    public function studentDetails()
    {
        return $this->belongsTo(StudentDetail::class, 'student_details_id');
    }

    public function academicDetails()
    {
        return $this->hasMany(AcademicDetail::class);
    }
    
    public function guardianDetails()
    {
        return $this->belongsTo(GuardianDetail::class, 'guardian_details_id');
    }
    
}
