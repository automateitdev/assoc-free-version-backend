<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreInstituteConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'coresubcategory_details_id',
        'type'
    ];

    public function coresubcategories()
    {
        return $this->belongsTo(CoreSubcategory::class, 'coresubcategory_details_id');
    }
    
    
}
