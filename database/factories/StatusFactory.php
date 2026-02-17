<?php

namespace Database\Factories;

use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatusFactory extends Factory
{
    protected $model = Status::class;

    public function definition(): array
    {
        // Генерируем короткий код (максимум 64 символа)
        $code = substr($this->faker->unique()->slug(3), 0, 64);
        
        return [
            'user_id' => $this->faker->numberBetween(1, 10),
            'project_id' => $this->faker->numberBetween(1, 10),
            'code' => $code,
            'name' => substr($this->faker->words(2, true), 0, 128),
            'order' => $this->faker->numberBetween(0, 100),
            'color' => $this->faker->hexColor(),
            'is_default' => false,
        ];
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

    public function default(): static
    {
        return $this->state([
            'is_default' => true,
        ]);
    }

    public function withOrder(int $order): static
    {
        return $this->state([
            'order' => $order,
        ]);
    }

    public function withColor(string $color): static
    {
        return $this->state([
            'color' => $color,
        ]);
    }
}

