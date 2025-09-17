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
        Schema::create('class_details_institute_class_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_details_id');
            $table->unsignedBigInteger('institute_class_map_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_details_institute_class_map');
    }
};
