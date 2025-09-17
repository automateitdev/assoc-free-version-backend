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
        Schema::create('hr_personal_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hr_info_id');
            $table->string('father_name')->nullable();
            $table->string('father_name_bangla')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_name_bangla')->nullable();
            $table->enum('marital_status', GlobalConstant::MERITAL_STATUS)->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('spouse_name_bangla')->nullable();
            $table->integer('no_of_child')->nullable();
            $table->string('nationality')->nullable();
            $table->string('nid_no')->nullable();
            $table->string('passport_no')->nullable();
            $table->string('tin_no')->nullable();
            $table->string('mpo_id')->nullable();
            $table->string('index_no')->nullable();
            $table->string('language')->nullable();
            $table->string('extra_curriculam')->nullable();
            $table->string('specialization')->nullable();
            $table->string('nid_attachment')->nullable();
            $table->string('passport_attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_personal_infos');
    }
};
