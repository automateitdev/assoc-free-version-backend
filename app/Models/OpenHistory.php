<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'rule_id',
        'start_date_time',
        'end_date_time',
        'amount',
        'file',
        'status'
    ];
}
