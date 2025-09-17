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
        Schema::create('hr_references', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hr_info_id');
            $table->string('name')->nullable();
            $table->string('organization')->nullable();
            $table->string('designation')->nullable();
            $table->string('relation')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_references');
    }
};
