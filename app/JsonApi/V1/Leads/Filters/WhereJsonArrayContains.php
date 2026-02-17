<?php

namespace App\JsonApi\V1\Leads\Filters;

use LaravelJsonApi\Eloquent\Contracts\Filter;
use Illuminate\Database\Eloquent\Builder;

class WhereJsonArrayContains implements Filter
{
    private string $name;
    private string $jsonColumn;
    private string $jsonPath;

    public static function make(string $name, string $jsonColumn, string $jsonPath): self
    {
        return new static($name, $jsonColumn, $jsonPath);
    }

    public function __construct(string $name, string $jsonColumn, string $jsonPath)
    {
        $this->name = $name;
        $this->jsonColumn = $jsonColumn;
        $this->jsonPath = $jsonPath;
    }

    public function key(): string
    {
        return $this->name;
    }

    public function isSingular(): bool
    {
        return false;
    }

    public function apply($query, $value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $model = $query->getModel();
        $column = $model->qualifyColumn($this->jsonColumn);

        // For PostgreSQL JSONB array contains
        // Format: data->'tags' @> '["tag1"]'::jsonb
        return $query->where(function ($q) use ($column, $value) {
            foreach ($value as $item) {
                // Use raw SQL for JSONB array contains
                $q->orWhereRaw($column . "->'" . $this->jsonPath . "' @> ?::jsonb", [json_encode([$item])]);
            }
        });
    }
}

