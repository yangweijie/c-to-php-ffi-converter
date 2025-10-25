<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Generator;

/**
 * Maps C types to PHP types and generates validation code
 */
class TypeMapper
{
    /**
     * @var array<string, string> Mapping from C types to PHP types
     */
    private array $typeMap = [
        'void' => 'void',
        'int' => 'int',
        'long' => 'int',
        'short' => 'int',
        'char' => 'int',
        'unsigned int' => 'int',
        'unsigned long' => 'int',
        'unsigned short' => 'int',
        'unsigned char' => 'int',
        'float' => 'float',
        'double' => 'float',
        'char*' => 'string',
        'const char*' => 'string',
        'void*' => 'mixed',
        'size_t' => 'int',
        'bool' => 'bool',
        '_Bool' => 'bool',
    ];

    /**
     * Map C type to PHP type
     *
     * @param string $cType C type name
     * @return string PHP type name
     */
    public function mapCTypeToPhp(string $cType): string
    {
        // Clean up the type (remove extra spaces, etc.)
        $cleanType = trim($cType);
        
        // Handle pointer types
        if (str_ends_with($cleanType, '*')) {
            $baseType = trim(substr($cleanType, 0, -1));
            if ($baseType === 'char' || $baseType === 'const char') {
                return 'string';
            }
            return 'mixed'; // Generic pointer type
        }
        
        // Handle array types
        if (str_contains($cleanType, '[')) {
            return 'array';
        }
        
        // Direct mapping
        if (isset($this->typeMap[$cleanType])) {
            return $this->typeMap[$cleanType];
        }
        
        // Handle struct/union types
        if (str_starts_with($cleanType, 'struct ') || str_starts_with($cleanType, 'union ')) {
            return 'mixed'; // FFI CData object
        }
        
        // Default to mixed for unknown types
        return 'mixed';
    }

    /**
     * Map PHP type to C type for FFI calls
     *
     * @param string $phpType PHP type name
     * @return string C type name
     */
    public function mapPhpTypeToC(string $phpType): string
    {
        $reverseMap = [
            'int' => 'int',
            'float' => 'double',
            'string' => 'char*',
            'bool' => 'bool',
            'array' => 'void*',
            'mixed' => 'void*',
        ];
        
        return $reverseMap[$phpType] ?? 'void*';
    }

    /**
     * Generate validation code for a parameter
     *
     * @param string $paramName Parameter name
     * @param string $cType C type
     * @return string Validation code
     */
    public function generateValidation(string $paramName, string $cType): string
    {
        $phpType = $this->mapCTypeToPhp($cType);
        $validation = '';
        
        switch ($phpType) {
            case 'int':
                $validation .= "        if (!is_int(\${$paramName})) {\n";
                $validation .= "            throw new \\InvalidArgumentException('Parameter {$paramName} must be an integer');\n";
                $validation .= "        }\n";
                break;
                
            case 'float':
                $validation .= "        if (!is_numeric(\${$paramName})) {\n";
                $validation .= "            throw new \\InvalidArgumentException('Parameter {$paramName} must be numeric');\n";
                $validation .= "        }\n";
                break;
                
            case 'string':
                $validation .= "        if (!is_string(\${$paramName})) {\n";
                $validation .= "            throw new \\InvalidArgumentException('Parameter {$paramName} must be a string');\n";
                $validation .= "        }\n";
                break;
                
            case 'bool':
                $validation .= "        if (!is_bool(\${$paramName})) {\n";
                $validation .= "            throw new \\InvalidArgumentException('Parameter {$paramName} must be a boolean');\n";
                $validation .= "        }\n";
                break;
                
            case 'array':
                $validation .= "        if (!is_array(\${$paramName})) {\n";
                $validation .= "            throw new \\InvalidArgumentException('Parameter {$paramName} must be an array');\n";
                $validation .= "        }\n";
                break;
        }
        
        return $validation;
    }

    /**
     * Check if a C type is a pointer type
     *
     * @param string $cType C type name
     * @return bool True if pointer type
     */
    public function isPointerType(string $cType): bool
    {
        return str_ends_with(trim($cType), '*');
    }

    /**
     * Check if a C type is a struct or union type
     *
     * @param string $cType C type name
     * @return bool True if struct/union type
     */
    public function isStructType(string $cType): bool
    {
        $cleanType = trim($cType);
        return str_starts_with($cleanType, 'struct ') || str_starts_with($cleanType, 'union ');
    }
}