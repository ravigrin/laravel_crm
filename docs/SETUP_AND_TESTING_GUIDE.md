# Руководство по настройке и тестированию проекта

## Содержание

1. [Требования](#требования)
2. [Установка](#установка)
3. [Настройка окружения](#настройка-окружения)
4. [Запуск проекта](#запуск-проекта)
5. [Настройка баз данных](#настройка-баз-данных)
6. [Настройка очередей](#настройка-очередей)
7. [Запуск тестов](#запуск-тестов)
8. [Тестирование API](#тестирование-api)
9. [Отладка](#отладка)
10. [Troubleshooting](#troubleshooting)

---

## Требования

### Системные требования

- **PHP**: >= 8.2
- **Composer**: >= 2.0
- **Node.js**: >= 18.x (опционально, для фронтенда)
- **Docker**: >= 20.x (для Docker Compose)
- **Docker Compose**: >= 2.0

### Расширения PHP

- BCMath
- Ctype
- cURL
- DOM
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PCRE
- PDO
- Tokenizer
- XML

### Базы данных

- **PostgreSQL**: >= 13.x (основная БД)
- **Redis**: >= 6.x (кэш и очереди)
- **MongoDB**: >= 4.x (опционально, для импорта данных)

---

## Установка

### 1. Клонирование репозитория

```bash
git clone <repository-url>
cd ref
```

### 2. Установка зависимостей

```bash
composer install
```

### 3. Копирование файла окружения

```bash
cp .env.example .env
```

### 4. Генерация ключа приложения

```bash
php artisan key:generate
```

---

## Настройка окружения

### Файл .env

Откройте файл `.env` и настройте следующие параметры:

```env
# Приложение
APP_NAME="PROJECT_REF_PLACEHOLDER"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Europe/Moscow

# База данных
DB_CONNECTION=pgsql
DB_HOST=postgres_database
DB_PORT=5432
DB_DATABASE=project_ref
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

# Очереди
QUEUE_CONNECTION=redis

# Кэш
CACHE_STORE=redis

# Почта
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Sentry (опционально)
SENTRY_LARAVEL_DSN=
SENTRY_DSN=
```

---

## Запуск проекта

### Вариант 1: Docker Compose (рекомендуется)

1. **Запуск контейнеров:**

```bash
docker-compose up -d
```

2. **Выполнение миграций:**

```bash
docker-compose exec php_fpm php artisan migrate
```

3. **Заполнение тестовыми данными (опционально):**

```bash
docker-compose exec php_fpm php artisan db:seed
```

4. **Доступ к приложению:**

```
http://localhost:8000
```

### Вариант 2: Локальная установка

1. **Настройка веб-сервера:**

Настройте Nginx или Apache для работы с проектом.

2. **Запуск встроенного сервера (для разработки):**

```bash
php artisan serve
```

3. **Доступ к приложению:**

```
http://localhost:8000
```

---

## Настройка баз данных

### PostgreSQL

1. **Создание базы данных:**

```sql
CREATE DATABASE project_ref;
CREATE USER project_user WITH PASSWORD 'password';
GRANT ALL PRIVILEGES ON DATABASE project_ref TO project_user;
```

2. **Выполнение миграций:**

```bash
php artisan migrate
```

3. **Откат миграций (если нужно):**

```bash
php artisan migrate:rollback
```

### Redis

1. **Проверка подключения:**

```bash
redis-cli ping
```

Должен вернуть: `PONG`

2. **Очистка кэша:**

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## Настройка очередей

### Horizon (рекомендуется для production)

1. **Публикация конфигурации:**

```bash
php artisan horizon:install
```

2. **Запуск Horizon:**

```bash
php artisan horizon
```

3. **Доступ к панели Horizon:**

```
http://localhost:8000/horizon
```

### Обычные очереди

1. **Запуск воркера:**

```bash
php artisan queue:work redis --tries=3
```

2. **Запуск воркера в фоне:**

```bash
php artisan queue:work redis --daemon
```

### Мониторинг очередей

```bash
# Просмотр неудачных задач
php artisan queue:failed

# Повтор неудачных задач
php artisan queue:retry all

# Очистка неудачных задач
php artisan queue:flush
```

---

## Запуск тестов

### Настройка тестового окружения

1. **Создание тестовой базы данных:**

В `.env` или `.env.testing`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

2. **Выполнение миграций для тестов:**

```bash
php artisan migrate --env=testing
```

### Запуск всех тестов

```bash
# Все тесты
php artisan test

# Или через PHPUnit
vendor/bin/phpunit
```

### Запуск конкретных тестов

```bash
# Feature тесты
php artisan test --testsuite=Feature

# Integration тесты
php artisan test --testsuite=Integration

# Unit тесты
php artisan test --testsuite=Unit

# Конкретный тест
php artisan test tests/Feature/LeadApiTest.php

# Конкретный метод
php artisan test --filter test_it_can_create_a_lead
```

### Покрытие кода

```bash
php artisan test --coverage
```

### Параллельное выполнение тестов

```bash
php artisan test --parallel
```

---

## Тестирование API

### 1. Использование встроенных тестов

```bash
# Запуск API тестов
php artisan test tests/Feature/LeadApiTest.php
php artisan test tests/Integration/IntegrationApiTest.php
```

### 2. Использование HTTP файлов (REST Client)

Создайте файл `tests/Feature/api-tests.http`:

```http
### Создание лида
POST http://localhost:8000/api/v1/leads
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "leads",
    "attributes": {
      "name": "Test Lead",
      "email": "test@example.com",
      "phone": "+1234567890"
    }
  }
}

### Получение лида
GET http://localhost:8000/api/v1/leads/1

### Повторная отправка лида
POST http://localhost:8000/api/v1/leads/1/resend
Content-Type: application/json

{
  "integration_types": ["email"]
}
```

### 3. Использование Postman

1. **Импорт коллекции:**

Импортируйте файл `docs/postman_collection.json` в Postman.

2. **Настройка переменных:**

- `base_url`: `http://localhost:8000/api`
- `lead_id`: `1`

3. **Запуск запросов:**

Выполняйте запросы из коллекции.

### 4. Использование cURL

```bash
# Создание лида
curl -X POST http://localhost:8000/api/v1/leads \
  -H "Content-Type: application/vnd.api+json" \
  -H "Accept: application/vnd.api+json" \
  -d '{
    "data": {
      "type": "leads",
      "attributes": {
        "name": "Test Lead",
        "email": "test@example.com"
      }
    }
  }'

# Получение лида
curl http://localhost:8000/api/v1/leads/1 \
  -H "Accept: application/vnd.api+json"

# Повторная отправка
curl -X POST http://localhost:8000/api/v1/leads/1/resend \
  -H "Content-Type: application/json" \
  -d '{"integration_types": ["email"]}'
```

### 5. Использование JavaScript (Fetch API)

См. примеры в `docs/API_EXAMPLES.md`.

---

## Отладка

### Логирование

1. **Просмотр логов:**

```bash
# Все логи
tail -f storage/logs/laravel.log

# Логи через Docker
docker-compose exec php_fpm tail -f storage/logs/laravel.log
```

2. **Уровни логирования:**

В `.env`:

```env
LOG_LEVEL=debug
```

### Laravel Debugbar

1. **Установка:**

```bash
composer require barryvdh/laravel-debugbar --dev
```

2. **Доступ:**

Откройте приложение в браузере - панель отладки появится автоматически.

### Tinker

```bash
# Запуск Tinker
php artisan tinker

# Примеры команд
$lead = App\Models\Lead::first();
$lead->name;
$lead->toArray();
```

### Xdebug

1. **Настройка в php.ini:**

```ini
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
```

2. **Использование в IDE:**

Настройте IDE для подключения к Xdebug (порт 9003).

---

## Troubleshooting

### Проблема: Ошибка подключения к базе данных

**Решение:**

1. Проверьте настройки в `.env`:
```env
DB_HOST=postgres_database
DB_PORT=5432
DB_DATABASE=project_ref
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

2. Проверьте, что PostgreSQL запущен:
```bash
docker-compose ps postgres_database
```

3. Проверьте подключение:
```bash
docker-compose exec postgres_database psql -U postgres -d project_ref -c "SELECT 1;"
```

### Проблема: Ошибка подключения к Redis

**Решение:**

1. Проверьте настройки в `.env`:
```env
REDIS_HOST=redis
REDIS_PORT=6379
```

2. Проверьте, что Redis запущен:
```bash
docker-compose ps redis
```

3. Проверьте подключение:
```bash
docker-compose exec redis redis-cli ping
```

### Проблема: Очереди не обрабатываются

**Решение:**

1. Проверьте, что воркер запущен:
```bash
php artisan queue:work redis
```

2. Проверьте настройки очередей в `.env`:
```env
QUEUE_CONNECTION=redis
```

3. Проверьте неудачные задачи:
```bash
php artisan queue:failed
```

### Проблема: Миграции не выполняются

**Решение:**

1. Очистите кэш:
```bash
php artisan cache:clear
php artisan config:clear
```

2. Проверьте права доступа к БД

3. Выполните миграции вручную:
```bash
php artisan migrate --force
```

### Проблема: Тесты не проходят

**Решение:**

1. Очистите кэш тестов:
```bash
php artisan test --clear-cache
```

2. Проверьте настройки тестовой БД:
```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

3. Запустите миграции для тестов:
```bash
php artisan migrate --env=testing
```

### Проблема: CORS ошибки

**Решение:**

1. Настройте CORS в `config/cors.php`

2. Проверьте middleware в `app/Http/Kernel.php`

### Проблема: Ошибки валидации JSON:API

**Решение:**

1. Убедитесь, что заголовки правильные:
```
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

2. Проверьте формат данных согласно JSON:API спецификации

---

## Полезные команды

### Artisan команды

```bash
# Очистка кэша
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Оптимизация
php artisan optimize
php artisan config:cache
php artisan route:cache

# Миграции
php artisan migrate
php artisan migrate:rollback
php artisan migrate:refresh
php artisan migrate:fresh

# Создание моделей/контроллеров
php artisan make:model ModelName
php artisan make:controller ControllerName
php artisan make:job JobName

# Очереди
php artisan queue:work
php artisan queue:listen
php artisan queue:restart
php artisan queue:failed
php artisan queue:retry all
```

### Docker команды

```bash
# Запуск контейнеров
docker-compose up -d

# Остановка контейнеров
docker-compose down

# Просмотр логов
docker-compose logs -f php_fpm
docker-compose logs -f nginx_webserver

# Выполнение команд в контейнере
docker-compose exec php_fpm php artisan migrate
docker-compose exec php_fpm composer install

# Пересборка контейнеров
docker-compose build --no-cache
docker-compose up -d --build
```

---

## Дополнительные ресурсы

- [Laravel Documentation](https://laravel.com/docs)
- [JSON:API Specification](https://jsonapi.org/)
- [Docker Documentation](https://docs.docker.com/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Redis Documentation](https://redis.io/documentation)

---

## Поддержка

При возникновении проблем:

1. Проверьте логи: `storage/logs/laravel.log`
2. Проверьте документацию в `docs/`
3. Создайте issue в репозитории



