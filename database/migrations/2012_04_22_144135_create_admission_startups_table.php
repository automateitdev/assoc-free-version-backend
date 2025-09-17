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
        Schema::create('admission_startups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_details_id');
            $table->integer('core_category_id');
            $table->string('core_category_name');
            $table->integer('core_subcategory_id');
            $table->string('core_subcategory_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_startups');
    }
};
