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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('institute_details_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->string('academic_year');
            $table->unsignedBigInteger('class_id');
            $table->string('class_name');
            $table->decimal('total_marks', 8, 2)->nullable();
            $table->boolean('is_generic')->default(false);
            $table->boolean('is_published')->default(false);
            $table->boolean('has_subjects')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
