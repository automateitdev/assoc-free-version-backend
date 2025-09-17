<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;
class InstituteDetail extends Model
{
    use HasFactory, HasRoles;

    protected $fillable = [
        'institute_id',
        'institute_name',
        'institute_ein',
        'institute_contact',
        'institute_email',
        'institute_category',
        'institute_type',
        'institute_board',
        'institute_address',
        'institute_district',
        'institute_sub_distric',
        'institute_division',
        'logo',
        'gateway',
    ];

    public function institute_profile()
    {
        return $this->hasMany(InstituteProfile::class, 'institute_details_id');
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class, 'institute_details_id');
    }
}
