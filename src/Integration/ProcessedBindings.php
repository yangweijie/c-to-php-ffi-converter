<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Integration;

use Yangweijie\CWrapper\Analyzer\FunctionSignature;
use Yangweijie\CWrapper\Analyzer\StructureDefinition;

/**
 * Represents processed bindings from FFIGen output
 */
class ProcessedBindings
{
    /**
     * @param array<FunctionSignature> $functions Processed function signatures
     * @param array<StructureDefinition> $structures Processed structure definitions
     * @param array<string, mixed> $constants Processed constants
     */
    public function __construct(
        public readonly array $functions,
        public readonly array $structures,
        public readonly array $constants
    ) {
    }
}