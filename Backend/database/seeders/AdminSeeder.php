<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user for production/main admin
        \App\Models\Admin::factory()->withCredentials(
            'admin',
            'admin@nhatanhdev.com',
            'admin123'
        )->recentLogin()->create();

        // Create Nhật Anh's personal admin account
        \App\Models\Admin::factory()->withCredentials(
            'nhatanhdev',
            'nhat.anh@nhatanhdev.com',
            'nhatanhdev2024'
        )->recentLogin()->create();

        // Create test admin users for development
        if (app()->environment(['local', 'testing'])) {
            // Admin who has never logged in
            \App\Models\Admin::factory()->withCredentials(
                'testadmin',
                'test@example.com',
                'password123'
            )->neverLoggedIn()->create();

            // Admin with recent login activity
            \App\Models\Admin::factory()->withCredentials(
                'activeadmin',
                'active@example.com',
                'password123'
            )->recentLogin()->create();

            // Additional random test admins
            \App\Models\Admin::factory(3)->create();
        }
    }
}
