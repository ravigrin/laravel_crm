<?php
namespace App\Jobs\MongoImport;

use App\Models\OAuth\Token;

class ImportOAuthTokens extends MongoImportJob
{
    protected $collectionName = 'OAuthToken';
    protected $storedItems = [];

    public function handle()
    {
        $limit = 1000;
        $offset = 0;

        while (true) {
            $mongoCollection = $this->getMongoData($limit, $offset);
            $break = true;
            $tokens = [];

            foreach ($mongoCollection as $item) {
                $break = false;
                if (array_key_exists($item['_id']->__toString(), $this->storedItems)) {
                    continue;
                }

                $tokens[$item['_id']->__toString()] = $this->createRow($item);
            }

            $this->removeStoredItems($tokens);

            if (!empty($tokens) && $this->insertRows($tokens) === true) {
                $this->storedItems[] = array_keys($tokens);
            }

            if ($break) {
                break;
            }

            $offset += $limit;
        }
    }

    public function createRow($item)
    {
        return [
            'temp_id' => $item['_id']->__toString(),
            'service' => $item['service'],
            'domain' => $item['domain'],
            'access_token' => $item['accessToken'],
            'refresh_token' => $item['refreshToken'],
            'expires' => $item['expireAt']->toDateTime()
        ];
    }

    public function insertRows($rows)
    {
        try {
            Token::insert($rows);
        } catch (\Exception $exception) {
            \Log::critical($exception->getMessage(), $exception->getTrace());
        }
    }

    protected function removeStoredItems($items = [])
    {
        if (empty($items)) {
            return;
        }

        Token::select('temp_id')->whereIn('temp_id', array_keys($items))->get()
            ->map(function ($item) use ($items) {
                unset($items[$item->external_id]);
            });
    }
}
