<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionSetup extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'enabled',
        'heading',
        'form',
        'subject',
        'academic_info',
    ];

    public function institute()
    {
        return $this->belongsTo(InstituteDetail::class, 'institute_details_id');
    }
}
