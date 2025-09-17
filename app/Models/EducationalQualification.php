<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalQualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'exam',
        'institute',
        'board',
        'group',
        'roll',
        'reg_no',
        'gpa_cgpa',
        'passing_year'
    ];
}
