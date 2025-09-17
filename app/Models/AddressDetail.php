<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddressDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'present_address',
        'present_village_town',
        'present_ward_union',
        'present_po',
        'present_ps_upazilla',
        'present_district',
        'present_division',
        'present_country',
        'present_landmark',
        'permanent_address',
        'permanent_village_town',
        'permanent_ward_union',
        'permanent_po',
        'permanent_ps_upazilla',
        'permanent_district',
        'permanent_division',
        'permanent_country',
        'permanent_landmark'
    ];
}
