<?php

use App\Utils\GlobalConstant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pay_applies', function (Blueprint $table) {
            $table->id();
            $table->unique(['institute_details_id', 'student_id', 'academic_year_id', 'fee_head_id', 'fee_subhead_id'], 'combine_payapplies');
            $table->unsignedBigInteger('institute_details_id');
            $table->unsignedBigInteger('combinations_pivot_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('fee_head_id');
            $table->unsignedBigInteger('fee_subhead_id');
            $table->decimal('payable', 10, 2);
            $table->date('fee_payable_date')->nullable();
            $table->date('fine_active_date')->nullable();
            $table->date('fee_expire_date')->nullable();
            $table->decimal('fine', 10, 2)->nullable();
            $table->unsignedBigInteger('waiver_id')->nullable();
            $table->decimal('waiver_amount', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_state', GlobalConstant::PAYSTATUS)->default(GlobalConstant::PAYSTATUS[0]);
            $table->string('invoice')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('trx_no')->nullable();
            $table->string('trx_id')->nullable();
            $table->string('portal')->default('FIXED');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_applies');
    }
};
