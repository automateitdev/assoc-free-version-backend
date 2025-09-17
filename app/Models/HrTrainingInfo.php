<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrTrainingInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_basic_info_id',
        'title',
        'institute_name',
        'topics_covered',
        'duration',
        'start_date',
        'end_date',
        'location',
        'achievement',
        'note',
        'attachment',
    ];

    public function hrBasicInfo()
    {
        return $this->belongsTo(HrBasicInfo::class);
    }
}
