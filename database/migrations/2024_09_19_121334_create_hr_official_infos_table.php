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
        Schema::create('hr_official_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hr_info_id');
            $table->string('department')->nullable();
            $table->string('designation')->nullable();
            $table->date('join_date')->nullable();
            $table->string('category')->nullable();
            $table->string('shift')->nullable();
            $table->string('job_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_official_infos');
    }
};
