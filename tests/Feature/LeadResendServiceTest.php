<?php

namespace Tests\Feature;

use App\Jobs\Integrations\AutoIntegrationJob;
use App\Jobs\Integrations\SendLeadToMultipleIntegrationsJob;
use App\Models\Lead;
use App\Services\Lead\LeadResendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeadResendServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeadResendService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LeadResendService::class);
        Queue::fake();
    }

    #[Test]
    public function it_can_resend_lead_to_all_integrations()
    {
        $lead = Lead::factory()->create();

        $result = $this->service->resendLead($lead->id);

        $this->assertTrue($result['success']);
        $this->assertEquals($lead->id, $result['lead_id']);
        $this->assertEquals('all_integrations', $result['method']);

        Queue::assertPushed(AutoIntegrationJob::class, function ($job) use ($lead) {
            return $job->leadId === $lead->id;
        });
    }

    #[Test]
    public function it_can_resend_lead_to_specific_integrations()
    {
        $lead = Lead::factory()->create();

        $result = $this->service->resendLead($lead->id, ['email', 'amocrm']);

        $this->assertTrue($result['success']);
        $this->assertEquals($lead->id, $result['lead_id']);
        $this->assertEquals(['email', 'amocrm'], $result['integration_types']);
        $this->assertEquals(2, $result['integrations_count']);

        Queue::assertPushed(SendLeadToMultipleIntegrationsJob::class, function ($job) use ($lead) {
            return $job->leadId === $lead->id
                && count($job->integrations) === 2;
        });
    }

    #[Test]
    public function it_validates_integration_types()
    {
        $lead = Lead::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->service->resendLead($lead->id, ['invalid_type']);
    }

    #[Test]
    public function it_throws_exception_when_lead_not_found()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->resendLead(99999);
    }

    #[Test]
    public function it_can_bulk_resend_leads()
    {
        $leads = Lead::factory()->count(3)->create();
        $leadIds = $leads->pluck('id')->toArray();

        $result = $this->service->bulkResendLeads($leadIds, ['email']);

        $this->assertEquals(3, $result['dispatched_count']);
        $this->assertEquals(3, $result['total_count']);
        $this->assertEquals(0, $result['errors_count']);
        $this->assertEmpty($result['errors']);

        Queue::assertPushed(SendLeadToMultipleIntegrationsJob::class, 3);
    }

    #[Test]
    public function it_handles_errors_in_bulk_resend()
    {
        $lead = Lead::factory()->create();
        $leadIds = [$lead->id, 99999]; // One valid, one invalid

        $result = $this->service->bulkResendLeads($leadIds, ['email']);

        $this->assertEquals(1, $result['dispatched_count']);
        $this->assertEquals(2, $result['total_count']);
        $this->assertEquals(1, $result['errors_count']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals(99999, $result['errors'][0]['lead_id']);
    }

    #[Test]
    public function it_returns_valid_integration_types()
    {
        $types = LeadResendService::getValidIntegrationTypes();

        $this->assertIsArray($types);
        $this->assertContains('email', $types);
        $this->assertContains('amocrm', $types);
        $this->assertContains('telegram', $types);
        $this->assertContains('bitrix24', $types);
        $this->assertContains('webhooks', $types);
    }
}

