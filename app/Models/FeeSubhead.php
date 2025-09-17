<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeSubhead extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'name'
    ];
}
