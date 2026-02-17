<?php

namespace App\Jobs\MongoImport;

use App\Jobs\Job;

abstract class MongoImportJob extends Job
{
    protected $client;
    protected $collectionName;

    public function __construct($params)
    {
        $connectionString = "mongodb://{$params['link']}:{$params['port']}/{$params['db']}";
        $this->client = new \MongoDB\Client(
            $connectionString,
            [
                'authMechanism' => 'SCRAM-SHA-1',
                'username' => $params['user'],
                'password' => $params['password']
            ],
            [
                'typeMap' => [
                    'root' => 'array',
                    'document' => 'array',
                    'array' => 'array',
                ],
            ]
        );
    }

    public function database()
    {
        return $this->client->quiz;
    }


    abstract public function insertRows($rows);

    /**
     * @param int $limit
     * @param int $offset
     * @param array $additional
     * @return \Traversable
     */
    public function getMongoData($limit = 1000, $offset = 0, $additional = [])
    {
        $options = [
            [
                '$skip' => $offset
            ],
            [
                '$limit' => $limit
            ]
        ];

        if (!empty($additional)) {
            $options[] = $additional;
        }

        $collection = $this->database()->selectCollection($this->collectionName);

        return $collection->aggregate($options);
    }
}
