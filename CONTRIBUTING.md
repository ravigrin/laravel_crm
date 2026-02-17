# Contributing Guide

Welcome to the Laravel CRM project! This guide explains how to contribute, especially when adding new integrations.

## Development Setup

```bash
# Clone repository
git clone https://github.com/your-org/laravel_crm.git
cd laravel_crm

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate --seed

# Start development
php artisan serve
php artisan queue:work
php artisan horizon # in separate terminal
```

## Code Standards

We follow PSR-12 and Laravel conventions:

```bash
# Format code
./vendor/bin/pint

# Lint code
./vendor/bin/phpstan analyse

# Run tests
php artisan test

# Check code coverage
php artisan test --coverage --min=80
```

## Git Workflow

```bash
# Create feature branch
git checkout -b feature/my-feature

# Make changes and commit
git commit -m "feat: describe your change"

# Push and create PR
git push origin feature/my-feature
```

**Commit Messages:** Use conventional commits (feat:, fix:, docs:, test:, etc.)

## Adding a New Integration

### 1. Create Integration Class

```php
// app/Services/Integration/MyIntegration.php
namespace App\Services\Integration;

class MyIntegration extends BaseIntegration
{
    /**
     * Validate integration credentials
     */
    public function validate(): bool
    {
        return !empty($this->config['api_key']) &&
               !empty($this->config['api_url']);
    }

    /**
     * Send lead to external service
     */
    public function send(Lead $lead): IntegrationResult
    {
        try {
            $response = $this->httpClient->post(
                $this->config['api_url'] . '/leads',
                $this->mapLeadToPayload($lead)
            );

            return IntegrationResult::success([
                'external_id' => $response['id'],
                'status' => $response['status'],
            ]);
        } catch (\Exception $e) {
            return IntegrationResult::failure($e->getMessage());
        }
    }

    /**
     * Map Lead model to integration payload
     */
    private function mapLeadToPayload(Lead $lead): array
    {
        return [
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'custom_field_1' => $lead->data['custom_field_1'] ?? null,
        ];
    }

    /**
     * Get rate limit configuration
     */
    public function getRateLimit(): array
    {
        return [
            'requests_per_second' => 2,
            'timeout' => 30,
        ];
    }
}
```

### 2. Register in Factory

```php
// app/Services/Integration/IntegrationFactory.php
public static function make(string $type): ?BaseIntegration
{
    return match ($type) {
        'amocrm' => new AmoCrmIntegration(),
        'my_integration' => new MyIntegration(), // ← Add this
        default => null,
    };
}
```

### 3. Add Configuration

```php
// config/integrations.php
'my_integration' => [
    'required_fields' => ['api_key', 'api_url'],
    'fields' => [
        'name' => ['type' => 'attr', 'key' => 'name'],
        'email' => ['type' => 'attr', 'key' => 'email'],
        'phone' => ['type' => 'attr', 'key' => 'phone'],
    ],
    'timeout' => 30,
    'retry' => true,
],
```

### 4. Create Job Class

```php
// app/Jobs/Integrations/MyIntegrationJob.php
namespace App\Jobs\Integrations;

class MyIntegrationJob extends BaseIntegrationJob
{
    public function getIntegrationType(): string
    {
        return 'my_integration';
    }

    public function getQueueName(): string
    {
        return 'integrations_my';
    }
}
```

### 5. Write Tests

```php
// tests/Integration/MyIntegrationTest.php
namespace Tests\Integration;

use App\Models\Lead;
use App\Services\Integration\MyIntegration;
use Tests\TestCase;

class MyIntegrationTest extends TestCase
{
    public function test_it_validates_required_credentials()
    {
        $integration = new MyIntegration([]);
        $this->assertFalse($integration->validate());
    }

    public function test_it_sends_lead_successfully()
    {
        $lead = Lead::factory()->create();
        $integration = new MyIntegration([
            'api_key' => 'test-key',
            'api_url' => 'https://api.example.com',
        ]);

        $result = $integration->send($lead);
        $this->assertTrue($result->isSuccessful());
        $this->assertNotEmpty($result->getData()['external_id']);
    }

    public function test_it_handles_api_errors()
    {
        $lead = Lead::factory()->create();
        $integration = new MyIntegration([
            'api_key' => 'invalid-key',
        ]);

        $result = $integration->send($lead);
        $this->assertFalse($result->isSuccessful());
    }
}
```

### 6. Update Documentation

Add to `docs/INTEGRATIONS_ARCHITECTURE.md`:

```markdown
## MyIntegration

**Type:** `my_integration`

**Rate Limits:** 2 req/s, 30s timeout

**Required Fields:**
- `api_key` - API authentication key
- `api_url` - API base URL

**Supported Fields:**
- name, email, phone
- custom_field_1

**Example:**
POST /v1/integration-credentials
{
    "type": "my_integration",
    "name": "My Integration Account",
    "credentials": {
        "api_key": "xxxx",
        "api_url": "https://api.example.com"
    }
}
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test tests/Integration/MyIntegrationTest.php

# Run with coverage
php artisan test --coverage --min=80
```

## Code Review Checklist

- [ ] Code follows PSR-12 standards
- [ ] Tests added for new functionality
- [ ] Documentation updated
- [ ] No hardcoded secrets
- [ ] Handles edge cases and errors
- [ ] Performance acceptable (no N+1 queries)
- [ ] Security considerations reviewed

## Questions?

- Check existing integrations for patterns
- Review `docs/INTEGRATIONS_ARCHITECTURE.md`
- Ask in code review comments
- Contact @team-leads

---

**Last Updated:** February 2026
