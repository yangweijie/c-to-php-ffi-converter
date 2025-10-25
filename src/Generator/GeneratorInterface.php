<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Generator;

use Yangweijie\CWrapper\Integration\ProcessedBindings;

/**
 * Interface for code generation
 */
interface GeneratorInterface
{
    /**
     * Generate wrapper code from processed bindings
     *
     * @param ProcessedBindings $bindings Processed bindings to generate from
     * @return GeneratedCode Generated code result
     */
    public function generate(ProcessedBindings $bindings): GeneratedCode;
}