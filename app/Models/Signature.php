<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signature extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image_path',
        'institute_details_id'
    ];

    public function instituteDetail()
    {
        return $this->belongsTo(InstituteDetail::class, 'institute_details_id', 'id');
    }
}
