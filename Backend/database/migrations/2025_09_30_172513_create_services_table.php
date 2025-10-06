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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title_vi');
            $table->string('title_en');
            $table->text('description_vi');
            $table->text('description_en');
            $table->string('icon');
            $table->string('color');
            $table->string('bg_color');
            $table->integer('order')->default(0);
            $table->timestamps();

            // Indexes for performance
            $table->index('order');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
