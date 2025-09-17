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
        Schema::table('admission_setups', function (Blueprint $table) {
            $table->string('subject')->default('NO');
            $table->string('academic_info')->default('NO');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_setups', function (Blueprint $table) {
            $table->dropColumn(['subject', 'academic_info']);
        });
    }
};
