<?php

namespace App\Http\Controllers;

use App\Http\Validators\ImportExportValidator;
use App\Jobs\Export\ExportToCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    use ImportExportValidator;

    protected $tokenRequiredActions = ['export', 'download'];

    /**
     * @OA\Get(
     *     path="/api/export/export",
     *     summary="Запуск экспорта данных",
     *     tags={"Export"},
     *     @OA\Response(response="200", description="Экспорт запущен"),
     *     @OA\Response(response="400", description="Ошибка валидации"),
     * )
     */
    public function export(Request $request)
    {
        $this->validateExport($request);
        $data = $request->all();

        //if entity_id and project_id are existing at the same time, entity_id always has highest priority
        $entityId = $data['external_entity_id'] ?? $data['external_project_id'];
        $isProject = ! Arr::exists($data, 'external_entity_id');

        $this->dispatch((new ExportToCsv($entityId, $data, $isProject))->onQueue('export_queue'));

        return $this->respondSuccess(['message' => 'Export started']);
    }

    /**
     * @OA\Get(
     *     path="/api/export/download",
     *     summary="Скачивание экспортированного файла",
     *     tags={"Export"},
     *     @OA\Response(response="200", description="Успешная загрузка файла"),
     *     @OA\Response(response="404", description="Файл не найден"),
     * )
     */
    public function download(Request $request)
    {
        $this->validateDownload($request);
        $data = $request->query();

        $path = config('export.export_path') . '/' . $data['type'] . '/' . $data['filename'];

        if (! Storage::exists($path)) {
            abort(404);
        }

        return Storage::download($path);
    }
}
