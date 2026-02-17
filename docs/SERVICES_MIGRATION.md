# Миграция от хелперов к сервисам

## Обзор изменений

Проект был рефакторен для перехода от статических хелперов к современной сервисной архитектуре с использованием интерфейсов и dependency injection.

## Новые сервисы

### 1. MailService
**Интерфейс:** `App\Interfaces\MailServiceInterface`  
**Реализация:** `App\Services\MailService`

```php
// Использование
$mailService = app(MailServiceInterface::class);
$mailService->send('user@example.com', 'template-id', $data);

// Или через dependency injection
public function __construct(MailServiceInterface $mailService)
{
    $this->mailService = $mailService;
}
```

**Преимущества:**
- Типизированный интерфейс
- Логирование отправки
- Обработка ошибок
- Конфигурация через config/services.php

### 2. HttpService
**Интерфейс:** `App\Interfaces\HttpServiceInterface`  
**Реализация:** `App\Services\HttpService`

```php
// Использование
$httpService = app(HttpServiceInterface::class, ['baseUrl' => 'https://api.example.com']);
$response = $httpService->post('/endpoint', $data, $headers);
$body = $httpService->getResponseBody($response);
```

**Преимущества:**
- Конфигурируемые таймауты и ретраи
- Логирование запросов
- Типизированные методы

### 3. CrmService
**Интерфейс:** `App\Interfaces\CrmServiceInterface`  
**Реализация:** `App\Services\CrmService`

```php
// Использование
$crmService = app(CrmServiceInterface::class);
$email = $crmService->getEmailByProjectId('project-123');
$credentials = $crmService->getProjectCredentials('project-123', 'email');
```

**Преимущества:**
- Кэширование запросов
- Разделение entity и project логики
- Методы для очистки кэша

### 4. LocaleService
**Интерфейс:** `App\Interfaces\LocaleServiceInterface`  
**Реализация:** `App\Services\LocaleService`

```php
// Использование
$localeService = app(LocaleServiceInterface::class);
$template = $localeService->getEmailTemplate('new_lead', 'RU');
$translation = $localeService->translate('messages.welcome', [], 'EN');
```

**Преимущества:**
- Кэширование шаблонов
- Поддержка множественных локалей
- Методы для управления кэшем

## Интеграции

### Обновленная архитектура интеграций

```php
// Создание интеграции
$integration = app(IntegrationInterface::class, ['type' => 'email']);

// Использование через IntegrationManager
$manager = app(IntegrationManager::class);
$manager->setIntegration($integration);
$result = $manager->send(['lead' => $lead, 'credentials' => $credentials]);
```

### Новые Job для интеграций

```php
// DispatchLeadBatch - батчевая обработка
DispatchLeadBatch::dispatch($leadId, $quizId, [
    EmailIntegrationJob::class,
    AmoCrmIntegrationJob::class,
]);

// Отдельные Job для каждой интеграции
EmailIntegrationJob::dispatch($leadId);
```

## Конфигурация

### services.php
```php
'postmark' => [
    'secret' => env('POSTMARK_SECRET'),
    'from' => env('POSTMARK_FROM', 'robot@marquiz.ru'),
],
```

### http.php
```php
'timeout' => env('HTTP_TIMEOUT', 30),
'connect_timeout' => env('HTTP_CONNECT_TIMEOUT', 10),
'retry' => [
    'times' => env('HTTP_RETRY_TIMES', 3),
    'sleep_ms' => env('HTTP_RETRY_SLEEP_MS', 1000),
],
```

## Миграция существующего кода

### Было (хелперы):
```php
use App\Helpers\MailClient;
use App\Helpers\Locale;

$mailClient = new MailClient();
$template = Locale::getEmailTemplate('new_lead');
$mailClient->send($email, $template, $data);
```

### Стало (сервисы):
```php
use App\Interfaces\MailServiceInterface;
use App\Interfaces\LocaleServiceInterface;

public function __construct(
    MailServiceInterface $mailService,
    LocaleServiceInterface $localeService
) {
    $this->mailService = $mailService;
    $this->localeService = $localeService;
}

$template = $this->localeService->getEmailTemplate('new_lead');
$this->mailService->send($email, $template->template_id, $data);
```

## Преимущества новой архитектуры

1. **Тестируемость** - легко мокать интерфейсы в тестах
2. **Типизация** - строгие типы и интерфейсы
3. **Кэширование** - встроенное кэширование в сервисах
4. **Логирование** - централизованное логирование
5. **Конфигурация** - все настройки в config файлах
6. **Dependency Injection** - автоматическое внедрение зависимостей
7. **Расширяемость** - легко добавлять новые реализации

## Следующие шаги

1. **Удалить старые хелперы** после полной миграции
2. **Обновить все контроллеры** для использования новых сервисов
3. **Создать тесты** для новых сервисов
4. **Добавить мониторинг** и метрики
5. **Создать документацию API** для новых эндпоинтов

## Примеры использования

### В контроллере:
```php
class LeadController extends Controller
{
    public function __construct(
        private MailServiceInterface $mailService,
        private CrmServiceInterface $crmService
    ) {}

    public function store(Request $request)
    {
        $lead = Lead::create($request->validated());
        
        $email = $this->crmService->getEmailByProjectId($lead->project_id);
        if ($email) {
            $this->mailService->send($email, 'new_lead', $lead->toArray());
        }
    }
}
```

### В Job:
```php
class ProcessLeadJob implements ShouldQueue
{
    public function handle(
        MailServiceInterface $mailService,
        CrmServiceInterface $crmService
    ) {
        $email = $crmService->getEmailByProjectId($this->projectId);
        $mailService->send($email, 'template', $this->data);
    }
}
```
