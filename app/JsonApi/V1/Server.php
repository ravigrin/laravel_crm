<?php

namespace App\JsonApi\V1;

use LaravelJsonApi\Core\Server\Server as BaseServer;

class Server extends BaseServer
{
    /**
     * The base URI namespace for this server.
     */
    protected string $baseUri = '/api/v1';

    /**
     * Get the server's list of schemas.
     */
    protected function allSchemas(): array
    {
        return [
            Leads\LeadSchema::class,
            Statuses\StatusSchema::class,
        ];
    }

    /**
     * Get the authorizer class name.
     * Returns a simple authorizer that allows all actions for testing.
     */
    public function authorizer(): string
    {
        return Authorizer::class;
    }
}
