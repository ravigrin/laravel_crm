<?php

namespace App\Services;

use App\Interfaces\FieldMapperInterface;

/**
 * Field mapper service
 */
class FieldMapper implements FieldMapperInterface
{
    private array $mappingConfig;

    /**
     * Constructor
     *
     * @param array $mappingConfig
     */
    public function __construct(array $mappingConfig)
    {
        $this->mappingConfig = $mappingConfig;
    }

    /**
     * Map a field
     *
     * @param string $type
     * @param mixed $value
     * @return mixed
     */
    public function mapField(string $type, mixed $value): mixed
    {
        return match ($type) {
            'attr' => $this->mapAttribute($value),
            'credentials' => $this->mapCredentials($value),
            'data' => $this->mapData($value),
            default => throw new \InvalidArgumentException("Invalid field type: {$type}"),
        };
    }
}