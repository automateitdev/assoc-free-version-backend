<?php

use App\Utils\GlobalConstant;
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
        Schema::create('admission_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_details_id');
            $table->string('academic_year');
            $table->string('class');
            // $table->string('shift');
            $table->string('center');
            // $table->string('group');
            $table->string('institute');
            $table->decimal('amount', 8, 2);
            $table->dateTime('start_date_time');
            $table->dateTime('end_date_time');
            $table->string('roll_start')->nullable();
            $table->enum('exam_enabled', GlobalConstant::YN);
            $table->dateTime('exam_date_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_payments');
    }
};
