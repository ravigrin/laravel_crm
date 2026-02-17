<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // this is test data
        DB::table('email_templates')->updateOrInsert(
            ['template_id' => '17649533'],
            [
                'locale_code' => 'RU',
                'template_code' => 'new_lead',
            ]
        );
    }
}


