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
        Schema::create('fee_subheads', function (Blueprint $table) {
            $table->id();
            $table->unique(['institute_details_id', 'name'], 'combined_subhead');
            $table->unsignedBigInteger('institute_details_id');
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_subheads');
    }
};
