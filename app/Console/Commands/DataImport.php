<?php

namespace App\Console\Commands;

use App\Jobs\MongoImport\ImportIntegrationsCredentialsJob;
use App\Jobs\MongoImport\ImportLeadsJob;
use App\Jobs\MongoImport\ImportOAuthServices;
use App\Jobs\MongoImport\ImportOAuthTokens;
use Illuminate\Console\Command;

class DataImport extends Command
{
    protected $signature = 'import:mongo_postgres
                                {collection : Specify which collection will be imported (Leads, Integrations, OAuthServices, OAuthTokens)}
                                {--link= : Link to database}
                                {--port= : Database port}
                                {--db= : Database name}
                                {--user= : Username}
                                {--password= : Password}';

    protected $description = 'Import data from mongo to postgres. Leads and Integrations as params';

    public function handle()
    {
        switch ($this->argument('collection')) {
            case 'Leads':
                $job = new ImportLeadsJob($this->prepareArguments());
                $job->handle();
                $this->info('Leads successfully imported');
                break;
            case 'Integrations':
                $job = new ImportIntegrationsCredentialsJob($this->prepareArguments());
                $job->handle();
                $this->info('Integrations successfully imported');
                break;
            case 'OAuthServices':
                $job = new ImportOAuthServices($this->prepareArguments());
                $job->handle();
                $this->info('OAuthServices successfully imported');
                break;
            case 'OAuthTokens':
                $job = new ImportOAuthTokens($this->prepareArguments());
                $job->handle();
                $this->info('OAuthTokens successfully imported');
                break;
        }
    }

    private function prepareArguments()
    {
        return [
            'collection' => $this->argument('collection'),
            'link' => $this->option('link') ?: env('MONGO_LINK'),
            'port' => $this->option('port') ?: env('MONGO_PORT'),
            'db' => $this->option('db') ?: env('MONGO_DB'),
            'user' => $this->option('user') ?: env('MONGO_USER'),
            'password' => $this->option('password') ?: env('MONGO_PASSWORD'),
        ];
    }
}
