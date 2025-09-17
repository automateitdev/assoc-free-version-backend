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
        Schema::table('admission_applieds', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_roll')->nullable()->after('edu_information');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_applieds', function (Blueprint $table) {
            //
        });
    }
};
