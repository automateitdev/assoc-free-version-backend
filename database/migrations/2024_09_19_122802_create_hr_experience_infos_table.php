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
        Schema::create('hr_experience_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hr_info_id');
            $table->string('organization_name')->nullable();
            $table->string('organization_type')->nullable();
            $table->string('designation')->nullable();
            $table->string('department')->nullable();
            $table->text('responsibility')->nullable();
            $table->date('joining_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->string('duration')->nullable();  // Can be calculated or provided as a string
            $table->string('location')->nullable();
            $table->string('attachment')->nullable();  //
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_experience_infos');
    }
};
