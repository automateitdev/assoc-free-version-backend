<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassDetailsInstituteClassMap extends Model
{
    use HasFactory;
    protected $table = 'class_details_institute_class_map';
    protected $fillable = [
        'class_details_id',
        'institute_class_map_id'
    ];

    public function class_details()
    {
        return $this->hasMany(ClassDetails::class, 'id', 'class_details_id');
    }

    public function institute_class_map()
    {
        return $this->hasMany(InstituteClassMap::class, 'institute_class_map_id', 'id');
    }
}
