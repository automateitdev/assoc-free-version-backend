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
        Schema::create('class_details', function (Blueprint $table) {
            $table->id();
            $table->unique(array('group_id', 'shift_id', 'section_id'), 'class_details_combination');
            $table->integer('group_id')->required();
            $table->integer('shift_id')->required();
            $table->integer('section_id')->required();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_details');
    }
};
