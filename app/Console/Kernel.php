<?php

namespace App\Console;

use App\Console\Commands\Lead\CleanUp;
use App\Console\Commands\DataImport;
use App\Console\Commands\Lead\Restore;
use App\Jobs\SendLeadStatistic;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CleanUp::class,
        Restore::class,
        DataImport::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('leads:clean_up')->monthly()->withoutOverlapping();
        $schedule->job(new SendLeadStatistic())->weekly();
    }
}
