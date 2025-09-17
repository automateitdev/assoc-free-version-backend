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
        Schema::create('open_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_details_id');
            $table->unsignedBigInteger('rule_id');
            $table->timestamp('start_date_time');
            $table->timestamp('end_date_time')->nullable();
            $table->json('amount')->nullable();
            $table->enum('file', GlobalConstant::FILE);
            $table->enum('status', GlobalConstant::OPEN_STATUS);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_histories');
    }
};
