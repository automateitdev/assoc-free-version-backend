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
        Schema::create('student_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('student_name');
            $table->enum('student_gender', GlobalConstant::GENDER)->nullable();
            $table->enum('student_religion', GlobalConstant::RELIGION)->nullable();
            $table->string('student_nationality')->nullable();
            $table->date('student_dob')->nullable();
            $table->string('student_birth_certificate')->nullable();
            $table->string('student_nid')->unique()->nullable();
            $table->string('student_mobile')->nullable();
            $table->string('student_email')->unique()->nullable();
            $table->enum('blood_group', GlobalConstant::BLOOD)->default(GlobalConstant::BLOOD[0]);
            $table->mediumText('photo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_details');
    }
};
