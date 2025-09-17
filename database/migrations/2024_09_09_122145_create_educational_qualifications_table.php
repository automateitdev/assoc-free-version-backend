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
        Schema::create('educational_qualifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_details_id');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('exam');
            $table->string('institute');
            $table->string('board');
            $table->string('group')->nullable();
            $table->string('roll')->unique();
            $table->string('reg_no')->unique();
            $table->decimal('gpa_cgpa', 3, 2);
            $table->string('passing_year');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_qualifications');
    }
};
