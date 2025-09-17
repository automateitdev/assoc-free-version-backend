<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstituteDepartmentMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'department_id'
    ];

    public function institute()
    {
        return $this->belongsTo(InstituteDetail::class, 'institute_details_id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
