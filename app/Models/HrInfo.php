<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_name',
        'department',
        'designation',
        'category',
        'job_type',
        'duty_shift',
    ];
}
