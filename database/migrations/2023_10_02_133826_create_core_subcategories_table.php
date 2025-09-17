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
        Schema::create('core_subcategories', function (Blueprint $table) {
            $table->id();
            $table->unique(array('core_category_id', 'core_subcategory_name'), 'coresub_combination');
            $table->integer('core_category_id');
            $table->string('core_subcategory_name', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core_subcategories');
    }
};
