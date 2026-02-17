<?php

namespace App\Providers;

use App\Events\ExportFinished;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\ExportFinishedNotification;
use Illuminate\Queue\Events\JobProcessed;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ExportFinished::class => [
            \App\Listeners\ExportFinished::class
        ]
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(StatementPrepared::class, function ($event) {
            $event->statement->setFetchMode(\PDO::FETCH_ASSOC);
        });
    }
}
