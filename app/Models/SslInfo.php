<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SslInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'store_id',
        'store_password',
    ];

    public function institute()
    {
        return $this->belongsTo(InstituteDetail::class, 'institute_details_id');
    }
}
