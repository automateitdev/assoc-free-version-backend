<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'student_name',
        'student_gender',
        'student_religion',
        'student_dob',
        'student_birth_certificate',
        'student_nid',
        'student_mobile',
        'student_email',
        'student_height',
        'student_weight',
        'student_special_disease',
        'blood_group',
        'photo'
    ];

}
