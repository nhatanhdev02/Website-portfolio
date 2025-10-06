<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Starting database seeding...');

        // Core seeders - always run
        $this->call([
            AdminSeeder::class,
            ContentSeeder::class,
        ]);

        // Test data seeder - only runs in local/testing environments
        $this->call([
            TestDataSeeder::class,
        ]);

        $this->command->info('Database seeding completed!');
    }
}
