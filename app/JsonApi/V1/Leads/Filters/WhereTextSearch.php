<?php

namespace App\JsonApi\V1\Leads\Filters;

use LaravelJsonApi\Eloquent\Contracts\Filter;
use Illuminate\Database\Eloquent\Builder;

class WhereTextSearch implements Filter
{
    private string $name;
    private array $searchableColumns;

    public static function make(string $name, array $searchableColumns): self
    {
        return new static($name, $searchableColumns);
    }

    public function __construct(string $name, array $searchableColumns)
    {
        $this->name = $name;
        $this->searchableColumns = $searchableColumns;
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
        if (empty($value)) {
            return $query;
        }

        $model = $query->getModel();
        $searchTerm = '%' . $value . '%';

        return $query->where(function ($q) use ($model, $searchTerm) {
            foreach ($this->searchableColumns as $column) {
                $qualifiedColumn = $model->qualifyColumn($column);
                $q->orWhere($qualifiedColumn, 'ILIKE', $searchTerm);
            }
        });
    }
}




