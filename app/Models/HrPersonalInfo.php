<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrPersonalInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_basic_info_id',
        'father_name',
        'father_name_bangla',
        'mother_name',
        'mother_name_bangla',
        'marital_status',
        'spouse_name',
        'spouse_name_bangla',
        'no_of_child',
        'nationality',
        'nid_no',
        'passport_no',
        'tin_no',
        'mpo_id',
        'index_no',
        'language',
        'extra_curriculam',
        'specialization',
        'nid_attachment',
        'passport_attachment',
    ];

    public function hrBasicInfo()
    {
        return $this->belongsTo(HrBasicInfo::class);
    }
}
