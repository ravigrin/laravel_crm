<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test environment
        $this->artisan('migrate', ['--database' => 'sqlite'])->run();
        
        // Disable rate limiting middleware in tests
        $this->withoutMiddleware(\App\Http\Middleware\IpFilter::class);
        $this->withoutMiddleware(\App\Http\Middleware\SpamProtection::class);
        
        // Mock HTTP responses for all integrations
        $this->setupIntegrationMocks();
    }

    /**
     * Setup HTTP mocks for all integrations to simulate successful API responses
     */
    protected function setupIntegrationMocks(): void
    {
        Http::fake([
            // AmoCRM API
            '*.amocrm.ru/*' => Http::response([
                '_embedded' => [
                    'leads' => [
                        [
                            'id' => 123456,
                            'name' => 'Test Lead'
                        ]
                    ]
                ]
            ], 200),
            
            // Telegram API
            'api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 123,
                    'text' => 'Message sent'
                ]
            ], 200),
            
            // Bitrix24 webhook
            '*.bitrix24.ru/*' => Http::response([
                'result' => 123456
            ], 200),
            
            // GetResponse API
            'api.getresponse.com/*' => Http::response([
                'contactId' => 'abc123',
                'href' => 'https://api.getresponse.com/v3/contacts/abc123'
            ], 200),
            
            // SendPulse API
            'api.sendpulse.com/*' => Http::response([
                'result' => true,
                'id' => 123456
            ], 200),
            
            // UniSender API
            'api.unisender.com/*' => Http::response([
                'result' => [
                    'person_id' => 123456,
                    'email_id' => 789012
                ]
            ], 200),
            
            // UON Travel API
            'uon.travel/*' => Http::response([
                'result' => [
                    'id' => 123456
                ]
            ], 200),
            
            // LpTracker API
            '*.lptracker.ru/*' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 123456
                ]
            ], 200),
            
            // RetailCRM API
            '*.retailcrm.ru/*' => Http::response([
                'success' => true,
                'id' => 123456
            ], 200),
            
            // Mailchimp API
            '*.api.mailchimp.com/*' => Http::response([
                'id' => 'abc123',
                'email_address' => 'test@example.com',
                'status' => 'subscribed'
            ], 200),
            
            // Webhooks - any POST request
            '*' => Http::response([
                'success' => true,
                'message' => 'Webhook received',
                'id' => 'ext_123'
            ], 200),
        ]);
        
        // Mock MailService to always return success
        $this->app->singleton(\App\Interfaces\MailServiceInterface::class, function () {
            $mock = \Mockery::mock(\App\Interfaces\MailServiceInterface::class);
            $mock->shouldReceive('send')->andReturn(true);
            $mock->shouldReceive('sendWithTemplate')->andReturn(true);
            return $mock;
        });
        
        // Mock LocaleService to return a test email template
        $this->app->singleton(\App\Interfaces\LocaleServiceInterface::class, function () {
            $mock = \Mockery::mock(\App\Interfaces\LocaleServiceInterface::class);
            
            // Create a real Email model instance for testing
            $emailTemplate = new \App\Models\Email();
            $emailTemplate->template_id = 'test-template-id';
            $emailTemplate->template_code = 'new_lead';
            $emailTemplate->locale_code = 'RU';
            
            $mock->shouldReceive('getEmailTemplate')->andReturn($emailTemplate);
            $mock->shouldReceive('translate')->andReturn('Translated text');
            $mock->shouldReceive('getCurrentLocale')->andReturn('RU');
            $mock->shouldReceive('setCurrentLocale')->andReturnNull();
            return $mock;
        });
        
        // Mock PhoneVerificationService to skip verification in tests
        $this->app->singleton(\App\Services\PhoneVerification\PhoneVerificationService::class, function () {
            $mock = \Mockery::mock(\App\Services\PhoneVerification\PhoneVerificationService::class);
            $mock->shouldReceive('ensureVerified')->andReturnNull();
            $mock->shouldReceive('attachLead')->andReturnNull();
            return $mock;
        });
        
        // Mock LeadValidationService to skip validation in tests
        if (interface_exists(\App\Services\Lead\LeadValidationService::class) || class_exists(\App\Services\Lead\LeadValidationService::class)) {
            $this->app->singleton(\App\Services\Lead\LeadValidationService::class, function () {
                $mock = \Mockery::mock(\App\Services\Lead\LeadValidationService::class);
                $mock->shouldReceive('validateLead')->andReturnNull();
                return $mock;
            });
        }
        
        // Mock rate limiters to skip rate limiting in tests
        $this->app->singleton(\App\Services\Lead\UserRateLimiter::class, function () {
            $mock = \Mockery::mock(\App\Services\Lead\UserRateLimiter::class);
            $mock->shouldReceive('ensureGlobalLimit')->andReturnNull();
            return $mock;
        });
        
        $this->app->singleton(\App\Services\Lead\ClientIdRateLimiter::class, function () {
            $mock = \Mockery::mock(\App\Services\Lead\ClientIdRateLimiter::class);
            $mock->shouldReceive('ensureLeadsLimit')->andReturnNull();
            $mock->shouldReceive('ensureQuizzesLimit')->andReturnNull();
            return $mock;
        });
        
        $this->app->singleton(\App\Services\Lead\TestLeadLimiter::class, function () {
            $mock = \Mockery::mock(\App\Services\Lead\TestLeadLimiter::class);
            $mock->shouldReceive('ensureWithinLimit')->andReturnNull();
            return $mock;
        });
        
        // Mock other lead services to return safe defaults
        $this->app->singleton(\App\Services\Lead\DuplicateDetector::class, function () {
            $mock = \Mockery::mock(\App\Services\Lead\DuplicateDetector::class);
            $mock->shouldReceive('linkDuplicate')->andReturnNull();
            return $mock;
        });
        
        $this->app->singleton(\App\Services\Lead\LeadPaymentService::class, function () {
            $mock = \Mockery::mock(\App\Services\Lead\LeadPaymentService::class);
            $mock->shouldReceive('shouldMarkPaid')->andReturn(false);
            return $mock;
        });
        
        $this->app->singleton(\App\Services\Lead\LeadBlockService::class, function () {
            $mock = \Mockery::mock(\App\Services\Lead\LeadBlockService::class);
            $mock->shouldReceive('shouldBlock')->andReturn(false);
            return $mock;
        });
        
        $this->app->singleton(\App\Services\Lead\GeoLocationService::class, function () {
            $mock = \Mockery::mock(\App\Services\Lead\GeoLocationService::class);
            $mock->shouldReceive('getLocationByIp')->andReturn(['city' => null, 'country' => null]);
            return $mock;
        });
    }

    /**
     * JSON API content type
     */
    protected const JSON_API_CONTENT_TYPE = 'application/vnd.api+json';

    /**
     * Make a JSON API request with proper headers
     */
    protected function jsonApi(string $method, string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        $headers = array_merge([
            'Accept' => self::JSON_API_CONTENT_TYPE,
            'Content-Type' => self::JSON_API_CONTENT_TYPE,
        ], $headers);

        $content = !empty($data) ? json_encode($data) : '';

        return $this->call(
            $method,
            $uri,
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers),
            $content
        );
    }

    /**
     * Make a JSON API GET request
     */
    protected function getJsonApi(string $uri, array $headers = []): \Illuminate\Testing\TestResponse
    {
        $headers = array_merge([
            'Accept' => self::JSON_API_CONTENT_TYPE,
        ], $headers);

        return $this->call(
            'GET',
            $uri,
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers)
        );
    }

    /**
     * Make a JSON API POST request
     */
    protected function postJsonApi(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->jsonApi('POST', $uri, $data, $headers);
    }

    /**
     * Make a JSON API PATCH request
     */
    protected function patchJsonApi(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->jsonApi('PATCH', $uri, $data, $headers);
    }

    /**
     * Make a JSON API DELETE request
     */
    protected function deleteJsonApi(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        $headers = array_merge([
            'Accept' => self::JSON_API_CONTENT_TYPE,
        ], $headers);

        $content = !empty($data) ? json_encode($data) : '';

        return $this->call(
            'DELETE',
            $uri,
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers),
            $content
        );
    }
}
