<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreSubcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'core_category_id',
        'core_subcategory_name'
    ];

    public function corecategory()
    {
        return $this->belongsTo(CoreCategory::class, 'core_category_id');
    }


}
