<?php
namespace App\Jobs\MongoImport;

use App\Models\Lead;
use Illuminate\Support\Arr;

class ImportLeadsJob extends MongoImportJob
{
    protected $storedItems = [];

    protected $collectionName = 'Answer';

    public function handle()
    {
        $limit = 1000;
        $offset = 0;

        while (true) {
            $mongoCollection = $this->getMongoData($limit, $offset);

            $leads = [];
            $break = true;
            foreach ($mongoCollection as $item) {
                $break = false;
                if (Arr::exists($this->storedItems, $item['_id']->__toString())) {
                    continue;
                }

                if (is_null($item['quizId'])) {
                    continue;
                }

                $leads[$item['_id']->__toString()] = $this->createRow($item);
            }

            $this->removeStoredItems($leads);

            if (!empty($leads) && $this->insertRows($leads) === true) {
                $this->storedItems[] = array_keys($leads);
            }

            if ($break) {
                break;
            }

            $offset += $limit;
        }
    }

    public function createRow($data = [])
    {
        $row = [];
        $row['external_id'] = $data['_id']->__toString();
        $row['name'] = null;
        $row['email'] = null;
        $row['phone'] = null;
        $row['messengers'] = null;

        if (Arr::exists($data, 'contacts') || Arr::exists($data, 'contacts2')) {
            $contact = Arr::exists($data, 'contacts') ? $data['contacts'] : $data['contacts2'];

            $row['name'] = $contact['name'] ?? null;
            $row['email'] = $contact['email'] ?? null;
            $row['phone'] = $contact['phone'] ?? null;

            $additionalContactInfo = Arr::exists($contact, 'messengers') ? $contact['messengers'] : [];

            if (!empty($additionalContactInfo)) {
                $row['messengers'] = json_encode($additionalContactInfo);
            }
        }

        $row['ip_address'] = null;
        $row['data'] = [];
        $row['utm_source'] = null;
        $row['utm_medium'] = null;
        $row['utm_campaign'] = null;
        $row['utm_content'] = null;
        $row['utm_term'] = null;

        if (Arr::exists($data, 'raw')) {
            $row['data']['raw'] = $data['raw'];
        }

        if (Arr::exists($data, 'answer')) {
            $row['data']['answer'] = $data['answer'];
        }

        if (Arr::exists($data, 'answer2')) {
            $row['data']['answer2'] = $data['answer2'];
        }

        if (Arr::exists($data, 'result')) {
            $row['data']['result'] = $data['result'];
        }

        if (Arr::exists($data, 'extra')) {
            $extra = $data['extra'];
            $row['ip_address'] = $extra['ip'] ?? null;
            $utms = Arr::exists($extra, 'utm') ? $extra['utm'] : [];

            if (!empty($utms)) {
                $row['utm_source'] = $utms['utm_source'] ?? null;
                $row['utm_medium'] = $utms['utm_medium'] ?? null;
                $row['utm_campaign'] = $utms['utm_campaign'] ?? null;
                $row['utm_content'] = $utms['utm_content'] ?? null;
                $row['utm_term'] = $utms['utm_term'] ?? null;
            }

            unset($extra['ip'], $extra['utm']);
            $row['data']['extra'] = $extra;
        }

        $row['data'] = json_encode($row['data']);
        $row['external_system'] = 'Marquiz';
        $row['external_entity'] = 'quiz';
        $row['external_entity_id'] = $data['quizId']->__toString();
        $row['external_project_id'] = $data['projectId'] ?? null;
        $row['status'] = 0;
        $row['created_at'] = $data['created']->toDateTime();
        $row['updated_at'] = Arr::exists($data, 'modified') ? $data['modified']->toDateTime() : $row['created_at']->toDateTime();

        return $row;
    }

    private function removeStoredItems($items = [])
    {
        if (empty($items)) {
            return;
        }

        Lead::select('external_id')->whereIn('external_id', array_keys($items))->get()
            ->map(function ($item) use ($items) {
                unset($items[$item->external_id]);
            });
    }

    public function insertRows($rows)
    {
        try {
            Lead::insert($rows);
            return true;
        } catch (\Exception $exception) {
            \Log::critical($exception->getMessage(), $exception->getTrace());
        }
    }
}
