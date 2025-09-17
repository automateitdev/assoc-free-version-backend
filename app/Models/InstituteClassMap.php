<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstituteClassMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'class_id',
        'class_name'
    ];

    public function classDetails()
    {
        return $this->belongsToMany(ClassDetails::class)->withPivot('id');
    }

}
