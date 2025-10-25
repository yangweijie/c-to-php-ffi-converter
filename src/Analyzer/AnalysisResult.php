<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Analyzer;

/**
 * Represents the result of analyzing a C project
 */
class AnalysisResult
{
    /**
     * @param array<FunctionSignature> $functions
     * @param array<StructureDefinition> $structures
     * @param array<string, mixed> $constants
     * @param array<string> $dependencies
     */
    public function __construct(
        public readonly array $functions,
        public readonly array $structures,
        public readonly array $constants,
        public readonly array $dependencies
    ) {
    }
}