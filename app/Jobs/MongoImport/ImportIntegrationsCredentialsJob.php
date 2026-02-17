<?php
namespace App\Jobs\MongoImport;

use App\Enums\AvailableIntegrations;
use App\Models\Integration\Credentials;
use App\Models\Integration\EntityCredentials;
use App\Models\OAuth\Token;
use Illuminate\Support\Str;

class ImportIntegrationsCredentialsJob extends MongoImportJob
{
    protected $collectionName = 'Quiz';
    protected $credentialsHashes = [];

    public function handle()
    {
        $limit = 1000;
        $offset = 0;

        while (true) {
            $mongoCollection = $this->getMongoData($limit, $offset, [
                '$lookup' => [
                    'from' => 'user',
                    'localField' => 'userId',
                    'foreignField' => '_id',
                    'as' => 'user',
                ]
            ]);

            $credentials = [];
            $break = true;
            foreach ($mongoCollection as $item) {
                $break = false;
                if (!$item['notifications']) {
                    continue;
                }

                foreach ($item['notifications'] as $code => $notification) {
                    if ($code === 'email') {
                        $notification['addresses'][] = $item['user'][0]['email'];
                    }

                    if (empty($notification) || !in_array($code, AvailableIntegrations::getValues(), false)) {
                        continue;
                    }

                    $notification = $this->indexesToSnakeCase($notification);

                    if ($code === 'amocrm' && array_key_exists('token_id', $notification)) {
                        $notification['token_id'] = $this->getTokenId($notification['token_id']);
                    }

                    $hash = $this->credsHash(json_encode($notification));

                    if (in_array($hash, $this->credentialsHashes, false)) {
                        continue;
                    }

                    $credentials[$hash] = [
                        'code' => $code,
                        'enabled'=> array_key_exists('enabled', $notification) ? $notification['enabled'] : false,
                        'credentials' => json_encode($notification),
                        'temp_id' => $item['_id']->__toString(),
                        'hash' => $hash,
                    ];
                }
            }

            $this->removeStoredItems($credentials);

            if (!empty($credentials) && $this->insertRows($credentials) === true) {
                $this->credentialsHashes[] = array_keys($credentials);
                $this->setCredentialsRelation();
            }

            if ($break) {
                break;
            }
            $offset += $limit;
        }
    }

    private function removeStoredItems($items = [])
    {
        if (empty($items)) {
            return [];
        }

        Credentials::select('hash')->whereIn('hash', array_keys($items))->get()
            ->map(function ($item) use ($items) {
                unset($items[$item->hash]);
            });
    }

    public function insertRows($rows)
    {
        try {
            Credentials::insert($rows);
            return true;
        } catch (\Exception $exception) {
            \Log::critical($exception->getMessage(), $exception->getTrace());
        }
    }

    private function credsHash($string)
    {
        return hash('md5', $string);
    }

    private function setCredentialsRelation()
    {
        $entityRelationTable = with(new EntityCredentials())->getTable();
        $credetnialsTable = with(new Credentials())->getTable();

        $select = \DB::table($credetnialsTable)
            ->join(
                $entityRelationTable,
                "{$entityRelationTable}.integration_credentials_id",
                '=',
                "{$credetnialsTable}.id",
                'left outer'
            )
            ->get(["{$credetnialsTable}.temp_id as entity_id", "{$credetnialsTable}.id as integration_credentials_id"]);

        $result = $select->all();

        try {
            EntityCredentials::insert($result);
        } catch (\Exception $exception) {
            \Log::critical($exception->getMessage(), $exception->getTrace());
        }
    }

    private function getTokenId($oldTokenId)
    {
        if (is_null(Token::select('id')->where('temp_id', $oldTokenId)->first())) {
            return null;
        }

        return Token::select('id')->where('temp_id', $oldTokenId)->first()->id;
    }

    private function indexesToSnakeCase($subject = [])
    {
        foreach ($subject as $key => $value) {
            if (Str::snake($key) === $key) {
                continue;
            }

            $subject[Str::snake($key)] = $subject[$key];
            unset($subject[$key]);
        }

        return $subject;
    }
}
