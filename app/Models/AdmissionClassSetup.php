<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionClassSetup extends Model
{
    use HasFactory;
    protected $fillable = [
        'institute_details_id',
        'class_id',
        'class_name',
        'group_id',
        'group_name',
    ];

    public function institute()
    {
        return $this->belongsTo(InstituteDetail::class, 'institute_details_id');
    }
}
