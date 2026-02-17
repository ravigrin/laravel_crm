<?php

namespace App\Jobs;

use App\Enums\DefaultStatuses;
use App\Exceptions\ClientException;
use App\Helpers\Locale;
use App\Helpers\MailClient;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use App\Helpers\Crm;

class SendLeadStatistic extends Job
{
    /**
     * Default Job Entrypoint
     */
    public function handle()
    {
        $limit = 1000;
        $offset = 0;

        while (true) {
            $data = $this->prepareData($limit, $offset);

            if (empty($data)) {
                break;
            }

            foreach ($data as $entityId => $item) {
                $email = Crm::getEmailByExternalId($entityId);
                $this->sendEmail($email, $item);
            }

            $offset += $limit;
        }
    }

    /**
     * Returns qty of each status for each external entity;
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function prepareData($limit = 1000, $offset = 0)
    {
        $result = [];
        Lead::query()->select([
            \DB::raw('count(*) as status_count'),
            'leads.status',
            'leads.external_entity_id'
        ])->whereBetween('leads.created_at', [Carbon::now()->subDays(7), Carbon::now()])
            ->where('leads.is_test', false)
            ->groupBy('leads.status', 'leads.external_entity_id')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->each(function ($item) use (&$result) {
                if (!Arr::exists($result, $item->external_entity_id)) {
                    $result[$item->external_entity_id] = [];

                    $result[$item->external_entity_id] = Arr::add($result[$item->external_entity_id], $item->status->value, [
                        'label' => DefaultStatuses::getDescription($item->status->value),
                        'qty' => $item->status_count
                    ]);

                } else {
                    $result[$item->external_entity_id] = Arr::add($result[$item->external_entity_id], $item->status->value, [
                        'label' => DefaultStatuses::getDescription($item->status->value),
                        'qty' => $item->status_count
                    ]);
                }
            });

        return $result;
    }

    private function sendEmail($address, $data = [])
    {
        try {
            (new MailClient())->send(
                $address,
                Locale::getEmailTemplate('weekly_lead_stat'),
                $data
            );
        } catch (ClientException $exception) {
            \Log::critical($exception->getMessage(), $exception->getTrace());
        }
    }
}
