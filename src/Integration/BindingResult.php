<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Integration;

/**
 * Represents the result of FFIGen binding generation
 */
class BindingResult
{
    /**
     * @param string $constantsFile Path to generated constants.php file
     * @param string $methodsFile Path to generated Methods.php file
     * @param bool $success Whether generation was successful
     * @param array<string> $errors Any errors that occurred
     */
    public function __construct(
        public readonly string $constantsFile,
        public readonly string $methodsFile,
        public readonly bool $success,
        public readonly array $errors = []
    ) {
    }
}