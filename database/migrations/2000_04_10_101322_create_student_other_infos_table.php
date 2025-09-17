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
        Schema::create('student_other_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_details_id');
            $table->enum('quota', GlobalConstant::YN)->nullable();
            $table->string('quota_name')->nullable();
            $table->enum('vaccinated', GlobalConstant::YN)->nullable();
            $table->string('vaccine_name')->nullable();
            $table->string('vaccine_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_other_infos');
    }
};
