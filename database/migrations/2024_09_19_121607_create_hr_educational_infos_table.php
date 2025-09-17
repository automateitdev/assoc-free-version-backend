<?php

use App\Utils\GlobalConstant;
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
        Schema::create('hr_educational_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hr_info_id');
            $table->string('level_of_education')->nullable();
            $table->string('exam_degree_title')->nullable();
            $table->string('major_group')->nullable();
            $table->string('institute_name')->nullable();
            $table->enum('result', GlobalConstant::RESULT)->nullable();
            $table->decimal('gpa_division', 5, 2)->nullable();
            $table->decimal('scale', 4, 2)->nullable();
            $table->year('passing_year')->nullable();
            $table->string('board')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_educational_infos');
    }
};
