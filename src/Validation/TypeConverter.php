<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Validation;

/**
 * Converts PHP types to C types for FFI calls
 */
class TypeConverter
{
    /**
     * Mapping of C types to their PHP equivalents and conversion rules
     */
    private const TYPE_MAPPINGS = [
        // Integer types
        'int' => ['php_type' => 'integer', 'min' => PHP_INT_MIN, 'max' => PHP_INT_MAX],
        'char' => ['php_type' => 'integer', 'min' => -128, 'max' => 127],
        'unsigned char' => ['php_type' => 'integer', 'min' => 0, 'max' => 255],
        'short' => ['php_type' => 'integer', 'min' => -32768, 'max' => 32767],
        'unsigned short' => ['php_type' => 'integer', 'min' => 0, 'max' => 65535],
        'long' => ['php_type' => 'integer', 'min' => PHP_INT_MIN, 'max' => PHP_INT_MAX],
        'unsigned long' => ['php_type' => 'integer', 'min' => 0, 'max' => PHP_INT_MAX],
        'long long' => ['php_type' => 'integer', 'min' => PHP_INT_MIN, 'max' => PHP_INT_MAX],
        'unsigned long long' => ['php_type' => 'integer', 'min' => 0, 'max' => PHP_INT_MAX],
        
        // Floating point types
        'float' => ['php_type' => 'double'],
        'double' => ['php_type' => 'double'],
        
        // String types
        'char*' => ['php_type' => 'string'],
        'const char*' => ['php_type' => 'string'],
        
        // Pointer types
        'void*' => ['php_type' => 'resource'],
        
        // Boolean type
        'bool' => ['php_type' => 'boolean'],
        '_Bool' => ['php_type' => 'boolean'],
    ];

    /**
     * Convert PHP value to appropriate C type
     */
    public function convert(mixed $value, string $cType): ValidationResult
    {
        $normalizedType = $this->normalizeCType($cType);
        $mapping = self::TYPE_MAPPINGS[$normalizedType] ?? null;

        if ($mapping === null) {
            return $this->handleUnknownType($value, $cType);
        }

        return $this->convertToType($value, $normalizedType, $mapping);
    }

    /**
     * Check if a PHP value is compatible with a C type
     */
    public function isCompatible(mixed $value, string $cType): bool
    {
        $result = $this->convert($value, $cType);
        return $result->isValid;
    }

    /**
     * Get the expected PHP type for a C type
     */
    public function getExpectedPhpType(string $cType): string
    {
        $normalizedType = $this->normalizeCType($cType);
        $mapping = self::TYPE_MAPPINGS[$normalizedType] ?? null;
        
        return $mapping['php_type'] ?? 'mixed';
    }

    /**
     * Normalize C type string (remove extra spaces, handle typedefs)
     */
    private function normalizeCType(string $cType): string
    {
        // Remove extra whitespace and normalize
        $normalized = trim(preg_replace('/\s+/', ' ', $cType));
        
        // Handle common type aliases
        $aliases = [
            'uint8_t' => 'unsigned char',
            'uint16_t' => 'unsigned short',
            'uint32_t' => 'unsigned int',
            'uint64_t' => 'unsigned long long',
            'int8_t' => 'char',
            'int16_t' => 'short',
            'int32_t' => 'int',
            'int64_t' => 'long long',
            'size_t' => 'unsigned long',
        ];

        return $aliases[$normalized] ?? $normalized;
    }

    /**
     * Convert value to specific type based on mapping
     */
    private function convertToType(mixed $value, string $cType, array $mapping): ValidationResult
    {
        $expectedPhpType = $mapping['php_type'];
        
        // Handle null values
        if ($value === null) {
            if (str_contains($cType, '*')) {
                return new ValidationResult(true, [], null);
            }
            return new ValidationResult(false, ["Null value not allowed for non-pointer type {$cType}"]);
        }

        return match ($expectedPhpType) {
            'integer' => $this->convertToInteger($value, $cType, $mapping),
            'double' => $this->convertToFloat($value, $cType),
            'string' => $this->convertToString($value, $cType),
            'boolean' => $this->convertToBoolean($value, $cType),
            'resource' => $this->convertToResource($value, $cType),
            default => new ValidationResult(false, ["Unknown PHP type: {$expectedPhpType}"])
        };
    }

    /**
     * Convert to integer with range checking
     */
    private function convertToInteger(mixed $value, string $cType, array $mapping): ValidationResult
    {
        if (is_int($value)) {
            $converted = $value;
        } elseif (is_float($value)) {
            $converted = (int) $value;
            if ($converted != $value) {
                return new ValidationResult(false, ["Float value {$value} cannot be safely converted to integer"]);
            }
        } elseif (is_string($value) && is_numeric($value)) {
            $converted = (int) $value;
        } elseif (is_bool($value)) {
            $converted = $value ? 1 : 0;
        } else {
            return new ValidationResult(false, ["Cannot convert " . gettype($value) . " to integer for type {$cType}"]);
        }

        // Check range if specified
        if (isset($mapping['min']) && $converted < $mapping['min']) {
            return new ValidationResult(false, ["Value {$converted} is below minimum {$mapping['min']} for type {$cType}"]);
        }
        
        if (isset($mapping['max']) && $converted > $mapping['max']) {
            return new ValidationResult(false, ["Value {$converted} is above maximum {$mapping['max']} for type {$cType}"]);
        }

        return new ValidationResult(true, [], $converted);
    }

    /**
     * Convert to float
     */
    private function convertToFloat(mixed $value, string $cType): ValidationResult
    {
        if (is_float($value) || is_int($value)) {
            return new ValidationResult(true, [], (float) $value);
        }
        
        if (is_string($value) && is_numeric($value)) {
            return new ValidationResult(true, [], (float) $value);
        }

        return new ValidationResult(false, ["Cannot convert " . gettype($value) . " to float for type {$cType}"]);
    }

    /**
     * Convert to string
     */
    private function convertToString(mixed $value, string $cType): ValidationResult
    {
        if (is_string($value)) {
            return new ValidationResult(true, [], $value);
        }
        
        if (is_null($value)) {
            return new ValidationResult(true, [], null);
        }
        
        if (is_scalar($value)) {
            return new ValidationResult(true, [], (string) $value);
        }

        return new ValidationResult(false, ["Cannot convert " . gettype($value) . " to string for type {$cType}"]);
    }

    /**
     * Convert to boolean
     */
    private function convertToBoolean(mixed $value, string $cType): ValidationResult
    {
        if (is_bool($value)) {
            return new ValidationResult(true, [], $value);
        }
        
        if (is_int($value)) {
            return new ValidationResult(true, [], $value !== 0);
        }
        
        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['true', '1', 'yes', 'on'])) {
                return new ValidationResult(true, [], true);
            }
            if (in_array($lower, ['false', '0', 'no', 'off', ''])) {
                return new ValidationResult(true, [], false);
            }
        }

        return new ValidationResult(false, ["Cannot convert " . gettype($value) . " to boolean for type {$cType}"]);
    }

    /**
     * Convert to resource (for pointers)
     */
    private function convertToResource(mixed $value, string $cType): ValidationResult
    {
        if (is_resource($value)) {
            return new ValidationResult(true, [], $value);
        }
        
        if (is_null($value)) {
            return new ValidationResult(true, [], null);
        }
        
        // Allow integers for pointer addresses
        if (is_int($value)) {
            return new ValidationResult(true, [], $value);
        }

        return new ValidationResult(false, ["Cannot convert " . gettype($value) . " to resource for type {$cType}"]);
    }

    /**
     * Handle unknown C types
     */
    private function handleUnknownType(mixed $value, string $cType): ValidationResult
    {
        // For unknown types, we'll be permissive but warn
        return new ValidationResult(true, [], $value);
    }
}