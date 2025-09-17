<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'wallet_no',
        'bank_account_name',
        'bank_name',
        'bank_account',
    ];

    public function instituteDetail()
    {
        return $this->belongsTo(InstituteDetail::class, 'institute_details_id');
    }

    public function walletMappings()
    {
        return $this->hasMany(WalletMapping::class);
    }
}
