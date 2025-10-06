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
        Schema::create('about', function (Blueprint $table) {
            $table->id();
            $table->text('content_vi');
            $table->text('content_en');
            $table->string('profile_image')->nullable();
            $table->json('skills')->nullable();
            $table->json('experience')->nullable();
            $table->string('resume_url')->nullable();
            $table->timestamps();

            // Index for performance
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about');
    }
};
