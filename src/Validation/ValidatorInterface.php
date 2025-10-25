<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Validation;

/**
 * Interface for parameter validation
 */
interface ValidatorInterface
{
    /**
     * Validate a value against a validation rule
     *
     * @param mixed $value Value to validate
     * @param ValidationRule $rule Validation rule to apply
     * @return ValidationResult Validation result
     */
    public function validate(mixed $value, ValidationRule $rule): ValidationResult;
}