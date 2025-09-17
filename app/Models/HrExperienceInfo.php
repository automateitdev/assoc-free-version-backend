<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrExperienceInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_basic_info_id',
        'organization_name',
        'organization_type',
        'designation',
        'department',
        'responsibility',
        'joining_date',
        'resign_date',
        'duration',
        'location',
        'attachment',
    ];

    public function hrBasicInfo()
    {
        return $this->belongsTo(HrBasicInfo::class);
    }
}
