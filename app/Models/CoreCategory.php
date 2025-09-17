<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'core_category_name'
    ];

    public function coresubcategories()
    {
        return $this->hasMany(CoreSubcategory::class);
    }
}
