<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeAmount extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'class_id',
        'group_id',
        'academic_year_id',
        'student_category_id',
        'fee_head_id',
        'fee_amount',
        'fine_amount'
    ];

    public function feehead()
    {
        return $this->belongsTo(FeeHead::class, 'fee_head_id');
    }
    public function academicYear()
    {
        return $this->belongsTo(CoreInstituteConfig::class, 'academic_year_id');
    }
    public function categories()
    {
        return $this->belongsTo(CoreInstituteConfig::class, 'student_category_id');
    }
    public function class()
    {
        return $this->belongsTo(CoreSubcategory::class, 'class_id');
    }
    public function classDetail()
    {
        return $this->belongsTo(ClassDetails::class, 'group_id', 'group_id');
    }
    
}
