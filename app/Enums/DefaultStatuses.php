<?php
declare(strict_types=1);

namespace App\Enums;


enum DefaultStatuses: int
{
    case New = 1;
    case AtWork = 2;
    case Success = 3;
    case Declined = 4;


    public function description(): string
    {
        return match($this) {
            self::New => __('statuses.new'),
            self::AtWork => __('statuses.at_work'),
            self::Success => __('statuses.success'),
            self::Declined => __('statuses.declined'),
        };
    }
}