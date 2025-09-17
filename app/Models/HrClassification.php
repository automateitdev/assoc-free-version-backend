<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrClassification extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'name',
        'type',
        'description',
    ];

    public function instituteDetails()
    {
        return $this->belongsTo(InstituteDetail::class);
    }
}
