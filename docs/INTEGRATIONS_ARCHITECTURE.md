# Архитектура интеграций

## Обзор

Интеграции были полностью переписаны с использованием современной сервисной архитектуры Laravel. Все интеграции теперь используют единый интерфейс `IntegrationChannelInterface` и работают через `IntegrationManager`.

## Ключевые компоненты

### 1. Интерфейсы

#### `IntegrationChannelInterface`
Основной интерфейс для всех интеграций:
```php
interface IntegrationChannelInterface
{
    public function send(Lead $lead, array $credentials): IntegrationResult;
    public function update(Lead $lead, array $credentials): IntegrationResult;
    public function validateCredentials(array $credentials): bool;
    public function getType(): string;
    public function getRequiredFields(): array;
    public function testConnection(array $credentials): IntegrationResult;
}
```

#### `IntegrationResult`
Типизированный результат операций:
```php
class IntegrationResult
{
    public function isSuccess(): bool;
    public function getMessage(): ?string;
    public function getExternalId(): ?string;
    public function getData(): array;
    public function getHttpCode(): ?int;
}
```

### 2. Базовый класс

#### `BaseIntegration`
Абстрактный базовый класс для всех интеграций:
- Обработка ошибок и логирование
- Валидация credentials
- Маппинг данных через FieldMapperService
- Единый интерфейс для send/update/test

### 3. Сервисы

#### `FieldMapperService`
Сервис для маппинга данных лидов в формат интеграций:
- Поддержка различных типов полей (attr, data, trans, complex, etc.)
- Конфигурируемые маппинги через config/integrations.php
- Поддержка вложенных структур

#### `IntegrationFactory`
Фабрика для создания интеграций:
```php
$integration = $factory->create('amocrm', ['base_url' => 'https://example.amocrm.ru']);
```

#### `IntegrationManager`
Менеджер для работы с интеграциями:
```php
$manager->setIntegrationByType('email');
$result = $manager->send($lead, $credentials);
```

## Реализованные интеграции

### 1. Email Integration
- **Тип**: `email`
- **Поля**: name, email, phone, data, utm_*
- **Требования**: emails (массив email адресов)

### 2. AmoCRM Integration
- **Тип**: `amocrm`
- **Поля**: name, price, phone, email, utm_*
- **Требования**: access_token, base_url, responsible_user_id

### 3. Telegram Integration
- **Тип**: `telegram`
- **Поля**: title, contacts, answers, link
- **Требования**: bot_token, chats

### 4. Bitrix24 Integration
- **Тип**: `bitrix24`
- **Поля**: TITLE, NAME, EMAIL, PHONE, COMMENTS
- **Требования**: webhook_url, user_id

### 5. Webhooks Integration
- **Тип**: `webhooks`
- **Поля**: name, email, phone, data
- **Требования**: url

### 6. Остальные интеграции (заглушки)
- GetResponse, SendPulse, UniSender, UonTravel, LpTracker

## Конфигурация

### config/integrations.php
```php
'amocrm' => [
    'base_url' => env('AMOCRM_BASE_URL', 'https://example.amocrm.ru'),
    'required_fields' => ['access_token', 'base_url', 'responsible_user_id'],
    'fields' => [
        'name' => ['type' => 'attr', 'key' => 'name'],
        'price' => ['type' => 'const', 'value' => 0],
        'phone' => ['type' => 'complex', 'key' => 'phone'],
        // ...
    ]
]
```

## Использование

### В контроллере
```php
public function sendLead(Request $request)
{
    $lead = Lead::findOrFail($request->lead_id);
    
    $this->integrationManager->setIntegrationByType('amocrm');
    $result = $this->integrationManager->send($lead, $credentials);
    
    if ($result->isSuccess()) {
        // Успешно отправлено
        $externalId = $result->getExternalId();
    }
}
```

### В Job
```php
public function handle(IntegrationChannelInterface $integration)
{
    $lead = Lead::findOrFail($this->leadId);
    $result = $integration->send($lead, $this->credentials);
    
    if ($result->isSuccess()) {
        Log::info('Integration successful', [
            'external_id' => $result->getExternalId()
        ]);
    }
}
```

### Прямое создание интеграции
```php
$integration = app(IntegrationChannelInterface::class, [
    'type' => 'amocrm',
    'config' => ['base_url' => 'https://example.amocrm.ru']
]);

$result = $integration->send($lead, $credentials);
```

## API Endpoints

### GET /api/integrations/types
Получить список доступных типов интеграций

### POST /api/integrations/test
Тестировать подключение к интеграции
```json
{
    "type": "amocrm",
    "credentials": {
        "access_token": "token",
        "base_url": "https://example.amocrm.ru"
    }
}
```

### POST /api/integrations/send
Отправить лид в интеграцию
```json
{
    "lead_id": 123,
    "type": "amocrm",
    "credentials": { ... }
}
```

### GET /api/integrations/{type}/config
Получить конфигурацию интеграции

## Маппинг полей

### Типы полей
- `attr` - атрибут модели Lead
- `data` - данные из поля data
- `trans` - перевод через LocaleService
- `const` - константное значение
- `date` - форматированная дата
- `answers_text` - ответы в текстовом формате
- `answers_html` - ответы в HTML формате
- `complex` - сложная структура (массивы)
- `dynamic` - динамическое значение с шаблоном

### Примеры маппинга
```php
'fields' => [
    'name' => ['type' => 'attr', 'key' => 'name'],
    'email' => ['type' => 'complex', 'key' => 'email'],
    'title' => ['type' => 'trans', 'key' => 'lead.title', 'locale' => 'RU'],
    'price' => ['type' => 'const', 'value' => 0],
    'created_at' => ['type' => 'date', 'key' => 'created_at'],
    'answers' => ['type' => 'answers_html'],
    'link' => ['type' => 'dynamic', 'key' => 'id', 'template' => '/leads/{value}'],
]
```

## Преимущества новой архитектуры

### 1. **Единообразие**
- Все интеграции используют один интерфейс
- Стандартизированные методы send/update/test
- Единый формат результатов

### 2. **Типизация**
- Строгие типы для всех методов
- Типизированные результаты операций
- Валидация на уровне интерфейса

### 3. **Конфигурируемость**
- Маппинг полей через конфигурацию
- Гибкая настройка для каждой интеграции
- Легкое добавление новых полей

### 4. **Тестируемость**
- Легко мокать интерфейсы в тестах
- Изолированное тестирование каждой интеграции
- Встроенные методы для тестирования подключения

### 5. **Расширяемость**
- Легко добавлять новые интеграции
- Фабричный паттерн для создания
- Плагинная архитектура

### 6. **Надежность**
- Централизованная обработка ошибок
- Логирование всех операций
- Retry механизмы через Jobs

### 7. **Производительность**
- Кэширование конфигураций
- Батчевая обработка через Jobs
- Асинхронная отправка

## Jobs для интеграций

### AutoIntegrationJob
Автоматически определяет все доступные интеграции для лида и отправляет их через batch обработку:

```php
use App\Jobs\Integrations\AutoIntegrationJob;

// Отправить лид во все доступные интеграции
AutoIntegrationJob::dispatch($leadId);
```

**Особенности:**
- Автоматически находит все enabled интеграции из `EntityCredentials` и `ProjectCredentials`
- Поддерживает только реализованные интеграции (email, amocrm, telegram, bitrix24, webhooks)
- Использует `SendLeadToMultipleIntegrationsJob` для batch обработки

**Подробнее:** [AUTO_INTEGRATION_JOB_USAGE.md](./AUTO_INTEGRATION_JOB_USAGE.md)

### SendLeadToMultipleIntegrationsJob
Отправляет лид в несколько указанных интеграций параллельно:

```php
use App\Jobs\Integrations\SendLeadToMultipleIntegrationsJob;

$integrations = [
    ['type' => 'email', 'settings' => []],
    ['type' => 'amocrm', 'settings' => []],
];

$credentials = [
    'email' => ['addresses' => ['test@example.com']],
    'amocrm' => ['access_token' => '...', 'base_url' => '...'],
];

SendLeadToMultipleIntegrationsJob::dispatch($leadId, $integrations, $credentials);
```

**Подробнее:** [BATCH_INTEGRATION_USAGE.md](./BATCH_INTEGRATION_USAGE.md)

### BaseIntegrationJob
Базовый класс для конкретных интеграций (EmailIntegrationJob, AmoCrmIntegrationJob и т.д.):

```php
// Используется автоматически через SendLeadToMultipleIntegrationsJob
// Не требует прямого использования
```

## Сервисы для работы с интеграциями

### LeadResendService
Централизованный сервис для повторной отправки лидов в интеграции:

```php
use App\Services\Lead\LeadResendService;

$resendService = app(LeadResendService::class);

// Отправить во все доступные интеграции (использует AutoIntegrationJob)
$result = $resendService->resendLead($leadId);

// Отправить в конкретные интеграции
$result = $resendService->resendLead($leadId, ['email', 'amocrm']);

// Batch отправка нескольких лидов
$result = $resendService->bulkResendLeads([1, 2, 3], ['email']);
```

**Преимущества:**
- Единообразный API для всех resend операций
- Валидация типов интеграций
- Централизованная обработка ошибок
- Логирование всех операций

## Миграция с старой архитектуры

### Было (NotificationSenders):
```php
// Старый подход через IoC container
$integration = app()->make('amocrm', ['lead' => $lead, 'credentials' => $credentials]);
$integration->send();

// Или через Notification job
dispatch(new Notification($leadId));
```

### Стало (Services + Jobs):
```php
// Новый подход через IntegrationManager
$manager->setIntegrationByType('amocrm');
$result = $manager->send($lead, $credentials);

// Или через AutoIntegrationJob (автоматическое определение)
AutoIntegrationJob::dispatch($leadId);

// Или через LeadResendService (рекомендуется)
$resendService = app(LeadResendService::class);
$result = $resendService->resendLead($leadId);
```

## Примеры использования новой архитектуры

### Пример 1: Отправка при создании лида

```php
use App\Jobs\Integrations\AutoIntegrationJob;
use App\Models\Lead;

public function store(Request $request)
{
    $lead = Lead::create($request->validated());
    
    // Отправить во все доступные интеграции
    AutoIntegrationJob::dispatch($lead->id);
    
    return response()->json($lead, 201);
}
```

### Пример 2: Повторная отправка через API

```php
use App\Services\Lead\LeadResendService;

public function resendLead(Request $request, int $leadId)
{
    $resendService = app(LeadResendService::class);
    
    $integrationTypes = $request->input('integration_types', []);
    $credentials = $request->input('credentials', []);
    
    $result = $resendService->resendLead($leadId, $integrationTypes, $credentials);
    
    return response()->json($result);
}
```

### Пример 3: Отправка в конкретные интеграции

```php
use App\Jobs\Integrations\SendLeadToMultipleIntegrationsJob;

$integrations = [
    ['type' => 'email', 'settings' => []],
    ['type' => 'amocrm', 'settings' => []],
];

$credentials = [
    'email' => ['addresses' => ['admin@example.com']],
    'amocrm' => [
        'access_token' => 'token',
        'base_url' => 'https://example.amocrm.ru',
        'responsible_user_id' => 123
    ],
];

SendLeadToMultipleIntegrationsJob::dispatch($leadId, $integrations, $credentials);
```

### Пример 4: Использование IntegrationManager напрямую

```php
use App\Services\Integration\IntegrationManager;

$manager = app(IntegrationManager::class);
$manager->setIntegrationByType('amocrm');

$result = $manager->send($lead, $credentials);

if ($result->isSuccess()) {
    $externalId = $result->getExternalId();
    Log::info('Lead sent successfully', ['external_id' => $externalId]);
} else {
    Log::error('Failed to send lead', ['error' => $result->getMessage()]);
}
```

### Пример 5: Тестирование подключения

```php
use App\Services\Integration\IntegrationManager;

$manager = app(IntegrationManager::class);
$manager->setIntegrationByType('amocrm');

$result = $manager->testConnection($credentials);

if ($result->isSuccess()) {
    return response()->json(['status' => 'connected']);
} else {
    return response()->json(['error' => $result->getMessage()], 400);
}
```

### Пример 6: Batch обработка нескольких лидов

```php
use App\Services\Lead\LeadResendService;

$leadIds = [1, 2, 3, 4, 5];
$resendService = app(LeadResendService::class);

// Отправить все лиды во все их доступные интеграции
$result = $resendService->bulkResendLeads($leadIds, []);

// Результат содержит:
// - dispatched_count: количество успешно отправленных
// - total_count: общее количество
// - errors_count: количество ошибок
// - errors: массив ошибок с деталями
```

## Полный поток данных

```
1. Lead создается
   ↓
2. AutoIntegrationJob::dispatch($leadId)
   ↓
3. AutoIntegrationJob определяет все доступные интеграции из credentials
   ↓
4. SendLeadToMultipleIntegrationsJob::dispatch($leadId, $integrations, $credentials)
   ↓
5. Batch обработка: создаются отдельные jobs для каждой интеграции
   ↓
6. BaseIntegrationJob (EmailIntegrationJob, AmoCrmIntegrationJob, etc.)
   ↓
7. IntegrationManager получает конкретную интеграцию
   ↓
8. IntegrationChannelInterface::send($lead, $credentials)
   ↓
9. IntegrationResult возвращается
   ↓
10. Обработка результата (логирование, обновление lead, уведомления)
```

## Связанная документация

- [AUTO_INTEGRATION_JOB_USAGE.md](./AUTO_INTEGRATION_JOB_USAGE.md) - Подробное руководство по AutoIntegrationJob
- [BATCH_INTEGRATION_USAGE.md](./BATCH_INTEGRATION_USAGE.md) - Использование batch интеграций
- [BATCH_INTEGRATION_ARCHITECTURE.md](./BATCH_INTEGRATION_ARCHITECTURE.md) - Архитектура batch обработки
- [REDUNDANT_CODE_ANALYSIS.md](./REDUNDANT_CODE_ANALYSIS.md) - Анализ миграции со старой архитектуры

