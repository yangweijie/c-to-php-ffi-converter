<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Validation;

/**
 * Represents the result of validation
 */
class ValidationResult
{
    /**
     * @param bool $isValid Whether validation passed
     * @param array<string> $errors Validation error messages
     * @param mixed $convertedValue Converted value (if applicable)
     */
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors = [],
        public readonly mixed $convertedValue = null
    ) {
    }
}