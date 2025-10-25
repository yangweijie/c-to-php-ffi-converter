<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Documentation;

use Yangweijie\CWrapper\Generator\GeneratedCode;

/**
 * Interface for documentation generation
 */
interface DocumentationGeneratorInterface
{
    /**
     * Generate documentation for generated code
     *
     * @param GeneratedCode $code Generated code to document
     * @return Documentation Generated documentation
     */
    public function generateDocumentation(GeneratedCode $code): Documentation;
}