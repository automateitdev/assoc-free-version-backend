<?php

use App\Utils\GlobalConstant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pay_applies', function (Blueprint $table) {
            $table->enum('waiver_applied', GlobalConstant::SWITCH)->default(GlobalConstant::SWITCH[1]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pay_applies', function (Blueprint $table) {
            //
        });
    }
};
