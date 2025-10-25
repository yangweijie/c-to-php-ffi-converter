<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Analyzer;

/**
 * Represents a C function signature
 */
class FunctionSignature
{
    /**
     * @param string $name Function name
     * @param string $returnType Return type
     * @param array<array{name: string, type: string}> $parameters Function parameters
     * @param array<string> $documentation Documentation comments
     */
    public function __construct(
        public readonly string $name,
        public readonly string $returnType,
        public readonly array $parameters,
        public readonly array $documentation = []
    ) {
    }
}