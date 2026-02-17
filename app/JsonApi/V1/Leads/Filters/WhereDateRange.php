<?php

namespace App\JsonApi\V1\Leads\Filters;

use LaravelJsonApi\Eloquent\Contracts\Filter;
use Illuminate\Database\Eloquent\Builder;

class WhereDateRange implements Filter
{
    private string $name;
    private string $column;

    public static function make(string $name, string $column): self
    {
        return new static($name, $column);
    }

    public function __construct(string $name, string $column)
    {
        $this->name = $name;
        $this->column = $column;
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
            return $query;
        }

        $model = $query->getModel();
        $column = $model->qualifyColumn($this->column);

        // Support both 'interval' format (startDate, endDate) and 'range' format (gte, lte)
        if (isset($value['startDate']) || isset($value['endDate'])) {
            // V1 format: { startDate: "...", endDate: "..." }
            if (isset($value['startDate'])) {
                $query->whereDate($column, '>=', $value['startDate']);
            }
            if (isset($value['endDate'])) {
                $query->whereDate($column, '<=', $value['endDate']);
            }
        } elseif (isset($value['gte']) || isset($value['lte'])) {
            // V2 format: { gte: "...", lte: "..." }
            if (isset($value['gte'])) {
                $query->whereDate($column, '>=', $value['gte']);
            }
            if (isset($value['lte'])) {
                $query->whereDate($column, '<=', $value['lte']);
            }
        }

        return $query;
    }
}




