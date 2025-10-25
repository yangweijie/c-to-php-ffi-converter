<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Validation;

use Yangweijie\CWrapper\Exception\ValidationException;

/**
 * Reports validation errors with detailed context
 */
class ValidationErrorReporter
{
    /**
     * Create a detailed validation exception from validation result
     */
    public function createException(ValidationResult $result, string $context = ''): ValidationException
    {
        if ($result->isValid) {
            throw new \InvalidArgumentException("Cannot create exception from valid validation result");
        }

        $message = $this->formatErrorMessage($result->errors, $context);
        return new ValidationException($message);
    }

    /**
     * Format error messages with context
     */
    public function formatErrorMessage(array $errors, string $context = ''): string
    {
        if (empty($errors)) {
            return "Validation failed";
        }

        $message = "Validation failed";
        
        if (!empty($context)) {
            $message .= " for {$context}";
        }

        $message .= ":\n";
        
        foreach ($errors as $index => $error) {
            $message .= "  " . ($index + 1) . ". {$error}\n";
        }

        return rtrim($message);
    }

    /**
     * Create detailed error report for function parameter validation
     */
    public function createFunctionParameterReport(
        ValidationResult $result,
        string $functionName,
        array $parameterNames = [],
        array $parameterTypes = []
    ): string {
        if ($result->isValid) {
            return "All parameters valid for function {$functionName}";
        }

        $report = "Parameter validation failed for function {$functionName}:\n\n";
        
        if (!empty($parameterTypes)) {
            $report .= "Expected signature:\n";
            $report .= "  {$functionName}(";
            
            $params = [];
            foreach ($parameterTypes as $index => $type) {
                $name = $parameterNames[$index] ?? "param{$index}";
                $params[] = "{$type} {$name}";
            }
            
            $report .= implode(', ', $params) . ")\n\n";
        }

        $report .= "Errors:\n";
        foreach ($result->errors as $index => $error) {
            $report .= "  " . ($index + 1) . ". {$error}\n";
        }

        return $report;
    }

    /**
     * Create suggestion for fixing validation errors
     */
    public function createSuggestion(ValidationResult $result, string $cType): string
    {
        if ($result->isValid) {
            return "";
        }

        $suggestions = [];
        
        foreach ($result->errors as $error) {
            if (str_contains($error, 'Type validation failed')) {
                $suggestions[] = "Ensure the parameter is of the correct PHP type for C type '{$cType}'";
            }
            
            if (str_contains($error, 'below minimum') || str_contains($error, 'above maximum')) {
                $suggestions[] = "Check that the value is within the valid range for C type '{$cType}'";
            }
            
            if (str_contains($error, 'Parameter count mismatch')) {
                $suggestions[] = "Verify you are passing the correct number of parameters";
            }
            
            if (str_contains($error, 'cannot be safely converted')) {
                $suggestions[] = "Use explicit type casting or provide a value that can be safely converted";
            }
        }

        if (empty($suggestions)) {
            $suggestions[] = "Review the parameter requirements for C type '{$cType}'";
        }

        return "Suggestions:\n  - " . implode("\n  - ", array_unique($suggestions));
    }
}