# Batch Integration System

Система для отправки одной заявки в несколько интеграций с использованием Laravel Jobs и Batch.

## Особенности

- **Отдельные очереди** для каждой интеграции
- **Batch processing** с поддержкой частичных успехов (`allowFailures`)
- **Callbacks** для нотификаций (`then`/`catch`/`finally`)
- **Автоматическое обновление статуса** заявки
- **Детальное логирование** всех операций

## Структура

### Jobs

1. **SendLeadToMultipleIntegrationsJob** - основной Job для отправки в несколько интеграций
2. **BaseIntegrationJob** - базовый класс для всех интеграций
3. **Конкретные Job'ы** для каждой интеграции:
   - `EmailIntegrationJob`
   - `AmoCrmIntegrationJob`
   - `TelegramIntegrationJob`
   - `Bitrix24IntegrationJob`
   - `WebhookIntegrationJob`
4. **BatchNotificationJob** - обработка нотификаций

### Очереди

- `integration-email` - для email уведомлений
- `integration-amocrm` - для AmoCRM
- `integration-telegram` - для Telegram
- `integration-bitrix24` - для Bitrix24
- `integration-webhooks` - для webhooks
- `notifications` - для системных уведомлений

## Использование

### API Endpoint

```http
POST /api/integrations/send-batch
Content-Type: application/json

{
    "lead_id": 123,
    "integrations": [
        {
            "type": "amocrm",
            "settings": {
                "priority": "high"
            }
        },
        {
            "type": "email",
            "settings": {
                "template": "new_lead"
            }
        },
        {
            "type": "telegram",
            "settings": {
                "chat_id": "@notifications"
            }
        }
    ],
    "credentials": {
        "amocrm": {
            "subdomain": "example",
            "client_id": "your_client_id",
            "client_secret": "your_client_secret",
            "access_token": "your_access_token"
        },
        "email": {
            "emails": ["admin@example.com", "notifications@example.com"],
            "template": "new_lead",
            "subject": "New Lead: {lead_name}"
        },
        "telegram": {
            "bot_token": "your_bot_token",
            "chat_id": "@notifications"
        }
    }
}
```

### Программное использование

```php
use App\Jobs\Integrations\SendLeadToMultipleIntegrationsJob;

// Отправка заявки в несколько интеграций
$integrations = [
    ['type' => 'amocrm', 'settings' => ['priority' => 'high']],
    ['type' => 'email', 'settings' => ['template' => 'new_lead']],
    ['type' => 'telegram', 'settings' => ['chat_id' => '@notifications']]
];

$credentials = [
    'amocrm' => [
        'subdomain' => 'example',
        'client_id' => 'your_client_id',
        'client_secret' => 'your_client_secret',
        'access_token' => 'your_access_token'
    ],
    'email' => [
        'emails' => ['admin@example.com'],
        'template' => 'new_lead'
    ],
    'telegram' => [
        'bot_token' => 'your_bot_token',
        'chat_id' => '@notifications'
    ]
];

SendLeadToMultipleIntegrationsJob::dispatch(123, $integrations, $credentials);
```

## Callbacks

### then() - Успешное завершение
Вызывается когда все Job'ы в batch завершились (успешно или с ошибкой).

```php
->then(function (Batch $batch) {
    // Обработка успешного завершения
    // Отправка уведомления о результатах
})
```

### catch() - Ошибка batch
Вызывается при критической ошибке batch.

```php
->catch(function (Batch $batch, \Throwable $e) {
    // Обработка критических ошибок
    // Отправка уведомления об ошибке
})
```

### finally() - Всегда вызывается
Вызывается в любом случае после завершения batch.

```php
->finally(function (Batch $batch) {
    // Обновление статуса заявки
    // Финальное логирование
})
```

## Статусы заявки

- `completed` - все интеграции успешно выполнены
- `partial` - часть интеграций выполнена успешно
- `failed` - все интеграции завершились с ошибкой

## Мониторинг

### Логи

Все операции логируются с детальной информацией:

```php
Log::info('Batch dispatched successfully', [
    'lead_id' => $this->leadId,
    'batch_id' => $batch->id,
    'jobs_count' => count($jobs)
]);
```

### Нотификации

Система отправляет email уведомления о:
- Успешном завершении batch
- Частичном успехе
- Полном провале
- Критических ошибках

## Настройка очередей

В `config/queue.php` настроены отдельные очереди для каждой интеграции:

```php
'integration-email' => [
    'driver' => 'redis',
    'queue' => 'integration-email',
    // ...
],
```

## Запуск воркеров

```bash
# Запуск воркера для всех интеграций
php artisan queue:work redis --queue=integration-email,integration-amocrm,integration-telegram,integration-bitrix24,integration-webhooks,notifications

# Или отдельно для каждой очереди
php artisan queue:work redis --queue=integration-email
php artisan queue:work redis --queue=integration-amocrm
```

## Мониторинг через Horizon

Если используется Laravel Horizon, можно настроить мониторинг очередей:

```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['integration-email', 'integration-amocrm', 'notifications'],
            'balance' => 'simple',
            'processes' => 10,
            'tries' => 3,
            'timeout' => 300,
        ],
    ],
],
```













