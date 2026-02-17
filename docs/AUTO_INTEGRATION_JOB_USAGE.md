# AutoIntegrationJob - Руководство по использованию

## Обзор

`AutoIntegrationJob` - это job, который автоматически определяет все доступные интеграции для лида на основе его `EntityCredentials` и `ProjectCredentials`, и отправляет лид во все найденные интеграции через новую архитектуру.

### Основные возможности

- ✅ Автоматическое определение интеграций из credentials
- ✅ Поддержка batch обработки через `SendLeadToMultipleIntegrationsJob`
- ✅ Использование новой архитектуры интеграций (`IntegrationChannelInterface`)
- ✅ Поддержка только реализованных интеграций (email, amocrm, telegram, bitrix24, webhooks)

---

## Когда использовать

### ✅ Используйте AutoIntegrationJob когда:

1. **Нужно отправить лид во все доступные интеграции**
   - При создании нового лида
   - При повторной отправке без указания конкретных интеграций
   - Когда нужно автоматически определить, куда отправлять лид

2. **Неизвестно, какие интеграции настроены**
   - Job автоматически найдет все enabled интеграции для entity/project

3. **Нужна простая отправка без дополнительной конфигурации**
   - Job сам определит интеграции и credentials

### ❌ НЕ используйте AutoIntegrationJob когда:

1. **Нужно отправить в конкретные интеграции**
   - Используйте `SendLeadToMultipleIntegrationsJob` напрямую

2. **Нужны кастомные credentials**
   - Используйте `SendLeadToMultipleIntegrationsJob` с явным указанием credentials

3. **Нужны специфичные настройки для интеграций**
   - Используйте `SendLeadToMultipleIntegrationsJob` с настройками

---

## Использование

### Базовое использование

```php
use App\Jobs\Integrations\AutoIntegrationJob;

// Отправить лид во все доступные интеграции
AutoIntegrationJob::dispatch($leadId);

// С задержкой
AutoIntegrationJob::dispatch($leadId)->delay(now()->addSeconds(30));
```

### Использование через LeadResendService (рекомендуется)

```php
use App\Services\Lead\LeadResendService;

$resendService = app(LeadResendService::class);

// Отправить во все интеграции
$result = $resendService->resendLead($leadId);

// Отправить в конкретные интеграции (не использует AutoIntegrationJob)
$result = $resendService->resendLead($leadId, ['email', 'amocrm']);
```

### Использование в контроллере

```php
use App\Jobs\Integrations\AutoIntegrationJob;

public function createLead(Request $request)
{
    $lead = Lead::create($request->validated());
    
    // Отправить во все доступные интеграции
    AutoIntegrationJob::dispatch($lead->id);
    
    return response()->json($lead);
}
```

---

## Как это работает

### 1. Определение интеграций

Job автоматически находит все enabled интеграции для лида:

```php
// Получает credentials из EntityCredentials
$entityCredentials = EntityCredentials::where('external_entity_id', $lead->external_entity_id)
    ->with(['credentials' => function ($query) {
        $query->where('enabled', true);
    }])
    ->get();

// Получает credentials из ProjectCredentials
$projectCredentials = ProjectCredentials::where('external_project_id', $lead->external_project_id)
    ->with(['credentials' => function ($query) {
        $query->where('enabled', true);
    }])
    ->get();
```

### 2. Фильтрация поддерживаемых интеграций

Job поддерживает только интеграции, реализованные в новой архитектуре:

- ✅ `email` - EmailIntegrationJob
- ✅ `amocrm` - AmoCrmIntegrationJob
- ✅ `telegram` - TelegramIntegrationJob
- ✅ `bitrix24` - Bitrix24IntegrationJob
- ✅ `webhooks` - WebhookIntegrationJob

Интеграции, которые еще не реализованы (getresponse, sendpulse, unisender, lptracker, uontravel), будут пропущены.

### 3. Отправка через SendLeadToMultipleIntegrationsJob

После определения интеграций, job создает batch через `SendLeadToMultipleIntegrationsJob`:

```php
$integrations = [
    ['type' => 'email', 'settings' => []],
    ['type' => 'amocrm', 'settings' => []],
    // ...
];

$credentialsMap = [
    'email' => [...],
    'amocrm' => [...],
    // ...
];

SendLeadToMultipleIntegrationsJob::dispatch($leadId, $integrations, $credentialsMap);
```

---

## Примеры использования

### Пример 1: Отправка при создании лида

```php
use App\Jobs\Integrations\AutoIntegrationJob;
use App\Models\Lead;

public function store(Request $request)
{
    $lead = Lead::create($request->validated());
    
    // Отправить во все доступные интеграции с задержкой
    $delay = $request->input('delay_sec', 0);
    AutoIntegrationJob::dispatch($lead->id)->delay(now()->addSeconds($delay));
    
    return response()->json($lead, 201);
}
```

### Пример 2: Повторная отправка через API

```php
// GET /api/leads/{id}/resend
// Body: {} (пустой - отправит во все интеграции)

public function resendLead(Request $request, int $leadId)
{
    $resendService = app(LeadResendService::class);
    
    // Если integration_types не указаны, использует AutoIntegrationJob
    $result = $resendService->resendLead($leadId, []);
    
    return response()->json($result);
}
```

### Пример 3: Использование в событии

```php
use App\Events\LeadCreated;
use App\Jobs\Integrations\AutoIntegrationJob;

class LeadCreatedListener
{
    public function handle(LeadCreated $event)
    {
        // Отправить лид во все доступные интеграции
        AutoIntegrationJob::dispatch($event->lead->id);
    }
}
```

### Пример 4: Batch обработка нескольких лидов

```php
use App\Services\Lead\LeadResendService;

$leadIds = [1, 2, 3, 4, 5];
$resendService = app(LeadResendService::class);

// Отправить все лиды во все их доступные интеграции
$result = $resendService->bulkResendLeads($leadIds, []);

// Результат:
// [
//     'dispatched_count' => 5,
//     'total_count' => 5,
//     'errors_count' => 0,
//     'errors' => []
// ]
```

---

## Логирование

Job логирует все этапы работы:

```php
// Начало работы
Log::info('AutoIntegrationJob: Starting automatic integration detection', [
    'lead_id' => $leadId
]);

// Найдены credentials
Log::info('AutoIntegrationJob: Found credentials', [
    'lead_id' => $leadId,
    'credentials_count' => count($credentials)
]);

// Интеграции определены
Log::info('AutoIntegrationJob: Dispatching SendLeadToMultipleIntegrationsJob', [
    'lead_id' => $leadId,
    'integrations_count' => count($integrations),
    'integration_types' => ['email', 'amocrm', 'telegram']
]);

// Предупреждения
Log::warning('AutoIntegrationJob: No credentials found for lead', [
    'lead_id' => $leadId
]);

Log::warning('AutoIntegrationJob: No supported integrations found for lead', [
    'lead_id' => $leadId
]);
```

---

## Обработка ошибок

### Ошибки, которые может выбросить job:

1. **Lead not found**
   ```php
   // Job выбросит ModelNotFoundException
   // Обработайте в контроллере:
   catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
       return response()->json(['error' => 'Lead not found'], 404);
   }
   ```

2. **Нет credentials**
   ```php
   // Job просто вернется без отправки
   // Проверьте логи для деталей
   ```

3. **Нет поддерживаемых интеграций**
   ```php
   // Job просто вернется без отправки
   // Проверьте логи для деталей
   ```

---

## Конфигурация

### Параметры job:

```php
public int $tries = 3;        // Количество попыток
public int $timeout = 300;    // Таймаут в секундах (5 минут)
```

### Настройка очереди:

По умолчанию job использует очередь по умолчанию. Для настройки:

```php
AutoIntegrationJob::dispatch($leadId)
    ->onQueue('integrations'); // Указать очередь
```

---

## Миграция со старого Notification job

### Было (старый код):

```php
use App\Jobs\Notification;

// Старый подход
dispatch(new Notification($leadId));
```

### Стало (новый код):

```php
use App\Jobs\Integrations\AutoIntegrationJob;

// Новый подход
AutoIntegrationJob::dispatch($leadId);
```

### Преимущества нового подхода:

1. ✅ Использует новую архитектуру интеграций
2. ✅ Batch обработка через `SendLeadToMultipleIntegrationsJob`
3. ✅ Лучшая обработка ошибок
4. ✅ Поддержка только реализованных интеграций
5. ✅ Централизованное логирование

---

## Часто задаваемые вопросы

### Q: Почему некоторые интеграции не отправляются?

A: Job поддерживает только интеграции, реализованные в новой архитектуре:
- ✅ email, amocrm, telegram, bitrix24, webhooks
- ❌ getresponse, sendpulse, unisender, lptracker, uontravel (еще не реализованы)

### Q: Как отправить в конкретные интеграции?

A: Используйте `SendLeadToMultipleIntegrationsJob` напрямую или `LeadResendService`:

```php
$resendService->resendLead($leadId, ['email', 'amocrm']);
```

### Q: Можно ли использовать кастомные credentials?

A: Да, используйте `SendLeadToMultipleIntegrationsJob` напрямую:

```php
SendLeadToMultipleIntegrationsJob::dispatch($leadId, $integrations, $customCredentials);
```

### Q: Как проверить, какие интеграции будут использованы?

A: Проверьте логи job или используйте `LeadResendService` с указанием интеграций.

---

## Связанная документация

- [INTEGRATIONS_ARCHITECTURE.md](./INTEGRATIONS_ARCHITECTURE.md) - Общая архитектура интеграций
- [BATCH_INTEGRATION_USAGE.md](./BATCH_INTEGRATION_USAGE.md) - Использование batch интеграций
- [BATCH_INTEGRATION_ARCHITECTURE.md](./BATCH_INTEGRATION_ARCHITECTURE.md) - Архитектура batch обработки



