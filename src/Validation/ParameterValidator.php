<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Validation;

use Yangweijie\CWrapper\Exception\ValidationException;

/**
 * Validates parameters for FFI wrapper methods
 */
class ParameterValidator implements ValidatorInterface
{
    private TypeConverter $typeConverter;
    private RangeValidator $rangeValidator;

    public function __construct(
        ?TypeConverter $typeConverter = null,
        ?RangeValidator $rangeValidator = null
    ) {
        $this->typeConverter = $typeConverter ?? new TypeConverter();
        $this->rangeValidator = $rangeValidator ?? new RangeValidator();
    }

    /**
     * Validate a value against a validation rule
     */
    public function validate(mixed $value, ValidationRule $rule): ValidationResult
    {
        return match ($rule->type) {
            'type' => $this->validateType($value, $rule->parameters),
            'range' => $this->rangeValidator->validate($value, $rule),
            'count' => $this->validateParameterCount($value, $rule->parameters),
            'custom' => $this->validateCustom($value, $rule->parameters),
            default => new ValidationResult(false, ["Unknown validation rule type: {$rule->type}"])
        };
    }

    /**
     * Validate multiple parameters against their expected types
     *
     * @param array<mixed> $parameters
     * @param array<string> $expectedTypes
     * @return ValidationResult
     */
    public function validateParameters(array $parameters, array $expectedTypes): ValidationResult
    {
        $errors = [];
        $convertedValues = [];

        // Check parameter count
        if (count($parameters) !== count($expectedTypes)) {
            return new ValidationResult(
                false,
                ["Parameter count mismatch. Expected " . count($expectedTypes) . ", got " . count($parameters)]
            );
        }

        // Validate each parameter
        foreach ($parameters as $index => $parameter) {
            $expectedType = $expectedTypes[$index] ?? 'mixed';
            $typeRule = new ValidationRule('type', ['expected_type' => $expectedType]);
            $result = $this->validateType($parameter, $typeRule->parameters);

            if (!$result->isValid) {
                $errors[] = "Parameter {$index}: " . implode(', ', $result->errors);
            } else {
                $convertedValues[] = $result->convertedValue ?? $parameter;
            }
        }

        return new ValidationResult(
            empty($errors),
            $errors,
            empty($errors) ? $convertedValues : null
        );
    }

    /**
     * Validate parameter type and convert if necessary
     */
    private function validateType(mixed $value, array $parameters): ValidationResult
    {
        $expectedType = $parameters['expected_type'] ?? 'mixed';
        
        if ($expectedType === 'mixed') {
            return new ValidationResult(true, [], $value);
        }

        // Check if type conversion is possible
        $conversionResult = $this->typeConverter->convert($value, $expectedType);
        
        if (!$conversionResult->isValid) {
            return new ValidationResult(
                false,
                ["Type validation failed: " . implode(', ', $conversionResult->errors)]
            );
        }

        return new ValidationResult(true, [], $conversionResult->convertedValue);
    }

    /**
     * Validate parameter count
     */
    private function validateParameterCount(mixed $value, array $parameters): ValidationResult
    {
        if (!is_array($value)) {
            return new ValidationResult(false, ["Expected array for parameter count validation"]);
        }

        $expectedCount = $parameters['expected_count'] ?? 0;
        $actualCount = count($value);

        if ($actualCount !== $expectedCount) {
            return new ValidationResult(
                false,
                ["Parameter count mismatch. Expected {$expectedCount}, got {$actualCount}"]
            );
        }

        return new ValidationResult(true);
    }

    /**
     * Validate using custom validation function
     */
    private function validateCustom(mixed $value, array $parameters): ValidationResult
    {
        $validator = $parameters['validator'] ?? null;
        
        if (!is_callable($validator)) {
            return new ValidationResult(false, ["Custom validator must be callable"]);
        }

        try {
            $result = $validator($value);
            
            if ($result === true) {
                return new ValidationResult(true);
            }
            
            if (is_string($result)) {
                return new ValidationResult(false, [$result]);
            }
            
            if (is_array($result)) {
                return new ValidationResult(false, $result);
            }
            
            return new ValidationResult(false, ["Custom validation failed"]);
        } catch (\Throwable $e) {
            return new ValidationResult(false, ["Custom validation error: " . $e->getMessage()]);
        }
    }
}