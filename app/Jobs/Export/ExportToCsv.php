<?php
namespace App\Jobs\Export;

use App\Exceptions\ExportException;
use Illuminate\Support\Facades\Storage;

class ExportToCsv extends Export
{
    protected $fields = ['name', 'phone', 'email', 'created_at', 'external_entity_id as entity_id',
        'data->source as sources', 'data->answers as answers', 'data->result as result',
        'messengers->vk as vk', 'messengers->telegram as telegram', 'messengers->watsapp as watsapp',
        'messengers->viber as viber', 'messengers->skype as skype', 'messengers->instagram as instagram',
    ];

    public function handle()
    {
        if ($this->prepareLeads()->count()) {
            try {
                $file = $this->path() . '/' . $this->filename();
                $handle = fopen('php://temp', 'w');
                $index = 0;
                $this->prepareLeads()->chunk(config('export.chunk_large_collections_size'))
                    ->each(function ($item) use ($file, $handle, &$index) {
                        $item->each(function ($row) use ($file, $handle, &$index) {
                            if ($index === 0) {
                                fputcsv($handle, array_keys($row->toArray()));
                            }
                            fputcsv($handle, $row->toArray());

                            Storage::put($file, $handle);
                            $index++;
                        });
                    });

                fclose($handle);

                $this->handleFinishEvent([
                    'entity_id' => $this->entityId,
                    'is_project' => $this->isProject,
                    'filename' => $this->filename(),
                ]);
            } catch (ExportException $exception) {
                \Log::critical($exception->getMessage(), $exception->getTrace());
            }
        }
    }
}
