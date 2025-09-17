<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrCustom extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_basic_info_id',
        'custome_1',
        'custome_2',
        'custome_3',
        'custome_4',
        'custome_5',
        'custome_6',
        'custome_7',
        'custome_8',
        'custome_9',
        'custome_10',
        'custome_11',
        'custome_12',
        'custome_13',
        'custome_14',
        'custome_15',
    ];

    public function hrBasicInfo()
    {
        return $this->belongsTo(HrBasicInfo::class);
    }
}
