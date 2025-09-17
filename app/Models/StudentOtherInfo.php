<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentOtherInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'quota',
        'quota_name',
        'vaccinated',
        'vaccine_name',
        'vaccine_file',
    ];
}
