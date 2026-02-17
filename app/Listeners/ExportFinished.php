<?php
namespace App\Listeners;

use App\Helpers\Crm;
use App\Jobs\Export\CleanUpExport;
use App\Jobs\NotifyByEmail;
use App\Events\ExportFinished as ExportFinishedEvent;
use Illuminate\Support\Arr;
use Firebase\JWT\JWT;

class ExportFinished
{
    public function handle(ExportFinishedEvent $event)
    {
        $entityId = $event->payload['entity_id'];
        $isProject = $event->payload['is_project'];
        $filename = $event->payload['filename'];

        dispatch(new NotifyByEmail(
            $this->getEmail($entityId, $isProject),
            ['link' => $this->buildUrl($isProject, $filename, $entityId)],
            'export_finished'
        ));
        $cleanJob = (new CleanUpExport($this->fullPath($isProject, $filename)))->delay(config('export.file_available_in'));
        dispatch($cleanJob);
    }

    public function getEmail($entityId, $isProject = false)
    {
        return $isProject ? Crm::getEmailByExternalId(null, $entityId)
        : Crm::getEmailByExternalId($entityId, null);
    }

    public function buildUrl($isProject, $filename, $entityId)
    {
        $type = $isProject ? 'project' : 'entity';
        $keyParam = $isProject ? 'external_project_id' : 'external_entity_id';

        $token = $this->generateToken([$keyParam => $entityId]);

        return url('api/export/download?')
            . Arr::query(['type' => $type, 'filename' => $filename, 'token'=>$token]);
    }

    public function generateToken(array $params)
    {
        $payload = [
            'iat' => time(),
        ];

        $data = array_merge($payload, $params);
        $secret = getenv('JWT_SECRET');

        return JWT::encode($data, $secret);
    }

    public function fullPath($isProject, $filename)
    {
        $type = $isProject ? 'project' : 'entity';
        return config('export.export_path'). '/' . $type . '/' . $filename;
    }
}
