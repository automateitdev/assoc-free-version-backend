<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'academic_year',
        'class',
        'shift',
        'group',
        'amount',
        'roll_start',
        'start_date_time',
        'end_date_time',
        'exam_enabled',
        'exam_date_time',
    ];

    public function admissionConfigs()
    {
        return $this->hasMany(AdmissionConfig::class);
    }
}
