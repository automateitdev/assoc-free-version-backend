<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaiverHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'combinations_pivot_id',
        'academic_year_id',
        'student_id',
        'fee_head_id',
        'waiver_id',
        'waiver_amount',
    ];

    public function studentDetails()
    {
        return $this->belongsTo(StudentDetail::class, 'student_id', 'student_id');
    }

    public function feehead()
    {
        return $this->belongsTo(FeeHead::class, 'fee_head_id');
    }
    public function waiver()
    {
        return $this->belongsTo(Waiver::class, 'waiver_id');
    }
}
