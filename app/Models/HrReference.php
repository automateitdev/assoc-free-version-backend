<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrReference extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_basic_info_id',
        'name',
        'organization',
        'designation',
        'relation',
        'mobile_no',
        'email',
        'address',
    ];

    public function hrBasicInfo()
    {
        return $this->belongsTo(HrBasicInfo::class);
    }
}
