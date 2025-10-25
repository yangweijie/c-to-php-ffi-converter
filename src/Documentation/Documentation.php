<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Documentation;

/**
 * Represents generated documentation
 */
class Documentation
{
    /**
     * @param array<string> $phpDocComments Generated PHPDoc comments
     * @param string $readmeContent Generated README content
     * @param array<string> $examples Generated usage examples
     */
    public function __construct(
        public readonly array $phpDocComments,
        public readonly string $readmeContent,
        public readonly array $examples
    ) {
    }
}