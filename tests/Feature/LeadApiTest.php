<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeadApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    #[Test]
    public function it_can_create_a_lead_via_json_api()
    {
        $user = User::factory()->create();
        
        $leadData = [
            'data' => [
                'type' => 'leads',
                'attributes' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'phone' => '+1234567890',
                    'userId' => $user->id,
                    'data' => [
                        'answers2' => [
                            ['q' => 'What is your name?', 'a' => 'John Doe']
                        ]
                    ],
                    'utmSource' => 'google',
                    'utmMedium' => 'cpc',
                    'utmCampaign' => 'test_campaign',
                ]
            ]
        ];

        $response = $this->postJsonApi('/api/v1/leads', $leadData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'name',
                        'email',
                        'phone'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('leads', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'user_id' => $user->id
        ]);
    }

    #[Test]
    public function it_can_get_a_lead_by_id()
    {
        $lead = Lead::factory()->create();

        $response = $this->getJsonApi("/api/v1/leads/{$lead->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => (string)$lead->id,
                    'attributes' => [
                        'name' => $lead->name,
                        'email' => $lead->email
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_can_list_leads()
    {
        Lead::factory()->count(5)->create();

        $response = $this->getJsonApi('/api/v1/leads');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'attributes'
                    ]
                ],
                'links',
                'meta'
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    #[Test]
    public function it_can_update_a_lead()
    {
        $lead = Lead::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com'
        ]);

        $updateData = [
            'data' => [
                'type' => 'leads',
                'id' => (string)$lead->id,
                'attributes' => [
                    'name' => 'New Name',
                    'email' => 'new@example.com'
                ]
            ]
        ];

        $response = $this->patchJsonApi("/api/v1/leads/{$lead->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'name' => 'New Name',
                        'email' => 'new@example.com'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'name' => 'New Name',
            'email' => 'new@example.com'
        ]);
    }

    #[Test]
    public function it_can_delete_a_lead()
    {
        $lead = Lead::factory()->create();

        $response = $this->deleteJsonApi("/api/v1/leads/{$lead->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('leads', [
            'id' => $lead->id
        ]);
    }

    #[Test]
    public function it_can_resend_lead_to_all_integrations()
    {
        $lead = Lead::factory()->create();

        $response = $this->postJson("/api/v1/leads/{$lead->id}/resend", []);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Lead resend queued to all configured integrations',
                'data' => [
                    'lead_id' => $lead->id,
                    'method' => 'all_integrations'
                ]
            ]);
    }

    #[Test]
    public function it_can_resend_lead_to_specific_integrations()
    {
        $lead = Lead::factory()->create();

        $response = $this->postJson("/api/v1/leads/{$lead->id}/resend", [
            'integration_types' => ['email', 'amocrm']
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Lead resend queued to specified integrations',
                'data' => [
                    'lead_id' => $lead->id,
                    'integration_types' => ['email', 'amocrm'],
                    'integrations_count' => 2
                ]
            ]);
    }

    #[Test]
    public function it_validates_integration_types_on_resend()
    {
        $lead = Lead::factory()->create();

        $response = $this->postJson("/api/v1/leads/{$lead->id}/resend", [
            'integration_types' => ['invalid_type']
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['integration_types.0']);
    }

    #[Test]
    public function it_can_bulk_resend_leads()
    {
        $leads = Lead::factory()->count(3)->create();
        $leadIds = $leads->pluck('id')->toArray();

        $response = $this->postJson('/api/v1/leads/bulk-resend', [
            'lead_ids' => $leadIds,
            'integration_types' => ['email']
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'dispatched_count' => 3,
                    'total_count' => 3,
                    'errors_count' => 0,
                    'integration_types' => ['email']
                ]
            ]);
    }

    #[Test]
    public function it_validates_lead_ids_on_bulk_resend()
    {
        $response = $this->postJson('/api/v1/leads/bulk-resend', [
            'lead_ids' => [99999, 99998]
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lead_ids.0', 'lead_ids.1']);
    }

    #[Test]
    public function it_can_get_kanban_data()
    {
        Lead::factory()->count(10)->create();

        $response = $this->getJson('/api/v1/leads/kanban');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'status',
                        'leads' => [
                            '*' => [
                                'id',
                                'name',
                                'email'
                            ]
                        ]
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_can_get_filter_counts()
    {
        Lead::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/leads/filter-counts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'cities',
                    'quizzes',
                    'statuses'
                ]
            ]);
    }
}
