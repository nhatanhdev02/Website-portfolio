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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title_vi');
            $table->string('title_en');
            $table->text('description_vi');
            $table->text('description_en');
            $table->string('image');
            $table->string('link')->nullable();
            $table->json('technologies');
            $table->string('category');
            $table->boolean('featured')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();

            // Indexes for performance
            $table->index(['featured', 'order']);
            $table->index('category');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
