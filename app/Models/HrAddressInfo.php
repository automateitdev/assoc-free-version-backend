<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrAddressInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_basic_info_id',
        'present_division',
        'present_district',
        'present_upazila',
        'present_post_office',
        'present_address_details',
        'permanent_division',
        'permanent_district',
        'permanent_upazila',
        'permanent_post_office',
        'permanent_address_details',
    ];

    public function hrBasicInfo()
    {
        return $this->belongsTo(HrBasicInfo::class);
    }
}
