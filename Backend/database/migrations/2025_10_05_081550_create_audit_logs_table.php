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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50)->index(); // auth, crud, file, security, etc.
            $table->string('event', 100)->index(); // login, logout, create, update, etc.
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->json('data'); // Event-specific data
            $table->string('ip_address', 45)->index(); // IPv4 and IPv6 support
            $table->text('user_agent')->nullable();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('method', 10)->nullable(); // HTTP method
            $table->text('url')->nullable(); // Request URL
            $table->json('headers')->nullable(); // Sanitized request headers
            $table->integer('status_code')->nullable()->index(); // HTTP status code
            $table->float('response_time')->nullable(); // Response time in milliseconds
            $table->bigInteger('memory_usage')->nullable(); // Memory usage in bytes
            $table->bigInteger('peak_memory')->nullable(); // Peak memory usage in bytes
            $table->string('level', 20)->default('info')->index(); // Log level
            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at');

            // Foreign key constraint
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');

            // Composite indexes for common queries
            $table->index(['category', 'event']);
            $table->index(['admin_id', 'created_at']);
            $table->index(['category', 'created_at']);
            $table->index(['level', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
