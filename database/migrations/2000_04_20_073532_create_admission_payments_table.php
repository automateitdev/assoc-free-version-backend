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

            $table->unsignedBigInteger('class_id');
            $table->string('class_name');

            // $table->string('shift');
            $table->unsignedBigInteger('center_id');
            $table->string('center_name');

            // $table->string('group');
            $table->unsignedBigInteger('institute_id');
            $table->string('institute_name');

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
