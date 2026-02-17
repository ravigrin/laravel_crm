<?php

namespace App\Jobs\Export;

use App\Events\ExportFinished;
use App\Jobs\Job;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Arr;

class Export extends Job
{
    use Queueable;

    protected $dateRange;
    protected $entityId;
    protected $isProject;
    protected $fields = ['*'];
    protected $filename;

    /**
     * Export constructor.
     * @param $entityId
     * @param array $dateRange
     * @param bool $isProject
     */
    public function __construct($entityId, $dateRange = [], $isProject = false)
    {
        $this->dateRange = $this->prepareDateRange($dateRange);
        $this->entityId = $entityId;
        $this->isProject = $isProject;
        $this->filename = $this->filename();
    }

    /**
     * Preparing leads collection
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    protected function prepareLeads()
    {
        $entityId = $this->entityId;
        $query = Lead::query();

        $query->when($this->isProject === true, function ($q) use ($entityId) {
            return $q->where('external_project_id', $entityId)->groupBy('external_project_id');
        });

        $query->when($this->isProject === false, function ($q) use ($entityId) {
            return $q->where('external_entity_id', $entityId);
        });

        $query->whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']]);
        $query->orderBy('created_at', 'DESC');

        return $query->get($this->fields);
    }

    /**
     * Preparing date range, which will be used for preparing collection
     * @param array $dateRange
     * @return array
     */
    private function prepareDateRange($dateRange = [])
    {
        $default = [
            'from' => Carbon::now()->subDays(80000), //@todo 30 days here
            'to' => Carbon::now(),
        ];

        if (!Arr::exists($dateRange, 'from') || strtotime($dateRange['from']) === false) {
            $dateRange['from'] = $default['from'];
        }

        if (!Arr::exists($dateRange, 'to') || strtotime($dateRange['to']) === false) {
            $dateRange['to'] = $default['to'];
        }

        return $dateRange;
    }

    /**
     * Get filename or generate it if filename not created yet
     */
    public function filename()
    {
        if ($this->filename) {
            return $this->filename;
        }

        return $this->filename = 'export_'. $this->entityId . '_' . Carbon::now() . '.csv';
    }

    /**
     * Specifing path depending on type of export: export by entity_id or export by project_id
     * @return string
     */
    public function path()
    {
        $subdir = $this->isProject ? 'project' : 'entity';
        return config('export.export_path') . '/' . $subdir;
    }

    /**
     * Export was finished, firing event
     * @param array $params
     */
    protected function handleFinishEvent($params = [])
    {
        event(new ExportFinished($params));
    }
}
