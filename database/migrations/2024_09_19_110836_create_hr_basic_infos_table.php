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
        Schema::create('hr_basic_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hr_info_id');
            $table->unsignedBigInteger('institute_details_id');
            $table->string('custom_hr_id')->unique(); 
            $table->string('name');             
            $table->string('name_bangla')->nullable(); 
            $table->enum('gender', GlobalConstant::GENDER);  
            $table->enum('religion', GlobalConstant::RELIGION); 
            $table->date('date_of_birth')->nullable();  
            $table->string('blood_group')->nullable();
            $table->string('mobile_no');       
            $table->string('email')->nullable(); 
            $table->string('photo')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_basic_infos');
    }
};
