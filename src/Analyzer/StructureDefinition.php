<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Analyzer;

/**
 * Represents a C structure definition
 */
class StructureDefinition
{
    /**
     * @param string $name Structure name
     * @param array<array{name: string, type: string}> $fields Structure fields
     * @param bool $isUnion Whether this is a union type
     */
    public function __construct(
        public readonly string $name,
        public readonly array $fields,
        public readonly bool $isUnion = false
    ) {
    }
}