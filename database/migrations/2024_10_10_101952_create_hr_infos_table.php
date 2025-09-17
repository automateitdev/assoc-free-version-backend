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
        Schema::create('hr_infos', function (Blueprint $table) {
            $table->id();
            $table->string('hr_name');
            $table->string('department')->nullable();
            $table->string('designation')->nullable();
            $table->string('category')->nullable();
            $table->string('job_type')->nullable();
            $table->string('duty_shift')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->enum('status',GlobalConstant::INSTITUTE_HR_STATUS)->default(GlobalConstant::INSTITUTE_HR_STATUS[0]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_infos');
    }
};
