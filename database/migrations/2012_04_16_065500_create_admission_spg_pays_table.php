<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admission_spg_pays', function (Blueprint $table) {
            $table->id();
            $table->string('unique_number');
            $table->unsignedBigInteger('institute_details_id');
            $table->string('session_token');
            $table->string('status');
            $table->string('msg');
            $table->string('transaction_id');
            $table->string('transaction_date');
            $table->string('invoice_no');
            $table->string('invoice_date');
            $table->string('br_code');
            $table->string('applicant_name');
            $table->string('applicant_no');
            $table->string('total_amount');
            $table->string('pay_status');
            $table->string('pay_mode');
            $table->string('pay_amount');
            $table->string('vat');
            $table->string('comission');
            $table->string('scroll_no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_spg_pays');
    }
};
