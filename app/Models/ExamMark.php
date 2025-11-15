<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamMark extends Model
{
    use HasFactory;
    protected $fillable = [
        'admission_applied_id',
        'exam_id',
        'total_mark',
        'obtained_mark',
        'obtained_grade',
        'obtained_grade_point'
    ];


    public function applicant()
    {
        return $this->belongsTo(AdmissionApplied::class, 'admission_applied_id', 'id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
