<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Validation;

/**
 * Represents a validation rule
 */
class ValidationRule
{
    /**
     * @param string $type Rule type (e.g., 'type', 'range', 'custom')
     * @param array<string, mixed> $parameters Rule parameters
     */
    public function __construct(
        public readonly string $type,
        public readonly array $parameters = []
    ) {
    }
}