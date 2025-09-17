<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletMapping extends Model
{
    use HasFactory;
    protected $fillable = [
        'wallet_id',
        'payment_code',
        'portal',
        'status',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }
}
