<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * Cached user IDs to avoid repeated database queries
     */
    protected static ?array $userIds = null;

    public function definition(): array
    {
        if (self::$userIds === null) {
            self::$userIds = User::pluck('id')->toArray();
        }

        return [
            // Ownership
            'user_id' => !empty(self::$userIds) ? $this->faker->randomElement(self::$userIds) : null,
            'project_id' => $this->faker->numberBetween(1, 10),
            'quiz_id' => $this->faker->numberBetween(1, 10),
            
            // External identification
            'external_id' => $this->faker->unique()->uuid(),
            'external_system' => 'example_system',
            'external_entity' => 'lead',
            'external_entity_id' => $this->faker->uuid(),
            'external_project_id' => $this->faker->uuid(),
            
            // Contact information
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'messengers' => [
                'telegram' => '@' . $this->faker->userName(),
                'whatsapp' => $this->faker->phoneNumber()
            ],
            'contacts' => [
                'address' => $this->faker->address(),
                'company' => $this->faker->company(),
            ],
            
            // Location
            'ip_address' => $this->faker->ipv4(),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            
            // UTM tags
            'utm_source' => $this->faker->randomElement(['google', 'facebook', 'direct', 'yandex']),
            'utm_medium' => $this->faker->randomElement(['cpc', 'organic', 'social', 'email']),
            'utm_campaign' => $this->faker->slug(),
            'utm_content' => $this->faker->word(),
            'utm_term' => $this->faker->word(),
            
            // Lead data
            'data' => [
                'answers2' => [
                    [
                        'q' => 'What is your name?',
                        'a' => $this->faker->name()
                    ],
                    [
                        'q' => 'What is your email?',
                        'a' => $this->faker->email()
                    ]
                ],
                'custom_field' => $this->faker->word()
            ],
            'status' => \App\Enums\DefaultStatuses::New,
            'integration_status' => null,
            'integration_data' => null,
            
            // Lead flags
            'is_test' => false,
            'viewed' => false,
            'paid' => false,
            'blocked' => false,
            
            // Duplicate detection
            'fingerprint' => $this->faker->sha256(),
            'equal_answer_id' => null,
            
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => now(),
        ];
    }

    public function withEmail(): static
    {
        return $this->state([
            'email' => $this->faker->unique()->safeEmail(),
        ]);
    }

    public function withPhone(): static
    {
        return $this->state([
            'phone' => $this->faker->phoneNumber(),
        ]);
    }

    public function withName(): static
    {
        return $this->state([
            'name' => $this->faker->name(),
        ]);
    }

    public function withUtm(): static
    {
        return $this->state([
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'test_campaign',
        ]);
    }

    public function withAnswers(): static
    {
        return $this->state([
            'data' => [
                'answers2' => [
                    [
                        'q' => 'What is your favorite color?',
                        'a' => $this->faker->colorName()
                    ],
                    [
                        'q' => 'How did you hear about us?',
                        'a' => $this->faker->randomElement(['Google', 'Facebook', 'Friend'])
                    ]
                ]
            ]
        ]);
    }

    public function withMessengers(): static
    {
        return $this->state([
            'messengers' => [
                'telegram' => '@' . $this->faker->userName(),
                'whatsapp' => $this->faker->phoneNumber(),
                'viber' => $this->faker->phoneNumber()
            ]
        ]);
    }

    public function withoutEmail(): static
    {
        return $this->state([
            'email' => null,
        ]);
    }

    public function withoutPhone(): static
    {
        return $this->state([
            'phone' => null,
        ]);
    }

    public function withoutName(): static
    {
        return $this->state([
            'name' => null,
        ]);
    }

    public function minimal(): static
    {
        return $this->state([
            'name' => null,
            'email' => null,
            'phone' => null,
            'data' => [],
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'messengers' => [],
        ]);
    }

    public function forUser(int $userId): static
    {
        return $this->state([
            'user_id' => $userId,
        ]);
    }

    public function forProject(int $projectId): static
    {
        return $this->state([
            'project_id' => $projectId,
        ]);
    }

    public function forQuiz(int $quizId): static
    {
        return $this->state([
            'quiz_id' => $quizId,
        ]);
    }

    public function isTest(): static
    {
        return $this->state([
            'is_test' => true,
        ]);
    }

    public function viewed(): static
    {
        return $this->state([
            'viewed' => true,
        ]);
    }

    public function paid(): static
    {
        return $this->state([
            'paid' => true,
        ]);
    }

    public function blocked(): static
    {
        return $this->state([
            'blocked' => true,
        ]);
    }

    public function withFingerprint(string $fingerprint): static
    {
        return $this->state([
            'fingerprint' => $fingerprint,
        ]);
    }

    public function withEqualAnswer(int $equalAnswerId): static
    {
        return $this->state([
            'equal_answer_id' => $equalAnswerId,
        ]);
    }

    public function withContacts(array $contacts): static
    {
        return $this->state([
            'contacts' => $contacts,
        ]);
    }

    public function withIntegrationStatus(string $status, array $data = null): static
    {
        return $this->state([
            'integration_status' => $status,
            'integration_data' => $data,
        ]);
    }

    public function withLocation(string $city = null, string $country = null): static
    {
        return $this->state([
            'city' => $city ?? $this->faker->city(),
            'country' => $country ?? $this->faker->country(),
        ]);
    }
}
