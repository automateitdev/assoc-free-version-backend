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
        
        Schema::create('academic_details', function (Blueprint $table) {
            $table->id();
            $table->unique(array('institute_details_id', 'academic_year', 'student_id', 'custom_student_id'), 'academic_details_combination');
            $table->unsignedBigInteger('institute_details_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('combinations_pivot_id');
            $table->date('admission_date')->nullable();
            $table->string('academic_session');
            $table->string('academic_year');
            $table->string('category');
            $table->bigInteger('class_roll')->nullable();
            $table->string('custom_student_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_details');
    }
};
