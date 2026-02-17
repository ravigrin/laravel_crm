# Batch Integration Architecture

## Общая схема

```
┌─────────────────────────────────────────────────────────────────┐
│                        API Controller                           │
│  POST /api/integrations/send-batch                             │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│              SendLeadToMultipleIntegrationsJob                  │
│  • Валидация данных                                            │
│  • Создание Batch с Job'ами для каждой интеграции              │
│  • Настройка callbacks (then/catch/finally)                    │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│                        Laravel Batch                           │
│  • allowFailures = true (частичные успехи)                     │
│  • then() - успешное завершение                                │
│  • catch() - критические ошибки                                │
│  • finally() - всегда вызывается                               │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Individual Integration Jobs                  │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐│
│  │    Email    │ │   AmoCRM    │ │  Telegram   │ │  Bitrix24   ││
│  │Integration  │ │Integration  │ │Integration  │ │Integration  ││
│  │    Job      │ │    Job      │ │    Job      │ │    Job      ││
│  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘│
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Separate Queues                             │
│  integration-email  integration-amocrm  integration-telegram   │
│  integration-bitrix24  integration-webhooks  notifications     │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Queue Workers                                │
│  • Отдельные воркеры для каждой очереди                        │
│  • Параллельная обработка                                      │
│  • Retry механизм                                              │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Callbacks & Notifications                   │
│  • BatchNotificationJob - обработка уведомлений                │
│  • Обновление статуса заявки                                   │
│  • Email уведомления о результатах                             │
└─────────────────────────────────────────────────────────────────┘
```

## Поток данных

### 1. Инициация
```
API Request → Controller → SendLeadToMultipleIntegrationsJob
```

### 2. Batch Creation
```
SendLeadToMultipleIntegrationsJob → Laravel Batch → Individual Jobs
```

### 3. Job Execution
```
Individual Jobs → Separate Queues → Queue Workers → Integration Services
```

### 4. Callbacks
```
Batch Completion → Callbacks → Notifications → Status Update
```

## Компоненты

### Jobs

1. **SendLeadToMultipleIntegrationsJob**
   - Основной Job для batch обработки
   - Создает и настраивает Laravel Batch
   - Обрабатывает callbacks

2. **BaseIntegrationJob**
   - Базовый класс для всех интеграций
   - Общая логика валидации и обработки
   - Настройка очередей

3. **Specific Integration Jobs**
   - EmailIntegrationJob
   - AmoCrmIntegrationJob
   - TelegramIntegrationJob
   - Bitrix24IntegrationJob
   - WebhookIntegrationJob

4. **BatchNotificationJob**
   - Обработка уведомлений о результатах batch
   - Отправка email уведомлений

### Queues

- `integration-email` - Email уведомления
- `integration-amocrm` - AmoCRM интеграция
- `integration-telegram` - Telegram интеграция
- `integration-bitrix24` - Bitrix24 интеграция
- `integration-webhooks` - Webhooks
- `notifications` - Системные уведомления

### Callbacks

1. **then()** - Успешное завершение
   - Все Job'ы завершились (успешно или с ошибкой)
   - Отправка уведомления о результатах

2. **catch()** - Критическая ошибка
   - Ошибка в самом batch
   - Отправка уведомления об ошибке

3. **finally()** - Всегда вызывается
   - Обновление статуса заявки
   - Финальное логирование

## Преимущества

1. **Масштабируемость** - отдельные очереди для каждой интеграции
2. **Надежность** - allowFailures для частичных успехов
3. **Мониторинг** - детальные логи и уведомления
4. **Гибкость** - легко добавлять новые интеграции
5. **Производительность** - параллельная обработка

## Мониторинг

- Логи всех операций
- Email уведомления о результатах
- Статусы заявок (completed/partial/failed)
- Метрики через Laravel Horizon (опционально)













