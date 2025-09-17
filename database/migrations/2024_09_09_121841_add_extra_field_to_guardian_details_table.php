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
        Schema::table('guardian_details', function (Blueprint $table) {
            $table->string('father_address')->nullable();
            $table->string('father_email')->nullable();
            $table->string('father_nid')->nullable();
            $table->string('father_profession')->nullable();
            $table->string('father_education')->nullable();
            $table->string('father_income')->nullable();
            $table->string('father_photo')->nullable();
            $table->string('mother_address')->nullable();
            $table->string('mother_mobile')->nullable();
            $table->string('mother_email')->nullable();
            $table->string('mother_nid')->nullable();
            $table->string('mother_profession')->nullable();
            $table->string('mother_education')->nullable();
            $table->string('mother_income')->nullable();
            $table->string('mother_photo')->nullable();
            $table->string('gurdian_name')->nullable();
            $table->string('gurdian_address')->nullable();
            $table->string('gurdian_mobile')->nullable();
            $table->string('gurdian_email')->nullable();
            $table->string('gurdian_nid')->nullable();
            $table->string('gurdian_profession')->nullable();
            $table->string('gurdian_education')->nullable();
            $table->string('gurdian_income')->nullable();
            $table->string('gurdian_photo')->nullable();
            $table->string('gurdian_relationship')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guardian_details', function (Blueprint $table) {
            //
        });
    }
};
