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

        'class_id',
        'class_name',

        // 'shift',
        'center_id',
        'center_name',
        // 'group',
        'institute_id',
        'institute_name',

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
