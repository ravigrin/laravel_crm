<?php

namespace App\Jobs\Export;

use App\Exceptions\ExportException;
use App\Jobs\Job;
use Illuminate\Bus\Queueable;

class CleanUpExport extends Job
{
    use Queueable;

    protected $filepath;

    public function __construct($filepath)
    {
        $this->filepath = $filepath;
    }

    public function handle()
    {
        try {
            \Storage::delete($this->filepath);
        } catch (ExportException $exception) {
            \Log::critical($exception->getMessage(), $exception->getTrace());
        }
    }
}
