<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'fee_head_id',
        'fee_subhead_id',
    ];

    public function feeHead(){
        return $this->belongsTo(FeeHead::class, 'fee_head_id', 'id');
    }
    public function feeSubhead(){
        return $this->belongsTo(FeeSubhead::class, 'fee_subhead_id', 'id');
    }
}
