<?php

declare(strict_types=1);

namespace App\Enums;


enum AvailableIntegrations: string
{
    case email = 'email';
    case bitrix24 = 'bitrix24';
    case amocrm = 'amocrm';
    case getResponse = 'getResponse';
    case lptracker = 'lptracker';
    case sendPulse = 'sendPulse';
    case telegram = 'telegram';
    case uniSender = 'uniSender';
    case uonTravel = 'uonTravel';
    case webhooks = 'webhooks';


    public function description(): string
    {
        return match($this) {
            self::email => __('integrations.email'),
            self::bitrix24 => __('integrations.bitrix24'),
            self::amocrm => __('integrations.amocrm'),
            self::getResponse => __('integrations.getresponse'),
            self::lptracker => __('integrations.lptracker'),
            self::sendPulse => __('integrations.sendpulse'),
            self::telegram => __('integrations.telegram'),
            self::uniSender => __('integrations.unisender'),
            self::uonTravel => __('integrations.uontravel'),
            self::webhooks => __('integrations.webhooks'),
        };
    }
}