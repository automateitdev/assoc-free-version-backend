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
        Schema::create('admission_applieds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_details_id');
            $table->string('unique_number')->unique();
            $table->string('student_name_bangla')->nullable();
            $table->string('student_name_english');
            $table->string('student_mobile')->nullable();
            $table->string('father_name_bangla')->nullable();
            $table->string('father_name_english');
            $table->string('father_nid')->nullable();
            $table->string('father_mobile')->nullable();
            $table->string('mother_name_bangla')->nullable();
            $table->string('mother_name_english');
            $table->string('mother_nid')->nullable();
            $table->string('mother_mobile')->nullable();
            $table->string('nationality');
            $table->date('date_of_birth');
            $table->string('student_nid_or_birth_no');
            $table->string('gender');
            $table->string('religion');
            $table->string('blood_group')->nullable();
            $table->string('merital_status')->nullable();
            $table->string('present_division');
            $table->string('present_district');
            $table->string('present_upozilla');
            $table->string('present_post_office');
            $table->string('present_post_code')->nullable();
            $table->text('present_address');
            $table->string('permanent_division');
            $table->string('permanent_district');
            $table->string('permanent_upozilla');
            $table->string('permanent_post_office');
            $table->string('permanent_post_code')->nullable();
            $table->text('permanent_address');
            $table->string('guardian_name')->nullable();
            $table->string('guardian_relation')->nullable();
            $table->string('guardian_mobile')->nullable();
            $table->string('guardian_occupation')->nullable();
            $table->string('guardian_yearly_income')->nullable();
            $table->string('guardian_property')->nullable();
            $table->string('academic_year');

            $table->unsignedBigInteger('class_id');
            $table->string('class_name');

            $table->unsignedBigInteger('institute_id');
            $table->string('institute_name');

            $table->unsignedBigInteger('center_id');
            $table->string('center_name');

            // $table->string('shift');
            // $table->string('group');
            $table->json('subject');
            $table->json('edu_information')->nullable();
            $table->string('quota')->nullable();
            $table->string('vaccine')->nullable();
            $table->string('vaccine_name')->nullable();
            $table->string('vaccine_certificate')->nullable();
            $table->string('student_pic');
            $table->string('student_birth_nid_file')->nullable();
            $table->string('other_file')->nullable();
            $table->unsignedBigInteger('assigned_roll')->nullable();
            $table->string('approval_status')->default('pending');
            $table->string('status')->nullable();
            $table->date('date')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_applieds');
    }
};
