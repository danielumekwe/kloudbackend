<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Default admin login — change this password via /admin/forgot-password (or
        // directly in the DB) before this app is exposed publicly.
        Admin::firstOrCreate(
            ['email' => 'admin@admin.com'],
            ['password' => md5('12345678')]
        );
    }
}
