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
        Schema::create('admission_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_details_id');
            $table->unsignedBigInteger('admission_payment_id');
            $table->string('roll')->nullable();
            $table->string('name')->nullable();
            $table->string('board')->nullable();
            $table->year('passing_year')->nullable();
            $table->string('admission_roll')->nullable();
            $table->string('unique_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_configs');
    }
};
