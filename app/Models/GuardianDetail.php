<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuardianDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'father_name',
        'father_address',
        'father_mobile',
        'father_email',
        'father_nid',
        'father_profession',
        'father_education',
        'father_income',
        'father_photo',
        'mother_name',
        'mother_address',
        'mother_mobile',
        'mother_email',
        'mother_nid',
        'mother_profession',
        'mother_education',
        'mother_income',
        'mother_photo',
        'gurdian_name',
        'gurdian_address',
        'gurdian_mobile',
        'gurdian_email',
        'gurdian_nid',
        'gurdian_profession',
        'gurdian_education',
        'gurdian_income',
        'gurdian_photo',
        'gurdian_relationship'
    ];
}
