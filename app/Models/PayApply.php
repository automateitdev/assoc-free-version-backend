<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayApply extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'combinations_pivot_id',
        'student_id',
        'academic_year_id',
        'fee_head_id',
        'fee_subhead_id',
        'payable',
        'fee_payable_date',
        'fine_active_date',
        'fee_expire_date',
        'fine',
        'waiver_id',
        'waiver_amount',
        'total_amount',
        'payment_state',
        'invoice',
        'payment_date',
        'trx_no',
        'trx_id',
        'portal',
        'waiver_applied',
    ];
    
    protected $casts = [
        'pay_details' => 'json',
    ];

    public function feeHead(){
        return $this->belongsTo(FeeHead::class, 'fee_head_id', 'id');
    }
    public function feeSubead(){
        return $this->belongsTo(FeeSubhead::class, 'fee_subhead_id', 'id');
    }
    public function academicyear()
    {
        return $this->belongsTo(CoreInstituteConfig::class, 'academic_year_id');
    }
    public function waiver()
    {
        return $this->belongsTo(Waiver::class, 'waiver_id', 'id');
    }
    public function academic_details()
    {
        return $this->belongsTo(AcademicDetail::class, 'student_id', 'student_id');
    }
    public function student_details()
    {
        return $this->belongsTo(StudentDetail::class, 'student_id', 'student_id');
    }

    public function group_name()
    {
        return $this->belongsTo(CoreInstituteConfig::class);
    }
}
