<?php

namespace App\JsonApi\V1\Leads;

use App\JsonApi\V1\Leads\Filters\WhereDateRange;
use App\JsonApi\V1\Leads\Filters\WhereTextSearch;
use App\JsonApi\V1\Leads\Filters\WhereJsonPath;
use App\JsonApi\V1\Leads\Filters\WhereJsonArrayContains;
use App\Models\Lead;
use LaravelJsonApi\Eloquent\SoftDeletes;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class LeadSchema extends Schema
{
    use SoftDeletes;

    /**
     * The model the schema corresponds to.
     */
    public static string $model = Lead::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make()->sortable(),
            Str::make('externalId', 'external_id')->sortable(),
            Str::make('name')->sortable(),
            Str::make('email')->sortable(),
            Str::make('phone'),
            ArrayHash::make('messengers'),
            ArrayHash::make('data'),
            ArrayHash::make('contacts'),
            Str::make('ipAddress', 'ip_address'),
            Str::make('city'),
            Str::make('country'),
            Number::make('status')->sortable(),
            Str::make('utmSource', 'utm_source')->sortable(),
            Str::make('utmMedium', 'utm_medium'),
            Str::make('utmCampaign', 'utm_campaign'),
            Str::make('utmContent', 'utm_content'),
            Str::make('utmTerm', 'utm_term'),
            Str::make('externalProjectId', 'external_project_id'),
            Str::make('externalSystem', 'external_system'),
            Str::make('externalEntity', 'external_entity'),
            Str::make('externalEntityId', 'external_entity_id'),
            Number::make('userId', 'user_id')->sortable(),
            Number::make('projectId', 'project_id')->sortable(),
            Number::make('quizId', 'quiz_id')->sortable(),
            Str::make('integrationStatus', 'integration_status'),
            ArrayHash::make('integrationData', 'integration_data'),
            Number::make('equalAnswerId', 'equal_answer_id'),
            Str::make('fingerprint'),
            Boolean::make('isTest', 'is_test'),
            Boolean::make('viewed'),
            Boolean::make('paid'),
            Boolean::make('blocked'),
            DateTime::make('createdAt')->sortable(),
            DateTime::make('updatedAt')->sortable(),
            DateTime::make('deletedAt'),
        ];
    }

    /**
     * Get the JSON:API resource type.
     */
    public static function type(): string
    {
        return 'leads';
    }

    /**
     * The default pagination parameters.
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 100];

    /**
     * Get the pagination parameters for the resource.
     */
    public function pagination(): ?\LaravelJsonApi\Contracts\Pagination\Paginator
    {
        return PagePagination::make();
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            // Основные фильтры
            Where::make('external_entity_id'),
            Where::make('external_project_id'),
            Where::make('status'),
            Where::make('user_id'),
            Where::make('project_id'),
            Where::make('quiz_id'),

            // locations / city - фильтр по городам
            WhereIn::make('locations', 'city'),
            WhereIn::make('city', 'city'), // Альтернативное имя для совместимости

            // quizzes - фильтр по массиву quiz IDs
            WhereIn::make('quizzes', 'quiz_id'),

            // paid - фильтр по оплаченным/неоплаченным
            Where::make('paid'),

            // stage - фильтр по стадиям (хранится в data JSONB)
            WhereJsonPath::make('stage', 'data', 'stage'),

            // user - фильтр по пользователю (алиас для user_id)
            Where::make('user', 'user_id'),

            // tags - фильтр по тегам (хранится в data JSONB как массив)
            WhereJsonArrayContains::make('tags', 'data', 'tags'),

            // integrations.status - фильтр по статусу интеграции
            // Используем подчеркивание вместо точки для совместимости с JSON:API
            Where::make('integrations_status', 'integration_status'),

            // integrations.type - фильтр по типу интеграции (хранится в integration_data)
            WhereJsonPath::make('integrations_type', 'integration_data', 'type'),

            // interval / range - фильтр по датам (created, updated)
            // Поддержка формата V1: interval[created][startDate], interval[created][endDate]
            // Поддержка формата V2: range[created][gte], range[created][lte]
            // Используем подчеркивание вместо точки для совместимости с JSON:API
            WhereDateRange::make('interval_created', 'created_at'),
            WhereDateRange::make('range_created', 'created_at'),
            WhereDateRange::make('interval_updated', 'updated_at'),
            WhereDateRange::make('range_updated', 'updated_at'),

            // query - текстовый поиск
            WhereTextSearch::make('query', [
                'name',
                'email',
                'phone',
                'city',
                'external_id',
            ]),
        ];
    }

    /**
     * Get the resource relationships.
     * 
     * Note: Removed 'user' relationship because UserSchema is not registered in the Server.
     * The userId field is already exposed as a field in the schema.
     */
    public function relationships(): array
    {
        return [];
    }

}
