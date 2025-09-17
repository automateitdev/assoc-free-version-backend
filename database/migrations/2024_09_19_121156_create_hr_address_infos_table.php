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
        Schema::create('hr_address_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hr_info_id');
            $table->string('present_division')->nullable();
            $table->string('present_district')->nullable();
            $table->string('present_upazila')->nullable();
            $table->string('present_post_office')->nullable();
            $table->text('present_address_details')->nullable();
            $table->string('permanent_division')->nullable();
            $table->string('permanent_district')->nullable();
            $table->string('permanent_upazila')->nullable();
            $table->string('permanent_post_office')->nullable();
            $table->text('permanent_address_details')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_address_infos');
    }
};
