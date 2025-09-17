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
        Schema::create('fee_amounts', function (Blueprint $table) {
            $table->id();
            $table->unique(['institute_details_id', 'class_id', 'group_id', 'academic_year_id', 'student_category_id', 'fee_head_id'], 'combine_fee_amount');
            $table->unsignedBigInteger('institute_details_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('student_category_id');
            $table->unsignedBigInteger('fee_head_id');
            $table->decimal('fee_amount', 10, 2);
            $table->decimal('fine_amount', 10, 2)->nullable();
            // $table->unsignedBigInteger('fund_id');
            // $table->decimal('fund_amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_amounts');
    }
};
