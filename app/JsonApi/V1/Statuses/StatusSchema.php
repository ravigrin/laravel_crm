<?php

namespace App\JsonApi\V1\Statuses;

use App\Models\Status;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Schema;

class StatusSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Status::class;

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make()->sortable(),
            Number::make('userId')->on('user_id')->sortable(),
            Number::make('projectId')->on('project_id')->sortable(),
            Str::make('code')->sortable(),
            Str::make('name')->sortable(),
            Number::make('order')->sortable(),
            Str::make('color'),
            Number::make('isDefault')->on('is_default'),
            DateTime::make('createdAt')->on('created_at')->sortable(),
            DateTime::make('updatedAt')->on('updated_at')->sortable(),
        ];
    }

    /**
     * Get the JSON:API resource type.
     */
    public static function type(): string
    {
        return 'statuses';
    }

    /**
     * The default pagination parameters.
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 100];

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            // Разрешаем фильтрацию по основным полям
            Where::make('external_entity_id'),
            Where::make('status_id'),
            Where::make('user_id'),
            Where::make('project_id'),
        ];
    }

}
