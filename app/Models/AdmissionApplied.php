<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionApplied extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'unique_number',
        'student_name_bangla',
        'student_name_english',
        'student_mobile',
        'father_name_bangla',
        'father_name_english',
        'father_nid',
        'father_mobile',
        'mother_name_bangla',
        'mother_name_english',
        'mother_nid',
        'mother_mobile',
        'nationality',
        'date_of_birth',
        'student_nid_or_birth_no',
        'gender',
        'religion',
        'blood_group',
        'merital_status',
        'present_division',
        'present_district',
        'present_upozilla',
        'present_post_office',
        'present_post_code',
        'present_address',
        'permanent_division',
        'permanent_district',
        'permanent_upozilla',
        'permanent_post_office',
        'permanent_post_code',
        'permanent_address',
        'guardian_name',
        'guardian_relation',
        'guardian_mobile',
        'guardian_occupation',
        'guardian_yearly_income',
        'guardian_property',
        'academic_year',
        // 'shift',

        'class_id',
        'class_name',

        'institute_id',
        'institute_name',

        'center_id',
        'center_name',

        'subject',
        'edu_information',
        'assigned_roll',
        'quota',
        'vaccine',
        'vaccine_name',
        'vaccine_certificate',
        'student_pic',
        'student_birth_nid_file',
        'other_file',
        'approval_status',
        'status',
        'date',
        'amount'
    ];

    public function admissionSpg()
    {
        return $this->hasOne(AdmissionSpgPay::class, 'unique_number', 'unique_number');
    }

    public function institute()
    {
        return $this->belongsTo(InstituteDetail::class, 'institute_details_id');
    }
}
