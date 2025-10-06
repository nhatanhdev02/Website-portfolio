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
        // Add composite indexes for better query performance
        Schema::table('projects', function (Blueprint $table) {
            // Composite index for category and featured filtering
            $table->index(['category', 'featured', 'order'], 'projects_category_featured_order_idx');
            // Index for updated_at for cache invalidation queries
            $table->index('updated_at');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            // Composite index for status and published date ordering
            $table->index(['status', 'published_at', 'created_at'], 'blog_posts_status_published_created_idx');
            // Index for updated_at for cache invalidation queries
            $table->index('updated_at');
        });

        Schema::table('services', function (Blueprint $table) {
            // Composite index for ordering and timestamps
            $table->index(['order', 'updated_at'], 'services_order_updated_idx');
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            // Composite index for read status and date filtering
            $table->index(['read_at', 'created_at'], 'contact_messages_read_created_idx');
            // Index for bulk operations
            $table->index(['created_at', 'read_at'], 'contact_messages_created_read_idx');
        });

        Schema::table('admins', function (Blueprint $table) {
            // Index for authentication queries
            $table->index('username');
            $table->index('last_login_at');
        });

        Schema::table('heroes', function (Blueprint $table) {
            // Index for cache invalidation queries
            $table->index('updated_at');
        });

        Schema::table('about', function (Blueprint $table) {
            // Index for cache invalidation queries
            $table->index('updated_at');
        });

        Schema::table('system_settings', function (Blueprint $table) {
            // Index for settings key lookups
            $table->index('key');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_category_featured_order_idx');
            $table->dropIndex(['updated_at']);
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex('blog_posts_status_published_created_idx');
            $table->dropIndex(['updated_at']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('services_order_updated_idx');
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex('contact_messages_read_created_idx');
            $table->dropIndex('contact_messages_created_read_idx');
        });

        Schema::table('admins', function (Blueprint $table) {
            $table->dropIndex(['username']);
            $table->dropIndex(['last_login_at']);
        });

        Schema::table('heroes', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('about', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropIndex(['key']);
            $table->dropIndex(['updated_at']);
        });
    }
};
