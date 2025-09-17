<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryStudent extends Model
{
    use HasFactory;

    protected $fillable = [ 
        'institute_details_id',
        'unique_number',
        'academic_year',
        'class',
        'shift',
        'group',
        'lottery_number',
        'lottery_status'
    ];

    public function admissionApplied()
    {
        return $this->belongsTo(AdmissionApplied::class, 'unique_number', 'unique_number');
    }
}
