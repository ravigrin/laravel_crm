<?php

namespace App\Interfaces;

/**
 * Field mapper interface
 */
interface FieldMapperInterface
{
    /**
     * Map a field
     *
     * @param string $type
     * @param mixed $value
     * @return mixed
     */
    public function mapField(string $type, mixed $value): mixed;
}
