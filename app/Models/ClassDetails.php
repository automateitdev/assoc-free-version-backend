<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'section_id',
        'group_id',
    ];

    public function shifts()
    {
        return $this->belongsTo(CoreSubcategory::class, 'shift_id');
    }
    public function sections()
    {
        return $this->belongsTo(CoreSubcategory::class, 'section_id');
    }
    public function groups()
    {
        return $this->belongsTo(CoreSubcategory::class, 'group_id');
    }
   
    public function instituteClassMaps(){
        return $this->belongsToMany(InstituteClassMap::class)->withPivot('id');
    }
}
