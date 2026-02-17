<?php

namespace App\JsonApi\V1\Leads\Filters;

use LaravelJsonApi\Eloquent\Contracts\Filter;
use Illuminate\Database\Eloquent\Builder;

class WhereJsonPath implements Filter
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
        return true;
    }

    public function apply($query, $value)
    {
        $model = $query->getModel();
        $column = $model->qualifyColumn($this->jsonColumn);
        
        // For PostgreSQL JSONB, use JSON path query
        // Format: data->>'stage' = 'value' or data->'stage' = '"value"'
        return $query->where($column . '->>\'' . $this->jsonPath . '\'', '=', $value);
    }
}

