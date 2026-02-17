<?php

namespace Database\Factories;

use App\Models\Email;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailFactory extends Factory
{
    protected $model = Email::class;

    public function definition(): array
    {
        return [
            'template_id' => $this->faker->slug(),
            'locale_code' => $this->faker->randomElement(['RU', 'EN', 'ES']),
            'template_code' => $this->faker->slug(),
            'subject' => $this->faker->sentence(),
            'body' => $this->faker->paragraphs(3, true),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function newLead(): static
    {
        return $this->state([
            'template_id' => 'new_lead',
            'template_code' => 'new_lead',
            'subject' => 'New Lead Notification',
            'body' => 'You have received a new lead: {{lead_name}}',
        ]);
    }

    public function leadUpdate(): static
    {
        return $this->state([
            'template_id' => 'lead_update',
            'template_code' => 'lead_update',
            'subject' => 'Lead Updated',
            'body' => 'Lead has been updated: {{lead_name}}',
        ]);
    }

    public function russian(): static
    {
        return $this->state([
            'locale_code' => 'RU',
        ]);
    }

    public function english(): static
    {
        return $this->state([
            'locale_code' => 'EN',
        ]);
    }
}
