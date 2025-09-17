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
        Schema::create('institute_details', function (Blueprint $table) {
            $table->id();
            $table->string('institute_id')->unique();  
            $table->string('institute_name')->nullable();
            $table->bigInteger('institute_ein')->unique()->nullable();
            $table->string('institute_contact')->nullable();
            $table->string('institute_email')->unique()->nullable();
            $table->string('institute_category')->nullable();
            $table->string('institute_type')->nullable();
            $table->string('institute_board')->nullable();
            $table->text('institute_address',300)->nullable();
            $table->string('institute_district',100)->nullable();
            $table->string('institute_sub_distric',100)->nullable();
            $table->string('institute_division',100)->nullable();
            $table->mediumText('logo')->nullable();
            $table->enum('gateway', GlobalConstant::GATEWAY);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institute_details');
    }
};
