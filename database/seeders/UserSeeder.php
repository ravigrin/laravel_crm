<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 users to match what LeadFactory expects (user_id between 1-10)
        User::factory()
            ->count(10)
            ->create();
    }
}



