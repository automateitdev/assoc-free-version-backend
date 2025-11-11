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
        Schema::table('admission_applied', function (Blueprint $table) {
            // Add a unique index to prevent duplicate rolls in same academic_year + class + center
            $table->unique(
                ['academic_year', 'class_id', 'center_id', 'assigned_roll'],
                'unique_roll_per_year_class_center'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_applied', function (Blueprint $table) {
            $table->dropUnique('unique_roll_per_year_class_center');
        });
    }
};
