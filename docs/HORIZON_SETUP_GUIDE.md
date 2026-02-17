# Laravel Horizon Setup Guide

## Обзор

Laravel Horizon предоставляет красивый веб-интерфейс для мониторинга и управления Redis очередями. Этот гайд описывает настройку и использование Horizon в проекте.

## Установка

### 1. Добавление в composer.json

```json
{
    "require": {
        "laravel/horizon": "^5.34"
    }
}
```

### 2. Установка зависимостей

```bash
composer install
```

### 3. Публикация конфигурации

```bash
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
```

## Конфигурация

### Переменные окружения

Добавьте в `.env` файл:

```env
# Horizon Configuration
HORIZON_DOMAIN=
HORIZON_PATH=horizon
HORIZON_USE=default
HORIZON_PREFIX=horizon:
HORIZON_DARK_MODE=false

# Queue Configuration
QUEUE_CONNECTION=redis
REDIS_QUEUE=default

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# Cache Configuration
CACHE_STORE=redis
REDIS_CACHE_CONNECTION=cache
```

### Конфигурация Horizon

Файл `config/horizon.php` уже настроен с оптимальными параметрами:

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'high', 'low'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
        ],
    ],
    'local' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'high', 'low'],
            'balance' => 'simple',
            'processes' => 3,
            'tries' => 3,
            'timeout' => 60,
        ],
    ],
],
```

## Запуск Horizon

### 1. Локальная разработка

```bash
# Запуск Horizon
php artisan horizon

# Запуск в фоновом режиме
php artisan horizon:start-daemon

# Проверка статуса
php artisan horizon:status-check

# Остановка
php artisan horizon:terminate
```

### 2. Production

```bash
# Запуск с supervisor
php artisan horizon

# Или через systemd service
sudo systemctl start horizon
sudo systemctl enable horizon
```

### 3. Docker

```bash
# Запуск с Docker Compose
docker-compose up horizon_worker

# Или запуск всех сервисов
docker-compose up -d
```

## Веб-дашборд

### Доступ к дашборду

После запуска Horizon, дашборд будет доступен по адресу:

```
http://localhost/horizon
```

### Аутентификация

В текущей конфигурации доступ открыт для локальной разработки. Для production добавьте аутентификацию в `HorizonServiceProvider`:

```php
Horizon::auth(function ($request) {
    return $request->user() && $request->user()->isAdmin();
});
```

## Управление очередями

### Создание Job

```php
<?php

namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessLeadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $lead;

    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
    }

    public function handle()
    {
        // Обработка лида
        // Отправка уведомлений
        // Интеграции с CRM
    }
}
```

### Отправка Job в очередь

```php
// В контроллере
ProcessLeadJob::dispatch($lead);

// С задержкой
ProcessLeadJob::dispatch($lead)->delay(now()->addMinutes(5));

// В конкретную очередь
ProcessLeadJob::dispatch($lead)->onQueue('high');

// С приоритетом
ProcessLeadJob::dispatch($lead)->onConnection('redis');
```

## Мониторинг

### Команды мониторинга

```bash
# Статус Horizon
php artisan horizon:status-check

# Очистка истории заданий
php artisan horizon:clear-jobs --failed
php artisan horizon:clear-jobs --all

# Статистика в JSON
php artisan horizon:status-check --json
```

### Метрики

Horizon отслеживает:
- ✅ Количество выполненных заданий
- ❌ Количество неудачных заданий
- ⏱️ Время выполнения
- 💾 Использование памяти
- 🔄 Активность супервизоров

## Настройка окружений

### Local (разработка)

```php
'local' => [
    'supervisor-1' => [
        'connection' => 'redis',
        'queue' => ['default', 'high', 'low'],
        'balance' => 'simple',
        'processes' => 3,
        'tries' => 3,
        'timeout' => 60,
        'sleep' => 3,
        'rest' => 3,
    ],
],
```

### Production

```php
'production' => [
    'supervisor-1' => [
        'connection' => 'redis',
        'queue' => ['default', 'high', 'low'],
        'balance' => 'auto',
        'autoScalingStrategy' => 'time',
        'maxProcesses' => 10,
        'maxTime' => 0,
        'maxJobs' => 0,
        'memory' => 128,
        'tries' => 3,
        'timeout' => 60,
    ],
],
```

## Интеграция с существующими Jobs

### Обновление существующих Jobs

Все существующие Jobs в проекте уже готовы для работы с Horizon:

```php
// app/Jobs/Notification.php
// app/Jobs/NotifyByEmail.php
// app/Jobs/SendLeadStatistic.php
// app/Jobs/Export/*
// app/Jobs/Integrations/*
// app/Jobs/MongoImport/*
```

### Использование в контроллерах

```php
class LeadController extends Controller
{
    public function store(Request $request)
    {
        $lead = Lead::create($request->all());
        
        // Отправка уведомления через Horizon
        Notification::dispatch($lead->id)
            ->delay($request->get('delay_sec', 0));
        
        return response()->json(['message' => 'Lead saved', 'lead' => $lead]);
    }
}
```

## Troubleshooting

### Проблемы с Redis

```bash
# Проверка подключения к Redis
redis-cli ping

# Проверка очередей
redis-cli llen queues:default
redis-cli llen queues:high
redis-cli llen queues:low
```

### Проблемы с процессами

```bash
# Проверка запущенных процессов Horizon
ps aux | grep horizon

# Остановка всех процессов
pkill -f "artisan horizon"
```

### Логи

```bash
# Просмотр логов Horizon
tail -f storage/logs/horizon.log

# Просмотр логов Laravel
tail -f storage/logs/laravel.log
```

## Производительность

### Оптимизация настроек

1. **Memory Limit**: Увеличьте `memory_limit` для тяжелых задач
2. **Processes**: Настройте количество процессов под нагрузку
3. **Timeout**: Установите разумные таймауты для задач
4. **Balance**: Используйте `auto` балансировку для production

### Мониторинг производительности

```php
// В Job добавьте метрики
public function handle()
{
    $start = microtime(true);
    
    // Ваша логика
    
    $duration = microtime(true) - $start;
    Log::info('Job completed', [
        'duration' => $duration,
        'memory' => memory_get_usage(true)
    ]);
}
```

## Безопасность

### Аутентификация дашборда

```php
// app/Providers/HorizonServiceProvider.php
Horizon::auth(function ($request) {
    // Проверка IP
    if (!in_array($request->ip(), ['127.0.0.1', '192.168.1.0/24'])) {
        return false;
    }
    
    // Проверка пользователя
    return $request->user() && $request->user()->hasRole('admin');
});
```

### Ограничение доступа

```php
// Middleware для Horizon
Route::group(['prefix' => 'horizon', 'middleware' => ['web', 'auth:sanctum', 'admin']], function () {
    // Horizon routes
});
```