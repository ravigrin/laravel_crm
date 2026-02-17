<?php

namespace Tests\Feature;

use App\Jobs\Integrations\AutoIntegrationJob;
use App\Jobs\Integrations\SendLeadToMultipleIntegrationsJob;
use App\Models\Integration\Credentials;
use App\Models\Integration\EntityCredentials;
use App\Models\Integration\ProjectCredentials;
use App\Models\Lead;
use Database\Factories\CredentialsFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AutoIntegrationJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_dispatches_send_lead_to_multiple_integrations_job_when_credentials_exist()
    {
        Queue::fake();

        $lead = Lead::factory()->create([
            'external_entity_id' => 'entity-123',
            'external_project_id' => 'project-456'
        ]);

        // Create credentials for entity
        $emailCredential = CredentialsFactory::new()->email()->create();

        EntityCredentials::create([
            'external_entity_id' => 'entity-123',
            'integration_credentials_id' => $emailCredential->id
        ]);

        // Create credentials for project
        $amocrmCredential = CredentialsFactory::new()->amocrm()->create();

        ProjectCredentials::create([
            'external_project_id' => 'project-456',
            'integration_credentials_id' => $amocrmCredential->id
        ]);

        $job = new AutoIntegrationJob($lead->id);
        $job->handle();

        Queue::assertPushed(SendLeadToMultipleIntegrationsJob::class, function ($job) use ($lead) {
            return $job->leadId === $lead->id
                && count($job->integrations) === 2
                && in_array('email', array_column($job->integrations, 'type'))
                && in_array('amocrm', array_column($job->integrations, 'type'));
        });
    }

    #[Test]
    public function it_skips_unsupported_integrations()
    {
        Queue::fake();

        $lead = Lead::factory()->create([
            'external_entity_id' => 'entity-123'
        ]);

        // Create unsupported integration credential
        $unsupportedCredential = CredentialsFactory::new()->create([
            'code' => 'getresponse', // Not supported by AutoIntegrationJob
            'enabled' => true,
            'credentials' => []
        ]);

        EntityCredentials::create([
            'external_entity_id' => 'entity-123',
            'integration_credentials_id' => $unsupportedCredential->id
        ]);

        $job = new AutoIntegrationJob($lead->id);
        $job->handle();

        // Should not dispatch if no supported integrations
        Queue::assertNotPushed(SendLeadToMultipleIntegrationsJob::class);
    }

    #[Test]
    public function it_handles_lead_not_found()
    {
        Queue::fake();

        $job = new AutoIntegrationJob(99999);
        
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $job->handle();
    }

    #[Test]
    public function it_handles_no_credentials()
    {
        Queue::fake();

        // Create lead with valid external_entity_id but no credentials linked
        $lead = Lead::factory()->create([
            'external_entity_id' => 'entity-without-credentials',
            'external_project_id' => 'project-without-credentials'
        ]);

        $job = new AutoIntegrationJob($lead->id);
        $job->handle();

        // Should not dispatch if no credentials
        Queue::assertNotPushed(SendLeadToMultipleIntegrationsJob::class);
    }

    #[Test]
    public function it_only_uses_enabled_credentials()
    {
        Queue::fake();

        $lead = Lead::factory()->create([
            'external_entity_id' => 'entity-123'
        ]);

        // Create enabled credential
        $enabledCredential = CredentialsFactory::new()->email()->create();

        // Create disabled credential
        $disabledCredential = CredentialsFactory::new()->amocrm()->disabled()->create();

        EntityCredentials::create([
            'external_entity_id' => 'entity-123',
            'integration_credentials_id' => $enabledCredential->id
        ]);

        EntityCredentials::create([
            'external_entity_id' => 'entity-123',
            'integration_credentials_id' => $disabledCredential->id
        ]);

        $job = new AutoIntegrationJob($lead->id);
        $job->handle();

        Queue::assertPushed(SendLeadToMultipleIntegrationsJob::class, function ($job) {
            return count($job->integrations) === 1
                && $job->integrations[0]['type'] === 'email';
        });
    }
}

