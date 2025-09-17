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
        Schema::create('hr_customs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hr_info_id');
            $table->unsignedBigInteger('institute_details_id');
            $table->string('custome_1')->nullable();
            $table->string('custome_1_alias')->nullable();
            $table->string('custome_2')->nullable();
            $table->string('custome_2_alias')->nullable();
            $table->string('custome_3')->nullable();
            $table->string('custome_3_alias')->nullable();
            $table->string('custome_4')->nullable();
            $table->string('custome_4_alias')->nullable();
            $table->string('custome_5')->nullable();
            $table->string('custome_5_alias')->nullable();
            $table->string('custome_6')->nullable();
            $table->string('custome_6_alias')->nullable();
            $table->string('custome_7')->nullable();
            $table->string('custome_7_alias')->nullable();
            $table->string('custome_8')->nullable();
            $table->string('custome_8_alias')->nullable();
            $table->string('custome_9')->nullable();
            $table->string('custome_9_alias')->nullable();
            $table->string('custome_10')->nullable();
            $table->string('custome_10_alias')->nullable();
            $table->string('custome_11')->nullable();
            $table->string('custome_11_alias')->nullable();
            $table->string('custome_12')->nullable();
            $table->string('custome_12_alias')->nullable();
            $table->string('custome_13')->nullable();
            $table->string('custome_13_alias')->nullable();
            $table->string('custome_14')->nullable();
            $table->string('custome_14_alias')->nullable();
            $table->string('custome_15')->nullable();
            $table->string('custome_15_alias')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_customs');
    }
};
