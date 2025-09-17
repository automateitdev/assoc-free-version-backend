<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['institute_details_id', 'unique_number', 'amount', 'status', 'transaction_id', 'currency'];
}
