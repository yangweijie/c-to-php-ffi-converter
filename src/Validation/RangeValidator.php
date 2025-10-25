<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Validation;

/**
 * Validates parameter ranges and constraints
 */
class RangeValidator implements ValidatorInterface
{
    /**
     * Validate a value against a range validation rule
     */
    public function validate(mixed $value, ValidationRule $rule): ValidationResult
    {
        if ($rule->type !== 'range') {
            return new ValidationResult(false, ["RangeValidator can only handle 'range' type rules"]);
        }

        $parameters = $rule->parameters;
        
        // Handle different range validation types
        if (isset($parameters['min']) || isset($parameters['max'])) {
            return $this->validateNumericRange($value, $parameters);
        }
        
        if (isset($parameters['length_min']) || isset($parameters['length_max'])) {
            return $this->validateLengthRange($value, $parameters);
        }
        
        if (isset($parameters['allowed_values'])) {
            return $this->validateAllowedValues($value, $parameters);
        }
        
        if (isset($parameters['pattern'])) {
            return $this->validatePattern($value, $parameters);
        }

        return new ValidationResult(false, ["No valid range constraints specified"]);
    }

    /**
     * Validate numeric range (min/max values)
     */
    private function validateNumericRange(mixed $value, array $parameters): ValidationResult
    {
        if (!is_numeric($value)) {
            return new ValidationResult(false, ["Value must be numeric for range validation"]);
        }

        $numericValue = is_string($value) ? (float) $value : $value;
        $errors = [];

        if (isset($parameters['min']) && $numericValue < $parameters['min']) {
            $errors[] = "Value {$numericValue} is below minimum {$parameters['min']}";
        }

        if (isset($parameters['max']) && $numericValue > $parameters['max']) {
            $errors[] = "Value {$numericValue} is above maximum {$parameters['max']}";
        }

        return new ValidationResult(empty($errors), $errors, $numericValue);
    } 
   /**
     * Validate length range (for strings, arrays)
     */
    private function validateLengthRange(mixed $value, array $parameters): ValidationResult
    {
        $length = 0;
        
        if (is_string($value)) {
            $length = strlen($value);
        } elseif (is_array($value)) {
            $length = count($value);
        } elseif (is_countable($value)) {
            $length = count($value);
        } else {
            return new ValidationResult(false, ["Value must be string, array, or countable for length validation"]);
        }

        $errors = [];

        if (isset($parameters['length_min']) && $length < $parameters['length_min']) {
            $errors[] = "Length {$length} is below minimum {$parameters['length_min']}";
        }

        if (isset($parameters['length_max']) && $length > $parameters['length_max']) {
            $errors[] = "Length {$length} is above maximum {$parameters['length_max']}";
        }

        return new ValidationResult(empty($errors), $errors, $value);
    }

    /**
     * Validate against allowed values (enum-like validation)
     */
    private function validateAllowedValues(mixed $value, array $parameters): ValidationResult
    {
        $allowedValues = $parameters['allowed_values'];
        
        if (!is_array($allowedValues)) {
            return new ValidationResult(false, ["allowed_values must be an array"]);
        }

        if (in_array($value, $allowedValues, true)) {
            return new ValidationResult(true, [], $value);
        }

        $allowedStr = implode(', ', array_map('strval', $allowedValues));
        return new ValidationResult(false, ["Value must be one of: {$allowedStr}"]);
    }

    /**
     * Validate against regex pattern
     */
    private function validatePattern(mixed $value, array $parameters): ValidationResult
    {
        if (!is_string($value)) {
            return new ValidationResult(false, ["Value must be string for pattern validation"]);
        }

        $pattern = $parameters['pattern'];
        
        if (!is_string($pattern)) {
            return new ValidationResult(false, ["Pattern must be a string"]);
        }

        if (preg_match($pattern, $value)) {
            return new ValidationResult(true, [], $value);
        }

        return new ValidationResult(false, ["Value does not match required pattern"]);
    }
}