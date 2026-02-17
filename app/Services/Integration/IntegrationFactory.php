<?php

namespace App\Services\Integration;

use App\Interfaces\IntegrationChannelInterface;
use App\Interfaces\HttpServiceInterface;
use App\Interfaces\LocaleServiceInterface;
use App\Interfaces\MailServiceInterface;
use App\Services\FieldMapperService;
use Illuminate\Contracts\Container\Container;
use Psr\Log\LoggerInterface;

class IntegrationFactory
{
    protected Container $container;
    protected HttpServiceInterface $httpService;
    protected FieldMapperService $fieldMapper;
    protected MailServiceInterface $mailService;
    protected LocaleServiceInterface $localeService;
    protected LoggerInterface $logger;

    public function __construct(
        Container $container,
        HttpServiceInterface $httpService,
        FieldMapperService $fieldMapper,
        MailServiceInterface $mailService,
        LocaleServiceInterface $localeService,
        LoggerInterface $logger
    ) {
        $this->container = $container;
        $this->httpService = $httpService;
        $this->fieldMapper = $fieldMapper;
        $this->mailService = $mailService;
        $this->localeService = $localeService;
        $this->logger = $logger;
    }

    /**
     * Create integration by type
     */
    public function create(string $type, array $config = []): IntegrationChannelInterface
    {
        $baseUrl = $config['base_url'] ?? '';

        return match ($type) {
            'email' => new EmailIntegration(
                $this->httpService,
                $this->fieldMapper,
                $this->mailService,
                $this->localeService,
                $baseUrl
            ),
            'amocrm' => new AmoCrmIntegration(
                $this->httpService,
                $this->fieldMapper,
                $this->logger,
                $config['base_url'] ?? config('integrations.amocrm.base_url')
            ),
            'telegram' => new TelegramIntegration(
                $this->httpService,
                $this->fieldMapper,
                $config['base_url'] ?? config('integrations.telegram.base_url')
            ),
            'bitrix24' => new Bitrix24Integration(
                $this->httpService,
                $this->fieldMapper,
                $this->logger,
                $config['base_url'] ?? config('integrations.bitrix24.base_url')
            ),
            'getresponse' => new GetResponseIntegration(
                $this->httpService,
                $this->fieldMapper,
                $config['base_url'] ?? config('integrations.getresponse.base_url')
            ),
            'sendpulse' => new SendPulseIntegration(
                $this->httpService,
                $this->fieldMapper,
                $config['base_url'] ?? config('integrations.sendpulse.base_url')
            ),
            'unisender' => new UniSenderIntegration(
                $this->httpService,
                $this->fieldMapper,
                $config['base_url'] ?? config('integrations.unisender.base_url')
            ),
            'uon_travel' => new UonTravelIntegration(
                $this->httpService,
                $this->fieldMapper,
                $config['base_url'] ?? config('integrations.uon_travel.base_url')
            ),
            'lptracker' => new LpTrackerIntegration(
                $this->httpService,
                $this->fieldMapper,
                $config['base_url'] ?? config('integrations.lptracker.base_url')
            ),
            'webhooks' => new WebhooksIntegration(
                $this->httpService,
                $this->fieldMapper,
                $config['base_url'] ?? ''
            ),
            'retailcrm' => new RetailCrmIntegration(
                $this->httpService,
                $this->fieldMapper,
                $this->logger,
                $config['base_url'] ?? config('integrations.retailcrm.base_url')
            ),
            'mailchimp' => new MailchimpIntegration(
                $this->httpService,
                $this->fieldMapper,
                $this->logger,
                $config['base_url'] ?? config('integrations.mailchimp.base_url')
            ),
            default => throw new \InvalidArgumentException("Integration type '{$type}' not supported")
        };
    }

    /**
     * Get available integration types
     */
    public function getAvailableTypes(): array
    {
        return [
            'email',
            'amocrm',
            'telegram',
            'bitrix24',
            'getresponse',
            'sendpulse',
            'unisender',
            'uon_travel',
            'lptracker',
            'webhooks',
            'retailcrm',
            'mailchimp'
        ];
    }

    /**
     * Check if integration type is supported
     */
    public function isSupported(string $type): bool
    {
        return in_array($type, $this->getAvailableTypes());
    }
}
