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
        Schema::create('admission_class_setups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_details_id');
            $table->bigInteger('class_id');
            $table->string('class_name');
            $table->string('center_id');
            $table->string('center_name');
            $table->string('institute_id');
            $table->string('institute_name');
            // $table->bigInteger('group_id');
            // $table->string('group_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_class_setups');
    }
};
