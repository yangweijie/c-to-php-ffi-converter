<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Generator;

/**
 * Represents a generated wrapper class
 */
class WrapperClass
{
    /**
     * @param string $name Class name
     * @param string $namespace Class namespace
     * @param array<string> $methods Generated methods
     * @param array<string> $properties Generated properties
     * @param array<string, mixed> $constants Generated constants
     */
    public function __construct(
        public readonly string $name,
        public readonly string $namespace,
        public readonly array $methods,
        public readonly array $properties,
        public readonly array $constants
    ) {
    }
}