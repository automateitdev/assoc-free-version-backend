<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DateSetup extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'academic_year_id',
        'class_id',
        'fee_head_id',
        'fee_subhead_id',
        'fee_payable_date',
        'fine_active_date',
        'fee_expire_date'
    ];

    public function feehead()
    {
        return $this->belongsTo(FeeHead::class, 'fee_head_id');
    }
    public function feeSubhead(){
        return $this->belongsTo(FeeSubhead::class, 'fee_subhead_id');
    }
    public function academicYear()
    {
        return $this->belongsTo(CoreInstituteConfig::class, 'academic_year_id');
    }
    public function class()
    {
        return $this->belongsTo(CoreSubcategory::class, 'class_id');
    }
    
}
