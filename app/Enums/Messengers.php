<?php

declare(strict_types=1);

namespace App\Enums;


enum Messengers: string
{
    case vk = 'vk';
    case telegram = 'telegram';
    case viber = 'viber';
    case whatsapp = 'whatsapp';
    case messenger = 'messenger';
    case skype = 'skype';
    case instagram = 'instagram';

}