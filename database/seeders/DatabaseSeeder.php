<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            OAuthServicesSeeder::class,
            EmailTemplatesSeeder::class,
            StatusSeeder::class,
            LeadSeeder::class,
        ]);
    }
}
