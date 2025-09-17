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
        Schema::create('admission_setups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_details_id');
            // $table->string('subject')->nullable();
            // $table->string('academic_info')->nullable();
            $table->enum('enabled', GlobalConstant::YN);
            $table->text('heading')->nullable();
            $table->enum('form', GlobalConstant::YN);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_setups');
    }
};
