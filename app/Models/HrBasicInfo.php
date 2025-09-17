<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrBasicInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_info_id',
        'institute_details_id',
        'custom_hr_id',
        'name',
        'name_bangla',
        'gender',
        'religion',
        'date_of_birth',
        'blood_group',
        'mobile_no',
        'email',
        'attachment',
        'photo',
    ];
    public function institute()
    {
        return $this->belongsTo(InstituteDetail::class, 'institute_details_id');
    }
}
