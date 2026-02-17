<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Validators\EmailTemplateValidator;
use App\Models\Email;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    use EmailTemplateValidator;

    /**
     * @OA\Post(
     *     path="/api/settings/email/template",
     *     summary="Создание нового шаблона email",
     *     tags={"Settings", "Email Templates"},
     *     @OA\Response(response="200", description="Успешное создание шаблона"),
     *     @OA\Response(response="400", description="Ошибка валидации"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера"),
     * )
     */
    public function store(Request $request)
    {
        $this->validateSaveTemplate($request);

        try {
            $template = Email::create($request->all());
            return $this->respondSuccess(['message' => 'Template saved', 'template' => $template]);
        } catch (\Exception $exception) {
            \Log::critical($exception->getMessage(), $exception->getTrace());
            return $this->respondError(['message' => 'Something went wrong']);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/settings/email/template",
     *     summary="Получение шаблона email по ID",
     *     tags={"Settings", "Email Templates"},
     *     @OA\Response(response="200", description="Успешное получение данных"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера"),
     * )
     */
    public function show(Request $request)
    {
        $this->validateGetTemplate($request);
        $data = $request->all();

        try {
            $template = Email::where('template_id', $data['template_id']);
            return $this->respondSuccess(['template' => $template]);
        } catch (\Exception $exception) {
            \Log::critical($exception->getMessage(), $exception->getTrace());
            return $this->respondError(['message' => 'Something went wrong']);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/settings/email/template",
     *     summary="Обновление шаблона email",
     *     tags={"Settings", "Email Templates"},
     *     @OA\Response(response="200", description="Успешное обновление данных"),
     *     @OA\Response(response="404", description="Шаблон не найден"),
     *     @OA\Response(response="500", description="Ошибка при обновлении"),
     * )
     */
    public function update(Request $request)
    {
        $this->validateUpdateTemplate($request);
        $data = $request->all();

        try {
            $template = Email::where('template_id', $data['template_id'])->firstOrFail();
            unset($data['template_id']);
            $template->update($data);
            return $this->respondSuccess(['template' => $template]);
        } catch (\Exception $exception) {
            \Log::critical($exception->getMessage(), $exception->getTrace());
            return $this->respondError(['message' => 'Something went wrong']);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/email/template",
     *     summary="Удаление шаблонов email",
     *     tags={"Settings", "Email Templates"},
     *     @OA\Response(response="200", description="Успешное удаление"),
     *     @OA\Response(response="500", description="Ошибка при удалении"),
     * )
     */
    public function destroy(Request $request)
    {
        $this->validateDeleteTemplate($request);
        $data = $request->all();

        try {
            Email::whereIn('template_id', $data['template_ids'])
                ->update(['deleted_at' => date('Y-m-d H:i:s')]);
            return $this->respondSuccess(['message' => 'Templates deleted', 'template_ids' => $data['template_ids']]);
        } catch (\Exception $exception) {
            \Log::critical($exception->getMessage(), $exception->getTrace());
            return $this->respondError(['message' => 'Something went wrong']);
        }
    }
}
