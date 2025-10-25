<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Exception;

use Throwable;

/**
 * Exception for validation-related errors
 */
class ValidationException extends FFIConverterException
{
    public static function invalidParameterType(string $parameter, string $expected, string $actual): self
    {
        return new self(
            message: "Invalid parameter type for '{$parameter}': expected {$expected}, got {$actual}",
            context: [
                'parameter' => $parameter,
                'expected_type' => $expected,
                'actual_type' => $actual
            ],
            recoverable: true,
            suggestion: "Ensure the parameter is of type {$expected}"
        );
    }

    public static function parameterOutOfRange(string $parameter, mixed $value, mixed $min = null, mixed $max = null): self
    {
        $message = "Parameter '{$parameter}' value {$value} is out of range";
        $context = ['parameter' => $parameter, 'value' => $value];
        $suggestion = "Provide a valid value for parameter '{$parameter}'";
        
        if ($min !== null && $max !== null) {
            $message .= " (expected: {$min} to {$max})";
            $context['min'] = $min;
            $context['max'] = $max;
            $suggestion .= " between {$min} and {$max}";
        } elseif ($min !== null) {
            $message .= " (minimum: {$min})";
            $context['min'] = $min;
            $suggestion .= " greater than or equal to {$min}";
        } elseif ($max !== null) {
            $message .= " (maximum: {$max})";
            $context['max'] = $max;
            $suggestion .= " less than or equal to {$max}";
        }

        return new self(
            message: $message,
            context: $context,
            recoverable: true,
            suggestion: $suggestion
        );
    }

    public static function invalidParameterCount(int $expected, int $actual): self
    {
        return new self(
            message: "Invalid parameter count: expected {$expected}, got {$actual}",
            context: ['expected_count' => $expected, 'actual_count' => $actual],
            recoverable: true,
            suggestion: "Provide exactly {$expected} parameters"
        );
    }

    public static function nullParameterNotAllowed(string $parameter): self
    {
        return new self(
            message: "Parameter '{$parameter}' cannot be null",
            context: ['parameter' => $parameter],
            recoverable: true,
            suggestion: "Provide a non-null value for parameter '{$parameter}'"
        );
    }

    public static function typeConversionFailed(mixed $value, string $fromType, string $toType, string $reason = null): self
    {
        $message = "Failed to convert value from {$fromType} to {$toType}";
        $context = [
            'value' => $value,
            'from_type' => $fromType,
            'to_type' => $toType
        ];
        
        if ($reason) {
            $message .= ": {$reason}";
            $context['reason'] = $reason;
        }

        return new self(
            message: $message,
            context: $context,
            recoverable: false,
            suggestion: "Ensure the value can be safely converted to {$toType}"
        );
    }

    public static function customValidationFailed(string $rule, mixed $value, string $message = null): self
    {
        $errorMessage = "Custom validation rule '{$rule}' failed for value: " . json_encode($value);
        if ($message) {
            $errorMessage .= " - {$message}";
        }

        return new self(
            message: $errorMessage,
            context: ['rule' => $rule, 'value' => $value, 'custom_message' => $message],
            recoverable: true,
            suggestion: "Check the value against the validation rule requirements"
        );
    }
}