# Валидация интеграций через FormRequest

## Обзор

Добавлена комплексная система валидации для интеграций через Laravel FormRequest. Это обеспечивает централизованную валидацию credentials и типизированные ошибки.

## Структура валидации

### 1. Базовый FormRequest

#### `BaseIntegrationRequest`
Абстрактный базовый класс для всех интеграционных запросов:

```php
abstract class BaseIntegrationRequest extends FormRequest
{
    abstract public function rules(): array;
    
    public function getIntegrationType(): string;
    public function getCredentials(): array;
    public function getLeadId(): ?int;
    
    protected function validateIntegrationSpecificRules($validator): void;
}
```

### 2. Специализированные FormRequest

#### `TestConnectionRequest`
Валидация для тестирования подключения:
```php
// Правила валидации
'type' => 'required|string|in:email,amocrm,telegram,...',
'credentials' => 'required|array',
'credentials.*' => 'required',

// Специфичная валидация для каждого типа интеграции
```

#### `SendLeadRequest`
Валидация для отправки лидов:
```php
// Наследует от TestConnectionRequest + дополнительные правила
'lead_id' => 'required|integer|exists:leads,id',

// Проверка наличия необходимых данных в лиде
// для конкретного типа интеграции
```

#### `UpdateLeadRequest`
Валидация для обновления лидов:
```php
// Наследует от SendLeadRequest + дополнительные правила
'external_id' => 'required|string',

// Проверка поддержки обновлений для типа интеграции
```

#### `GetConfigRequest`
Валидация для получения конфигурации:
```php
'type' => 'required|string',
// Проверка поддержки типа интеграции
```

### 3. Специфичные FormRequest для интеграций

#### `EmailIntegrationRequest`
```php
'rules' => [
    'type' => 'required|string|in:email',
    'credentials' => 'required|array',
    'credentials.emails' => 'required|array|min:1',
    'credentials.emails.*' => 'required|email|max:255',
    'credentials.template_id' => 'nullable|string|max:255',
    'credentials.subject' => 'nullable|string|max:255',
]
```

#### `AmoCrmIntegrationRequest`
```php
'rules' => [
    'type' => 'required|string|in:amocrm',
    'credentials' => 'required|array',
    'credentials.access_token' => 'required|string|min:10',
    'credentials.base_url' => 'required|url',
    'credentials.responsible_user_id' => 'required|integer|min:1',
    'credentials.pipeline_id' => 'nullable|integer|min:1',
    'credentials.status_id' => 'nullable|integer|min:1',
    'credentials.price' => 'nullable|numeric|min:0',
]
```

#### `TelegramIntegrationRequest`
```php
'rules' => [
    'type' => 'required|string|in:telegram',
    'credentials' => 'required|array',
    'credentials.bot_token' => 'required|string|min:10',
    'credentials.chats' => 'required|array|min:1',
    'credentials.chats.*' => 'required|array',
    'credentials.chats.*.id' => 'required|integer',
    'credentials.chats.*.name' => 'nullable|string|max:255',
    'credentials.parse_mode' => 'nullable|string|in:HTML,Markdown',
]
```

#### `Bitrix24IntegrationRequest`
```php
'rules' => [
    'type' => 'required|string|in:bitrix24',
    'credentials' => 'required|array',
    'credentials.webhook_url' => 'required|url',
    'credentials.user_id' => 'required|integer|min:1',
    'credentials.pipeline_id' => 'nullable|integer|min:1',
    'credentials.status_id' => 'nullable|integer|min:1',
    'credentials.stage_id' => 'nullable|integer|min:1',
]
```

## API Endpoints

### 1. Тестирование подключения
```http
POST /api/integrations/test
Content-Type: application/json

{
    "type": "amocrm",
    "credentials": {
        "access_token": "token",
        "base_url": "https://example.amocrm.ru",
        "responsible_user_id": 123
    }
}
```

**Ответ при ошибке валидации:**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "credentials.access_token": ["Access token is required"],
        "credentials.base_url": ["Base URL must be a valid URL"]
    }
}
```

### 2. Отправка лида
```http
POST /api/integrations/send
Content-Type: application/json

{
    "lead_id": 123,
    "type": "email",
    "credentials": {
        "emails": ["admin@example.com", "notifications@example.com"]
    }
}
```

### 3. Обновление лида
```http
POST /api/integrations/update
Content-Type: application/json

{
    "lead_id": 123,
    "external_id": "ext_123",
    "type": "amocrm",
    "credentials": {
        "access_token": "token",
        "base_url": "https://example.amocrm.ru"
    }
}
```

### 4. Получение конфигурации
```http
GET /api/integrations/email/config
```

**Ответ:**
```json
{
    "success": true,
    "data": {
        "type": "email",
        "required_fields": ["emails"],
        "supported_operations": {
            "send": true,
            "update": false,
            "test_connection": true
        }
    }
}
```

### 5. Получение FormRequest правил
```http
GET /api/integrations/amocrm/form-request
```

**Ответ:**
```json
{
    "success": true,
    "data": {
        "type": "amocrm",
        "form_request_class": "App\\Http\\Requests\\Integration\\AmoCrmIntegrationRequest",
        "validation_rules": {
            "type": "required|string|in:amocrm",
            "credentials": "required|array",
            "credentials.access_token": "required|string|min:10",
            // ...
        },
        "messages": {
            "credentials.access_token.required": "Access token is required",
            // ...
        },
        "attributes": {
            "credentials": "integration credentials",
            // ...
        }
    }
}
```

## Валидация в интеграциях

### Двухуровневая валидация

#### 1. FormRequest валидация (уровень API)
- Проверка структуры данных
- Валидация типов и форматов
- Проверка обязательных полей

#### 2. Integration валидация (уровень сервиса)
```php
// В BaseIntegration
public function validateCredentials(array $credentials): bool
{
    $requiredFields = $this->getRequiredFields();
    
    foreach ($requiredFields as $field) {
        if (!array_key_exists($field, $credentials)) {
            return false;
        }
        
        // Дополнительная валидация значений
        if (!$this->validateFieldValue($field, $credentials[$field])) {
            return false;
        }
    }

    return true;
}

// В конкретных интеграциях
protected function validateFieldValue(string $field, $value): bool
{
    switch ($field) {
        case 'access_token':
            return is_string($value) && strlen($value) >= 10;
        case 'base_url':
            return filter_var($value, FILTER_VALIDATE_URL) !== false;
        // ...
    }
}
```

## Кастомные сообщения об ошибках

### Глобальные сообщения
```php
public function messages(): array
{
    return [
        'credentials.required' => 'Integration credentials are required',
        'credentials.array' => 'Credentials must be an array',
        'type.required' => 'Integration type is required',
        'type.in' => 'Integration type is not supported',
    ];
}
```

### Специфичные сообщения
```php
public function messages(): array
{
    return [
        'credentials.access_token.required' => 'Access token is required',
        'credentials.access_token.min' => 'Access token must be at least 10 characters',
        'credentials.base_url.url' => 'Base URL must be a valid URL',
        'credentials.emails.*.email' => 'Invalid email format',
    ];
}
```

## Кастомные атрибуты

```php
public function attributes(): array
{
    return [
        'credentials' => 'integration credentials',
        'type' => 'integration type',
        'credentials.access_token' => 'access token',
        'credentials.emails' => 'email addresses',
    ];
}
```

## Дополнительная валидация

### Валидация лидов
```php
protected function validateIntegrationSpecificRules($validator): void
{
    $leadId = $this->getLeadId();
    if ($leadId) {
        $lead = Lead::find($leadId);
        
        $validator->after(function ($validator) use ($lead) {
            switch ($this->getIntegrationType()) {
                case 'email':
                    if (empty($lead->email)) {
                        $validator->errors()->add('lead_id', 'Lead must have an email address');
                    }
                    break;
                case 'amocrm':
                    if (empty($lead->name) && empty($lead->email) && empty($lead->phone)) {
                        $validator->errors()->add('lead_id', 'Lead must have at least name, email, or phone');
                    }
                    break;
            }
        });
    }
}
```

### Валидация операций
```php
protected function validateIntegrationSpecificRules($validator): void
{
    $validator->after(function ($validator) {
        $type = $this->getIntegrationType();
        
        // Проверка поддержки обновлений
        if (in_array($type, ['email', 'webhooks'])) {
            $validator->errors()->add('type', "Integration type '{$type}' does not support update operations");
        }
    });
}
```

## Преимущества

### 1. **Централизованная валидация**
- Все правила валидации в одном месте
- Легко поддерживать и обновлять
- Консистентность между API и сервисами

### 2. **Типизированные ошибки**
- Четкие сообщения об ошибках
- Локализация поддержка
- Кастомные атрибуты для полей

### 3. **Многоуровневая валидация**
- FormRequest для API уровня
- Integration валидация для бизнес-логики
- Дополнительные проверки для конкретных случаев

### 4. **Автоматическая генерация документации**
- API для получения правил валидации
- Динамическое получение конфигурации
- Интеграция с фронтендом

### 5. **Расширяемость**
- Легко добавлять новые типы интеграций
- Наследование и переопределение правил
- Кастомная валидация для специфичных случаев

## Использование в контроллерах

```php
public function testConnection(TestConnectionRequest $request): JsonResponse
{
    // Данные уже валидированы FormRequest
    $type = $request->getIntegrationType();
    $credentials = $request->getCredentials();
    
    // Дополнительная валидация на уровне интеграции
    $this->integrationManager->setIntegrationByType($type);
    $result = $this->integrationManager->testConnection($credentials);
    
    return response()->json([
        'success' => $result->isSuccess(),
        'message' => $result->getMessage()
    ]);
}
```

## Интеграция с фронтендом

Фронтенд может получить правила валидации:

```javascript
// Получить правила валидации для AmoCRM
fetch('/api/integrations/amocrm/form-request')
    .then(response => response.json())
    .then(data => {
        const rules = data.data.validation_rules;
        const messages = data.data.messages;
        
        // Использовать для валидации на фронтенде
        validateForm(formData, rules, messages);
    });
```
