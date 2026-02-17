# Руководство по миграции Query Cache

## Обзор

Этот документ описывает миграцию с удаленного пакета `rennokki/laravel-eloquent-query-cache` на новое решение кеширования запросов, основанное на встроенных возможностях Laravel 12.

## Что изменилось

### Было (старое решение):
```php
use Rennokki\QueryCache\Traits\QueryCacheable;

class Lead extends Model
{
    use QueryCacheable;
    
    protected static $flushCacheOnUpdate = true;
    public $cacheFor = 3600;
}
```

### Стало (новое решение):
```php
use App\Traits\Cacheable;

class Lead extends Model
{
    use Cacheable;
    
    // Конфигурация теперь в config/query_cache.php
}
```

## Новые возможности

### 1. Гибкая конфигурация
```php
// config/query_cache.php
'models' => [
    \App\Models\Lead::class => [
        'ttl' => 3600, // 1 час
        'tags' => ['leads', 'models'],
        'cache_find' => true,
        'cache_where' => true,
        'cache_queries' => true,
        'flush_on_update' => true,
    ],
]
```

### 2. Поддержка тегов кеша
```php
// Автоматическая группировка по тегам
$tags = ['model:lead', 'models'];
```

### 3. Типизированные методы
```php
// В контроллере или сервисе
$cacheService = app(QueryCacheServiceInterface::class);

// Кеширование с автоматической генерацией ключей
$result = $cacheService->rememberQuery($query, 3600);

// Кеширование поиска по ID
$lead = $cacheService->rememberFind(Lead::class, $id, 3600);

// Кеширование запросов WHERE
$leads = $cacheService->rememberWhere(Lead::class, ['status' => 1], 3600);
```

## Использование в моделях

### Базовое использование
```php
class Lead extends Model
{
    use Cacheable;
    
    // Автоматическое кеширование при использовании методов с суффиксом Cached
}
```

### Доступные методы кеширования

#### 1. Поиск с кешированием
```php
// Старый способ (автоматический)
$lead = Lead::find($id);

// Новый способ (явный)
$lead = Lead::findCached($id);
$lead = Lead::findCached($id, 1800); // с кастомным TTL
```

#### 2. Поиск нескольких записей
```php
$leads = Lead::findManyCached([1, 2, 3]);
```

#### 3. Запросы WHERE с кешированием
```php
$leads = Lead::whereCached(['status' => 1, 'user_id' => 123]);
```

#### 4. Получение всех записей
```php
$leads = Lead::allCached();
```

#### 5. Кеширование произвольных запросов
```php
$leads = Lead::query()
    ->where('created_at', '>', now()->subDays(7))
    ->cached(); // автоматическое кеширование
```

## Использование в контроллерах

### Прямое использование сервиса
```php
class LeadController extends Controller
{
    public function index(QueryCacheServiceInterface $cacheService)
    {
        // Кеширование сложного запроса
        $leads = $cacheService->rememberQuery(
            Lead::query()->where('status', 1)->orderBy('created_at', 'desc'),
            3600
        );
        
        return response()->json($leads);
    }
    
    public function show($id, QueryCacheServiceInterface $cacheService)
    {
        // Кеширование поиска по ID
        $lead = $cacheService->rememberFind(Lead::class, $id, 3600);
        
        if (!$lead) {
            return response()->json(['error' => 'Lead not found'], 404);
        }
        
        return response()->json($lead);
    }
}
```

### Использование через трейт
```php
class LeadController extends Controller
{
    public function index()
    {
        // Простое кеширование
        $leads = Lead::whereCached(['status' => 1]);
        
        return response()->json($leads);
    }
}
```

## Управление кешем

### Artisan команды

#### Очистка кеша
```bash
# Очистить весь кеш запросов
php artisan cache:clear-query --all

# Очистить кеш для конкретной модели
php artisan cache:clear-query --model="App\Models\Lead"

# Очистить кеш по тегам
php artisan cache:clear-query --tags="leads,models"
```

#### Статистика кеша
```bash
php artisan cache:query-stats
```

### Программное управление
```php
// Инвалидация кеша модели
$lead->invalidateModelCache();

// Инвалидация по тегам
$cacheService->forgetByTags(['leads', 'models']);

// Очистка всего кеша
$cacheService->clearAllCache();
```

## Конфигурация

### Переменные окружения
```env
# Настройки кеширования запросов
QUERY_CACHE_DEFAULT_TTL=3600
QUERY_CACHE_DRIVER=redis
QUERY_CACHE_PREFIX=query_cache
QUERY_CACHE_LOGGING=false
QUERY_CACHE_STATS=true

# TTL для конкретных моделей
LEAD_CACHE_TTL=3600
STATUS_CACHE_TTL=3600
EMAIL_CACHE_TTL=7200
USER_CACHE_TTL=1800
```

### Настройка драйвера кеша
```php
// config/cache.php
'default' => env('CACHE_STORE', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
    ],
],
```

## Мониторинг и отладка

### Логирование
```php
// Включить логирование операций кеша
QUERY_CACHE_LOGGING=true
```

### Статистика
```php
$stats = app(CacheInvalidationService::class)->getCacheStats();
```

### Метрики производительности
```php
// Проверка поддержки кеширования
$lead = new Lead();
if ($lead->supportsCaching()) {
    $lead->cache(); // Кеширование экземпляра
}
```

## Миграция существующего кода

### 1. Обновить модели
```php
// Добавить трейт Cacheable
use App\Traits\Cacheable;

class YourModel extends Model
{
    use Cacheable;
}
```

### 2. Обновить конфигурацию
```php
// Добавить конфигурацию в config/query_cache.php
'YourModel' => [
    'ttl' => 3600,
    'tags' => ['your_model', 'models'],
    'cache_find' => true,
    'cache_where' => true,
    'cache_queries' => true,
],
```

### 3. Обновить код использования
```php
// Заменить автоматическое кеширование на явное
// Было: автоматическое кеширование
$leads = Lead::where('status', 1)->get();

// Стало: явное кеширование
$leads = Lead::whereCached(['status' => 1]);
// или
$leads = Lead::query()->where('status', 1)->cached();
```

## Преимущества нового решения

### 1. **Производительность**
- Поддержка Redis для высоконагруженных приложений
- Эффективная инвалидация через теги
- Оптимизированные алгоритмы генерации ключей

### 2. **Гибкость**
- Настраиваемый TTL для каждой модели
- Выборочное кеширование операций
- Поддержка кастомных тегов

### 3. **Надежность**
- Fallback при ошибках кеша
- Подробное логирование
- Мониторинг производительности

### 4. **Совместимость**
- Полная совместимость с Laravel 12
- Использование встроенных возможностей
- Типизация и интерфейсы

### 5. **Управляемость**
- Artisan команды для управления
- Статистика и мониторинг
- Централизованная конфигурация

## Тестирование

### Unit тесты
```php
public function test_lead_caching_works()
{
    $lead = Lead::factory()->create();
    
    // Первый запрос - из БД
    $result1 = Lead::findCached($lead->id);
    
    // Второй запрос - из кеша
    $result2 = Lead::findCached($lead->id);
    
    $this->assertEquals($result1->id, $result2->id);
}
```

### Интеграционные тесты
```php
public function test_cache_invalidation_on_update()
{
    $lead = Lead::factory()->create();
    
    // Кешируем запись
    Lead::findCached($lead->id);
    
    // Обновляем запись
    $lead->update(['name' => 'Updated Name']);
    
    // Кеш должен быть инвалидирован
    $this->assertFalse(Cache::has($this->getCacheKey($lead)));
}
```
