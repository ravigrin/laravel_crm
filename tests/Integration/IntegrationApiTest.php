<?php

namespace Tests\Integration;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_get_available_integration_types()
    {
        $response = $this->getJson('/api/integrations/types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'types'
                ]
            ])
            ->assertJson([
                'success' => true
            ]);

        $types = $response->json('data.types');
        $this->assertIsArray($types);
        $this->assertContains('email', $types);
        $this->assertContains('amocrm', $types);
    }

    #[Test]
    public function it_can_test_integration_connection()
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200)
        ]);

        $response = $this->postJson('/api/integrations/test', [
            'type' => 'email',
            'credentials' => [
                'emails' => ['test@example.com']
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ])
            ->assertJson([
                'success' => true
            ]);
    }

    #[Test]
    public function it_can_send_lead_to_integration()
    {
        Http::fake([
            '*' => Http::response(['id' => 'ext_123'], 200)
        ]);

        $lead = Lead::factory()->create();

        $response = $this->postJson('/api/integrations/send', [
            'lead_id' => $lead->id,
            'type' => 'email',
            'credentials' => [
                'emails' => ['admin@example.com']
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'external_id',
                    'integration_data'
                ]
            ])
            ->assertJson([
                'success' => true
            ]);
    }

    #[Test]
    public function it_validates_required_fields_on_send()
    {
        $response = $this->postJson('/api/integrations/send', [
            'type' => 'email'
            // Missing lead_id and credentials
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lead_id', 'credentials']);
    }

    #[Test]
    public function it_can_get_integration_config()
    {
        $response = $this->getJson('/api/integrations/email/config');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'type',
                    'required_fields',
                    'fields'
                ]
            ]);
    }

    #[Test]
    public function it_returns_404_for_invalid_integration_type()
    {
        $response = $this->getJson('/api/integrations/invalid_type/config');

        $response->assertStatus(404);
    }
}

