<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_details_id',
        'admission_payment_id',
        'roll',
        'name',
        'board',
        'passing_year',
        'admission_roll',
        'unique_id',
        'status'
    ];

    public function admissionPayment()
    {
        return $this->belongsTo(AdmissionPayment::class, 'admission_payment_id');
    }
}
