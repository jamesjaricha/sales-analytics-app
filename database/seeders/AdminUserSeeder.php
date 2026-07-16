<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create default admin user if it doesn't exist
        if (! User::where('email', 'admin@salesanalytics.com')->exists()) {
            User::create([
                'name' => 'System Administrator',
                'email' => 'admin@salesanalytics.com',
                'password' => Hash::make('password123'), // Change this in production!
                'role' => 'admin',
            ]);

            $this->command->info('Admin user created with email: admin@salesanalytics.com');
            $this->command->warn('Default password: password123 - CHANGE THIS IN PRODUCTION!');
        } else {
            $this->command->info('Admin user already exists.');
        }
    }
}
