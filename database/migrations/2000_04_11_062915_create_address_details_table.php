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
        Schema::create('address_details', function (Blueprint $table) {
            $table->id();
            $table->string('present_address');
            $table->string('present_village_town');
            $table->string('present_ward_union')->nullable();
            $table->string('present_po');
            $table->string('present_ps_upazilla');
            $table->string('present_district');
            $table->string('present_division');
            $table->string('present_country');
            $table->string('present_landmark')->nullable();
            $table->string('permanent_address');
            $table->string('permanent_village_town');
            $table->string('permanent_ward_union')->nullable();;
            $table->string('permanent_po');
            $table->string('permanent_ps_upazilla');
            $table->string('permanent_district');
            $table->string('permanent_division');
            $table->string('permanent_country');
            $table->string('permanent_landmark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('address_details');
    }
};
