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
        Schema::create('date_setups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_details_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->bigInteger('class_id');
            $table->bigInteger('fee_head_id');
            $table->bigInteger('fee_subhead_id');
            $table->date('fee_payable_date');
            $table->date('fine_active_date')->nullable();
            $table->date('fee_expire_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('date_setups');
    }
};
