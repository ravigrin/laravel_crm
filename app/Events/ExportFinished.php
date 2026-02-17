<?php

namespace App\Events;

class ExportFinished extends Event
{
    public $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }
}
