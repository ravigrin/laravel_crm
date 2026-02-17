<?php

namespace Database\Factories;

use App\Models\Integration\Credentials;
use Illuminate\Database\Eloquent\Factories\Factory;

class CredentialsFactory extends Factory
{
    protected $model = Credentials::class;

    public function definition(): array
    {
        return [
            'code' => 'email',
            'enabled' => true,
            'credentials' => [
                'addresses' => ['admin@example.com']
            ],
            'hash' => $this->faker->sha256(),
        ];
    }

    public function email(): static
    {
        return $this->state([
            'code' => 'email',
            'credentials' => [
                'addresses' => ['admin@example.com', 'manager@example.com']
            ]
        ]);
    }

    public function amocrm(): static
    {
        return $this->state([
            'code' => 'amocrm',
            'credentials' => [
                'access_token' => 'test_access_token',
                'base_url' => 'https://test.amocrm.ru',
                'responsible_user_id' => 123,
                'pipeline_id' => 456,
                'status_id' => 789
            ]
        ]);
    }

    public function telegram(): static
    {
        return $this->state([
            'code' => 'telegram',
            'credentials' => [
                'bot_token' => '1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                'chats' => [
                    ['id' => -1001234567890, 'name' => 'Test Chat']
                ]
            ]
        ]);
    }

    public function bitrix24(): static
    {
        return $this->state([
            'code' => 'bitrix24',
            'credentials' => [
                'webhook_url' => 'https://test.bitrix24.ru/rest/123/abc/',
                'user_id' => 123
            ]
        ]);
    }

    public function webhooks(): static
    {
        return $this->state([
            'code' => 'webhooks',
            'credentials' => [
                'url' => 'https://webhook.example.com/endpoint'
            ]
        ]);
    }

    public function disabled(): static
    {
        return $this->state([
            'enabled' => false
        ]);
    }
}



