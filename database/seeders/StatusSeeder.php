<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Статусы для разных проектов
        $projectIds = [1, 2, 3];

        foreach ($projectIds as $projectId) {
            // Создаем дефолтный статус
            Status::factory()
                ->forProject($projectId)
                ->default()
                ->withOrder(0)
                ->withColor('#4CAF50')
                ->create([
                    'code' => 'new',
                    'name' => 'Новый',
                ]);

            // Создаем остальные статусы
            Status::factory()
                ->forProject($projectId)
                ->withOrder(1)
                ->withColor('#2196F3')
                ->create([
                    'code' => 'in-progress',
                    'name' => 'В работе',
                ]);

            Status::factory()
                ->forProject($projectId)
                ->withOrder(2)
                ->withColor('#FF9800')
                ->create([
                    'code' => 'pending',
                    'name' => 'Ожидает',
                ]);

            Status::factory()
                ->forProject($projectId)
                ->withOrder(3)
                ->withColor('#9C27B0')
                ->create([
                    'code' => 'closed',
                    'name' => 'Закрыт',
                ]);

            Status::factory()
                ->forProject($projectId)
                ->withOrder(4)
                ->withColor('#F44336')
                ->create([
                    'code' => 'cancelled',
                    'name' => 'Отменен',
                ]);
        }

        // Создаем дополнительные статусы
        Status::factory()
            ->count(20)
            ->create();
    }
}

