<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Generator;

use Yangweijie\CWrapper\Documentation\Documentation;

/**
 * Represents generated wrapper code
 */
class GeneratedCode
{
    /**
     * @param array<WrapperClass> $classes Generated wrapper classes
     * @param array<string> $interfaces Generated interfaces
     * @param array<string> $traits Generated traits
     * @param Documentation $documentation Generated documentation
     */
    public function __construct(
        public readonly array $classes,
        public readonly array $interfaces,
        public readonly array $traits,
        public readonly Documentation $documentation
    ) {
    }
}