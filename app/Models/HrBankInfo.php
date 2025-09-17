<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrBankInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_basic_info_id',
        'account_name',
        'account_no',
        'bank_name',
        'branch_name',
        'routing_no',
        'account_type',
        'status',
    ];

    public function hrBasicInfo()
    {
        return $this->belongsTo(HrBasicInfo::class);
    }
}
