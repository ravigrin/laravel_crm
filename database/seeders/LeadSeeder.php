<?php

namespace Database\Seeders;

use App\Enums\DefaultStatuses;
use App\Models\Lead;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем обычные лиды
        Lead::factory()
            ->count(50)
            ->create();

        // Создаем тестовые лиды
        Lead::factory()
            ->count(10)
            ->isTest()
            ->create();

        // Создаем просмотренные лиды
        Lead::factory()
            ->count(20)
            ->viewed()
            ->create();

        // Создаем оплаченные лиды
        Lead::factory()
            ->count(15)
            ->paid()
            ->create();

        // Создаем заблокированные лиды
        Lead::factory()
            ->count(5)
            ->blocked()
            ->create();

        // Создаем лиды с геолокацией
        Lead::factory()
            ->count(30)
            ->withLocation('Moscow', 'Russia')
            ->create();

        Lead::factory()
            ->count(10)
            ->withLocation('Saint Petersburg', 'Russia')
            ->create();

        // Создаем лиды с интеграциями
        Lead::factory()
            ->count(10)
            ->withIntegrationStatus('success', ['external_id' => '12345'])
            ->create();

        Lead::factory()
            ->count(5)
            ->withIntegrationStatus('failed', ['error' => 'Connection timeout'])
            ->create();

        // Создаем лиды с дублями (одинаковый fingerprint)
        $fingerprint = 'test-fingerprint-' . time();
        $firstLead = Lead::factory()
            ->withFingerprint($fingerprint)
            ->create();

        Lead::factory()
            ->count(3)
            ->withFingerprint($fingerprint)
            ->withEqualAnswer($firstLead->id)
            ->create();
    }
}

