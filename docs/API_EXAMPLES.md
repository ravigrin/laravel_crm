# API Примеры запросов и ответов

## Базовый URL

```
http://localhost:8000/api
```

Все запросы должны содержать заголовок:
```
Content-Type: application/json
Accept: application/vnd.api+json
```

---

## Leads API (JSON:API)

### 1. Создание лида

**Запрос:**
```http
POST /api/v1/leads
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "leads",
    "attributes": {
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+1234567890",
      "data": {
        "answers2": [
          {
            "q": "What is your name?",
            "a": "John Doe"
          },
          {
            "q": "What is your email?",
            "a": "john@example.com"
          }
        ]
      },
      "utm_source": "google",
      "utm_medium": "cpc",
      "utm_campaign": "test_campaign",
      "messengers": {
        "telegram": "@johndoe",
        "whatsapp": "+1234567890"
      }
    },
    "relationships": {
      "user": {
        "data": {
          "type": "users",
          "id": "1"
        }
      }
    }
  },
  "meta": {
    "delay_sec": 0
  }
}
```

**Ответ (201 Created):**
```json
{
  "data": {
    "type": "leads",
    "id": "123",
    "attributes": {
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+1234567890",
      "data": {
        "answers2": [
          {
            "q": "What is your name?",
            "a": "John Doe"
          },
          {
            "q": "What is your email?",
            "a": "john@example.com"
          }
        ]
      },
      "utm_source": "google",
      "utm_medium": "cpc",
      "utm_campaign": "test_campaign",
      "status": 1,
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-15T10:30:00.000000Z"
    },
    "relationships": {
      "user": {
        "data": {
          "type": "users",
          "id": "1"
        }
      }
    }
  }
}
```

---

### 2. Получение лида по ID

**Запрос:**
```http
GET /api/v1/leads/123
Accept: application/vnd.api+json
```

**Ответ (200 OK):**
```json
{
  "data": {
    "type": "leads",
    "id": "123",
    "attributes": {
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+1234567890",
      "status": 1,
      "created_at": "2024-01-15T10:30:00.000000Z"
    }
  }
}
```

---

### 3. Список лидов

**Запрос:**
```http
GET /api/v1/leads?page[number]=1&page[size]=20
Accept: application/vnd.api+json
```

**Ответ (200 OK):**
```json
{
  "data": [
    {
      "type": "leads",
      "id": "123",
      "attributes": {
        "name": "John Doe",
        "email": "john@example.com",
        "status": 1
      }
    },
    {
      "type": "leads",
      "id": "124",
      "attributes": {
        "name": "Jane Smith",
        "email": "jane@example.com",
        "status": 1
      }
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/v1/leads?page[number]=1",
    "last": "http://localhost:8000/api/v1/leads?page[number]=10",
    "prev": null,
    "next": "http://localhost:8000/api/v1/leads?page[number]=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 20,
    "to": 20,
    "total": 200
  }
}
```

---

### 4. Обновление лида

**Запрос:**
```http
PATCH /api/v1/leads/123
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "leads",
    "id": "123",
    "attributes": {
      "name": "John Updated",
      "email": "john.updated@example.com"
    }
  }
}
```

**Ответ (200 OK):**
```json
{
  "data": {
    "type": "leads",
    "id": "123",
    "attributes": {
      "name": "John Updated",
      "email": "john.updated@example.com",
      "updated_at": "2024-01-15T11:00:00.000000Z"
    }
  }
}
```

---

### 5. Удаление лида

**Запрос:**
```http
DELETE /api/v1/leads/123
```

**Ответ (204 No Content):**
```
(пустое тело)
```

---

### 6. Повторная отправка лида во все интеграции

**Запрос:**
```http
POST /api/v1/leads/123/resend
Content-Type: application/json

{}
```

**Ответ (200 OK):**
```json
{
  "success": true,
  "message": "Lead resend queued to all configured integrations",
  "data": {
    "lead_id": 123,
    "method": "all_integrations"
  }
}
```

---

### 7. Повторная отправка лида в конкретные интеграции

**Запрос:**
```http
POST /api/v1/leads/123/resend
Content-Type: application/json

{
  "integration_types": ["email", "amocrm"],
  "credentials": {
    "email": {
      "addresses": ["admin@example.com"]
    },
    "amocrm": {
      "access_token": "token",
      "base_url": "https://example.amocrm.ru"
    }
  }
}
```

**Ответ (200 OK):**
```json
{
  "success": true,
  "message": "Lead resend queued to specified integrations",
  "data": {
    "lead_id": 123,
    "integration_types": ["email", "amocrm"],
    "integrations_count": 2
  }
}
```

---

### 8. Массовая повторная отправка лидов

**Запрос:**
```http
POST /api/v1/leads/bulk-resend
Content-Type: application/json

{
  "lead_ids": [123, 124, 125],
  "integration_types": ["email"],
  "credentials": {
    "email": {
      "addresses": ["admin@example.com"]
    }
  }
}
```

**Ответ (200 OK):**
```json
{
  "success": true,
  "message": "Successfully queued resend for 3 lead(s)",
  "data": {
    "dispatched_count": 3,
    "total_count": 3,
    "errors_count": 0,
    "integration_types": ["email"],
    "errors": []
  }
}
```

**Ответ с ошибками:**
```json
{
  "success": true,
  "message": "Successfully queued resend for 2 lead(s)",
  "data": {
    "dispatched_count": 2,
    "total_count": 3,
    "errors_count": 1,
    "integration_types": ["email"],
    "errors": [
      {
        "lead_id": 125,
        "error": "Lead not found"
      }
    ]
  }
}
```

---

### 9. Получение данных для Kanban доски

**Запрос:**
```http
GET /api/v1/leads/kanban
Accept: application/json
```

**Ответ (200 OK):**
```json
{
  "data": [
    {
      "status": 1,
      "status_name": "New",
      "leads": [
        {
          "id": 123,
          "name": "John Doe",
          "email": "john@example.com",
          "phone": "+1234567890",
          "created_at": "2024-01-15T10:30:00.000000Z"
        }
      ]
    },
    {
      "status": 2,
      "status_name": "In Progress",
      "leads": []
    }
  ]
}
```

---

### 10. Получение счетчиков для фильтров

**Запрос:**
```http
GET /api/v1/leads/filter-counts
Accept: application/json
```

**Ответ (200 OK):**
```json
{
  "data": {
    "cities": [
      {
        "city": "Moscow",
        "count": 50
      },
      {
        "city": "Saint Petersburg",
        "count": 30
      }
    ],
    "quizzes": [
      {
        "quiz_id": 1,
        "count": 100
      },
      {
        "quiz_id": 2,
        "count": 80
      }
    ],
    "statuses": [
      {
        "status": 1,
        "count": 40
      },
      {
        "status": 2,
        "count": 60
      }
    ]
  }
}
```

---

## Integrations API

### 1. Получение списка доступных типов интеграций

**Запрос:**
```http
GET /api/integrations/types
Accept: application/json
```

**Ответ (200 OK):**
```json
{
  "success": true,
  "data": {
    "types": [
      "email",
      "amocrm",
      "telegram",
      "bitrix24",
      "webhooks",
      "retailcrm",
      "getresponse",
      "sendpulse",
      "unisender",
      "lptracker",
      "uontravel"
    ]
  }
}
```

---

### 2. Тестирование подключения к интеграции

**Запрос:**
```http
POST /api/integrations/test
Content-Type: application/json

{
  "type": "email",
  "credentials": {
    "addresses": ["admin@example.com"]
  }
}
```

**Ответ (200 OK):**
```json
{
  "success": true,
  "data": {
    "connected": true,
    "message": "Connection successful"
  }
}
```

**Ответ при ошибке (400 Bad Request):**
```json
{
  "success": false,
  "message": "Invalid credentials",
  "errors": {
    "credentials": ["The addresses field is required."]
  }
}
```

---

### 3. Отправка лида в интеграцию

**Запрос:**
```http
POST /api/integrations/send
Content-Type: application/json

{
  "lead_id": 123,
  "type": "email",
  "credentials": {
    "addresses": ["admin@example.com", "manager@example.com"],
    "template_id": "new_lead",
    "subject": "New Lead Notification"
  }
}
```

**Ответ (200 OK):**
```json
{
  "success": true,
  "data": {
    "external_id": "ext_123456",
    "message": "Lead sent successfully"
  }
}
```

---

### 4. Обновление лида в интеграции

**Запрос:**
```http
POST /api/integrations/update
Content-Type: application/json

{
  "lead_id": 123,
  "type": "amocrm",
  "credentials": {
    "access_token": "token",
    "base_url": "https://example.amocrm.ru"
  }
}
```

**Ответ (200 OK):**
```json
{
  "success": true,
  "data": {
    "external_id": "ext_123456",
    "message": "Lead updated successfully"
  }
}
```

---

### 5. Получение конфигурации интеграции

**Запрос:**
```http
GET /api/integrations/email/config
Accept: application/json
```

**Ответ (200 OK):**
```json
{
  "success": true,
  "data": {
    "type": "email",
    "required_fields": [
      "addresses"
    ],
    "fields": {
      "name": {
        "type": "attr",
        "key": "name"
      },
      "email": {
        "type": "complex",
        "key": "email"
      }
    }
  }
}
```

---

### 6. Batch отправка лидов

**Запрос:**
```http
POST /api/integrations/send-batch
Content-Type: application/json

{
  "lead_ids": [123, 124, 125],
  "type": "email",
  "credentials": {
    "addresses": ["admin@example.com"]
  }
}
```

**Ответ (200 OK):**
```json
{
  "success": true,
  "data": {
    "batch_id": "batch_123",
    "total_leads": 3,
    "queued_leads": 3,
    "message": "Batch job queued successfully"
  }
}
```

---

## Примеры ошибок

### Валидация ошибок (422 Unprocessable Entity)

```json
{
  "success": false,
  "errors": {
    "email": [
      "The email field must be a valid email address."
    ],
    "integration_types.0": [
      "The selected integration_types.0 is invalid."
    ]
  }
}
```

### Ошибка "Lead not found" (404 Not Found)

```json
{
  "success": false,
  "message": "Lead not found"
}
```

### Ошибка сервера (500 Internal Server Error)

```json
{
  "success": false,
  "message": "Failed to resend lead",
  "error": "Database connection error"
}
```

---

## Примеры использования с cURL

### Создание лида
```bash
curl -X POST http://localhost:8000/api/v1/leads \
  -H "Content-Type: application/vnd.api+json" \
  -H "Accept: application/vnd.api+json" \
  -d '{
    "data": {
      "type": "leads",
      "attributes": {
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+1234567890"
      }
    }
  }'
```

### Повторная отправка лида
```bash
curl -X POST http://localhost:8000/api/v1/leads/123/resend \
  -H "Content-Type: application/json" \
  -d '{
    "integration_types": ["email", "amocrm"]
  }'
```

### Тестирование интеграции
```bash
curl -X POST http://localhost:8000/api/integrations/test \
  -H "Content-Type: application/json" \
  -d '{
    "type": "email",
    "credentials": {
      "addresses": ["admin@example.com"]
    }
  }'
```

---

## Примеры использования с Postman

1. **Создайте коллекцию** "Leads API"
2. **Настройте переменные:**
   - `base_url`: `http://localhost:8000/api`
   - `lead_id`: `123`

3. **Создайте запросы:**
   - `POST {{base_url}}/v1/leads` - Создание лида
   - `GET {{base_url}}/v1/leads/{{lead_id}}` - Получение лида
   - `POST {{base_url}}/v1/leads/{{lead_id}}/resend` - Повторная отправка

---

## Примеры использования с JavaScript (Fetch API)

```javascript
// Создание лида
const createLead = async () => {
  const response = await fetch('http://localhost:8000/api/v1/leads', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/vnd.api+json',
      'Accept': 'application/vnd.api+json'
    },
    body: JSON.stringify({
      data: {
        type: 'leads',
        attributes: {
          name: 'John Doe',
          email: 'john@example.com',
          phone: '+1234567890'
        }
      }
    })
  });
  
  const data = await response.json();
  console.log(data);
};

// Повторная отправка лида
const resendLead = async (leadId) => {
  const response = await fetch(`http://localhost:8000/api/v1/leads/${leadId}/resend`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      integration_types: ['email', 'amocrm']
    })
  });
  
  const data = await response.json();
  console.log(data);
};
```

