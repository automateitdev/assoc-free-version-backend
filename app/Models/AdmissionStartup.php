<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionStartup extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'core_category_id',
        'core_category_name',
        'core_subcategory_id',
        'core_subcategory_name',
    ];
}
