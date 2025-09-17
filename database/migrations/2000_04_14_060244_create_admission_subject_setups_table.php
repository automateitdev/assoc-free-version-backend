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
        Schema::create('admission_subject_setups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_details_id');
            $table->bigInteger('class_id');
            $table->bigInteger('group_id');
            $table->string('class_name');
            $table->string('group_name');
            $table->json('compulsory');
            $table->json('group');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_subject_setups');
    }
};
