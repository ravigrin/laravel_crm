<?php
namespace App\Jobs\MongoImport;
use App\Models\Oauth\Service;

class ImportOAuthServices extends MongoImportJob
{
    protected $collectionName = 'OAuthService';
    protected $storedItems = [];

    public function handle()
    {
        $limit = 1000;
        $offset = 0;
        while (true) {
            $mongoCollection = $this->getMongoData($limit, $offset);

            $break = true;
            $services = [];

            foreach ($mongoCollection as $item) {
                $break = false;

                if (array_key_exists($item['_id']->__toString(), $this->storedItems)) {
                    continue;
                }

                $services[$item['_id']->__toString()] = $this->createRow($item);
            }

            $this->removeStoredItems($services);

            if (!empty($services) && $this->insertRows($services) === true) {
                $this->storedItems[] = array_keys($services);
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
            'client_id' => $item['clientId'],
            'client_secret' => $item['clientSecret'],
            'redirect_url' => $item['redirectUri'],
        ];
    }

    protected function removeStoredItems($items = [])
    {
        if (empty($items)) {
            return;
        }

        Service::select('temp_id')->whereIn('temp_id', array_keys($items))->get()
            ->map(function ($item) use ($items) {
                unset($items[$item->external_id]);
            });
    }

    public function insertRows($rows)
    {
        try {
            Service::insert($rows);
            return true;
        } catch (\Exception $exception) {
            \Log::critical($exception->getMessage(), $exception->getTrace());
        }
    }
}
