<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OAuthServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // this is test data
        DB::table('oauth_services')->updateOrInsert(
            ['service' => 'amocrm'],
            [
                'temp_id' => 'test-amocrm',
                'client_id' => 'fc8fec81-d6dd-4a4a-a1e5-d84be5afa335',
                'client_secret' => '173ND8Pn8MH8tIM9tEQrLCjMd4xvhvEBxBC1f2pj14fZAgbJbZoLXw7N6MUiaFvE',
                'redirect_url' => 'https://55ab85769e4d.ngrok.io/api/integrations/amocrm/savetoken',
            ]
        );
    }
}


