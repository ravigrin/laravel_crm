# API Tests для Leads и Statuses

Этот каталог содержит тесты для JSON:API endpoints ресурсов Leads и Statuses.

## Структура

- `LeadApiTest.php` - Тесты для API endpoints лидов
- `StatusApiTest.php` - Тесты для API endpoints статусов
- `ApiExamples.http` - Примеры HTTP запросов для тестирования вручную

## Запуск тестов

### Все тесты
```bash
php artisan test
```

### Только тесты для Leads
```bash
php artisan test tests/Feature/LeadApiTest.php
```

### Только тесты для Statuses
```bash
php artisan test tests/Feature/StatusApiTest.php
```

### Конкретный тест
```bash
php artisan test --filter test_can_create_lead
```

## Использование фикстур

### Factories

#### LeadFactory
```php
use App\Models\Lead;

// Создать обычный лид
$lead = Lead::factory()->create();

// Создать тестовый лид
$lead = Lead::factory()->isTest()->create();

// Создать просмотренный лид
$lead = Lead::factory()->viewed()->create();

// Создать оплаченный лид
$lead = Lead::factory()->paid()->create();

// Создать заблокированный лид
$lead = Lead::factory()->blocked()->create();

// Создать лид для конкретного пользователя
$lead = Lead::factory()->forUser(1)->create();

// Создать лид для конкретного проекта
$lead = Lead::factory()->forProject(1)->create();

// Создать лид для конкретного квиза
$lead = Lead::factory()->forQuiz(1)->create();

// Создать лид с геолокацией
$lead = Lead::factory()->withLocation('Moscow', 'Russia')->create();

// Создать лид с дублем
$firstLead = Lead::factory()->create();
$duplicate = Lead::factory()
    ->withFingerprint('same-fingerprint')
    ->withEqualAnswer($firstLead->id)
    ->create();

// Создать лид со статусом интеграции
$lead = Lead::factory()
    ->withIntegrationStatus('success', ['external_id' => '12345'])
    ->create();
```

#### StatusFactory
```php
use App\Models\Status;

// Создать обычный статус
$status = Status::factory()->create();

// Создать статус для пользователя
$status = Status::factory()->forUser(1)->create();

// Создать статус для проекта
$status = Status::factory()->forProject(1)->create();

// Создать дефолтный статус
$status = Status::factory()->default()->create();

// Создать статус с порядком
$status = Status::factory()->withOrder(5)->create();

// Создать статус с цветом
$status = Status::factory()->withColor('#FF5733')->create();
```

## Использование Seeders

### Запуск seeders
```bash
# Все seeders
php artisan db:seed

# Конкретный seeder
php artisan db:seed --class=LeadSeeder
php artisan db:seed --class=StatusSeeder
```

### Что создают seeders

#### LeadSeeder
- 50 обычных лидов
- 10 тестовых лидов
- 20 просмотренных лидов
- 15 оплаченных лидов
- 5 заблокированных лидов
- 30 лидов из Москвы
- 10 лидов из Санкт-Петербурга
- 10 лидов с успешной интеграцией
- 5 лидов с ошибкой интеграции
- 4 лида с дублями (одинаковый fingerprint)

#### StatusSeeder
- По 5 статусов для каждого из 3 проектов (включая дефолтный)
- 20 дополнительных статусов

## Использование HTTP примеров

Файл `ApiExamples.http` содержит примеры запросов для:
- Получения списка ресурсов
- Фильтрации и сортировки
- Пагинации
- Создания, обновления и удаления ресурсов
- Работы с новыми полями (is_test, viewed, paid, blocked, location, etc.)

### Использование в VS Code

1. Установите расширение [REST Client](https://marketplace.visualstudio.com/items?itemName=humao.rest-client)
2. Откройте файл `ApiExamples.http`
3. Нажмите "Send Request" над каждым запросом

### Использование в других инструментах

Скопируйте запросы из файла в:
- Postman
- Insomnia
- cURL
- Любой другой HTTP клиент

## Примеры тестовых запросов

### Создать лид
```bash
curl -X POST http://localhost:8000/api/v1/leads \
  -H "Accept: application/vnd.api+json" \
  -H "Content-Type: application/vnd.api+json" \
  -d '{
    "data": {
      "type": "leads",
      "attributes": {
        "name": "Test Lead",
        "email": "test@example.com",
        "externalSystem": "example_system",
        "externalEntity": "lead",
        "externalEntityId": "ext-123",
        "status": 1
      }
    }
  }'
```

### Получить список лидов
```bash
curl -X GET "http://localhost:8000/api/v1/leads?page[size]=10" \
  -H "Accept: application/vnd.api+json"
```

### Создать статус
```bash
curl -X POST http://localhost:8000/api/v1/statuses \
  -H "Accept: application/vnd.api+json" \
  -H "Content-Type: application/vnd.api+json" \
  -d '{
    "data": {
      "type": "statuses",
      "attributes": {
        "name": "Новый",
        "code": "new",
        "order": 0,
        "color": "#4CAF50",
        "isDefault": true
      }
    }
  }'
```

## Формат ответов JSON:API

Все ответы соответствуют стандарту JSON:API:

```json
{
  "data": {
    "type": "leads",
    "id": "1",
    "attributes": {
      "name": "John Doe",
      "email": "john@example.com",
      ...
    }
  },
  "links": {
    "self": "/api/v1/leads/1"
  },
  "meta": {
    ...
  }
}
```

Ошибки возвращаются в формате:

```json
{
  "errors": [
    {
      "status": "422",
      "title": "Validation Error",
      "detail": "The email field is required.",
      "source": {
        "pointer": "/data/attributes/email"
      }
    }
  ]
}
```

