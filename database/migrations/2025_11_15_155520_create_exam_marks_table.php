<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExamMarksTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_marks', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('admission_applied_id');
            $table->unsignedBigInteger('exam_id');

            // Marks
            $table->decimal('total_mark', 8, 2);
            $table->decimal('obtained_mark', 8, 2)->nullable();
            $table->string('obtained_grade')->nullable();
            $table->decimal('obtained_grade_point', 4, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_marks');
    }
}
