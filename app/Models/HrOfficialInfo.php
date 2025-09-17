<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrOfficialInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_basic_info_id',
        'department',
        'designation',
        'join_date',
        'category',
        'shift',
        'job_type',
    ];

    public function hrBasicInfo()
    {
        return $this->belongsTo(HrBasicInfo::class);
    }
}
