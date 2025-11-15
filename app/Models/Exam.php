<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;
    protected $fillable = [
        'institute_details_id',
        'academic_year',
        'academic_year_id',
        'class_id',
        'class_name',
        'name',
        'total_marks',
        'is_generic',
        'is_published',
        'has_subjects',
    ];

    protected $casts = [
        'is_generic' => 'boolean',
        'is_published' => 'boolean',
        'has_subjects' => 'boolean',
    ];

    public function instituteDetail()
    {
        return $this->belongsTo(InstituteDetail::class, 'institute_details_id');
    }

    public function centerExams()
    {
        return $this->hasMany(CenterExam::class, 'exam_id');
    }

    public function examMarks()
    {
        return $this->hasMany(ExamMark::class);
    }
}
