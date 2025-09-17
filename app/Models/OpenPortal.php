<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenPortal extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'academic_year',
        'class_name',
        'group_name',
        'shift',
        'section',
        'rules',
        'student_id',
        'student_name',
        'fee_head_name',
        'amount',
        'start_date',
        'end_date',
        'payment_state',
        'invoice',
        'payment_date',
        'trx_no',
        'trx_id',
        'history_id',
        'portal'
    ];
}
