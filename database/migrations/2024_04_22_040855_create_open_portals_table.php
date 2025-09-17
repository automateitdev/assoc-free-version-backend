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
        Schema::create('open_portals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_details_id');
            $table->unsignedBigInteger('academic_year')->nullable();
            $table->string('class_name')->nullable();
            $table->string('group_name')->nullable();
            $table->string('shift')->nullable();
            $table->string('section')->nullable();
            $table->enum('rules', GlobalConstant::RULES);
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('student_name')->nullable();
            $table->string('fee_head_name')->nullable();
            $table->decimal('amount', 10,2)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('payment_state', GlobalConstant::PAYSTATUS)->default(GlobalConstant::PAYSTATUS[0]);
            $table->string('invoice')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('trx_no')->nullable();
            $table->string('trx_id')->nullable();
            $table->unsignedBigInteger('history_id');
            $table->string('portal')->default('OPEN');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_portals');
    }
};
