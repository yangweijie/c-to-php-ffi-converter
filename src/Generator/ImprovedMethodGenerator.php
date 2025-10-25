<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Generator;

/**
 * Improved method generator that uses klitsche/ffigen type information
 * but generates simplified method names for object-oriented classes
 */
class ImprovedMethodGenerator
{
    private FFIGenOutputParser $parser;

    public function __construct(?FFIGenOutputParser $parser = null)
    {
        $this->parser = $parser ?? new FFIGenOutputParser();
    }

    /**
     * Generate improved method from klitsche/ffigen function info
     *
     * @param string $functionName Original function name
     * @param array $functionInfo Function info from klitsche/ffigen
     * @param string $className Target class name for method name simplification
     * @param string $generationType Generation type ('object' or 'functional')
     * @return string Generated method code
     */
    public function generateImprovedMethod(
        string $functionName,
        array $functionInfo,
        string $className,
        string $generationType = 'object'
    ): string {
        $methodName = $this->generateMethodName($functionName, $className, $generationType);
        $parameters = $this->generateParameterSignature($functionInfo['parameters']);
        $returnType = $this->normalizeReturnType($functionInfo['returnType']);
        
        $code = "    /**\n";
        $code .= "     * Wrapper for {$functionName}\n";
        
        // Generate parameter documentation
        foreach ($functionInfo['parameters'] as $param) {
            $paramType = $this->formatTypeForDoc($param['type'], $param['nullable']);
            $code .= "     * @param {$paramType} \${$param['name']}\n";
        }
        
        if ($returnType !== 'void') {
            $returnTypeDoc = $this->formatTypeForDoc($returnType, str_contains($returnType, '?'));
            $code .= "     * @return {$returnTypeDoc}\n";
        }
        
        $code .= "     */\n";
        $code .= "    public static function {$methodName}({$parameters})";
        
        if ($returnType !== 'void') {
            $code .= ": {$returnType}";
        }
        
        $code .= "\n    {\n";
        
        // Generate FFI call
        $paramNames = array_map(fn($p) => '$' . $p['name'], $functionInfo['parameters']);
        $paramList = implode(', ', $paramNames);
        
        if ($returnType !== 'void') {
            $code .= "        return static::getFFI()->{$functionName}({$paramList});\n";
        } else {
            $code .= "        static::getFFI()->{$functionName}({$paramList});\n";
        }
        
        $code .= "    }\n";

        return $code;
    }

    /**
     * Generate simplified method name for object-oriented classes
     *
     * @param string $functionName Original C function name
     * @param string $className Target class name
     * @param string $generationType Generation type
     * @return string Simplified method name
     */
    private function generateMethodName(string $functionName, string $className, string $generationType): string
    {
        if ($generationType === 'functional') {
            return $functionName;
        }

        // Extract component name from class name (e.g., "UiButton" -> "Button")
        $componentName = '';
        if (str_starts_with($className, 'Ui')) {
            $componentName = substr($className, 2);
        }

        // Handle "New" functions - convert to "new"
        if (preg_match('/^ui(?:New)(.+)$/', $functionName, $matches)) {
            $suffix = $matches[1];
            if (empty($componentName) || $suffix === $componentName || str_starts_with($suffix, $componentName)) {
                return 'new';
            }
        }

        // Remove "ui" prefix
        $simplifiedName = $functionName;
        if (str_starts_with($simplifiedName, 'ui')) {
            $simplifiedName = substr($simplifiedName, 2);
        }

        // Remove component name prefix if present
        if ($componentName && str_starts_with($simplifiedName, $componentName)) {
            $simplifiedName = substr($simplifiedName, strlen($componentName));
        }

        // Convert to camelCase
        if (empty($simplifiedName)) {
            return 'invoke';
        }

        return lcfirst($simplifiedName);
    }

    /**
     * Generate parameter signature with correct types
     *
     * @param array $parameters Parameter information
     * @return string Parameter signature
     */
    private function generateParameterSignature(array $parameters): string
    {
        $paramStrings = [];

        foreach ($parameters as $param) {
            $type = $this->normalizeParameterType($param['type'], $param['nullable']);
            $paramString = '';

            if ($type !== 'mixed') {
                $paramString .= $type . ' ';
            }

            $paramString .= '$' . $param['name'];
            $paramStrings[] = $paramString;
        }

        return implode(', ', $paramStrings);
    }

    /**
     * Normalize parameter type for method signature
     *
     * @param string $type Original type
     * @param bool $nullable Whether the type is nullable
     * @return string Normalized type
     */
    private function normalizeParameterType(string $type, bool $nullable): string
    {
        // Handle union types (e.g., "string|null")
        if (str_contains($type, '|')) {
            $types = explode('|', $type);
            $mainType = $types[0];
            $hasNull = in_array('null', $types);
            
            if ($hasNull && count($types) === 2) {
                return '?' . $mainType;
            }
            
            return $type; // Keep complex union types as-is
        }

        // Add nullable prefix if needed
        if ($nullable && !str_starts_with($type, '?')) {
            return '?' . $type;
        }

        return $type;
    }

    /**
     * Normalize return type for method signature
     *
     * @param string $returnType Original return type
     * @return string Normalized return type
     */
    private function normalizeReturnType(string $returnType): string
    {
        if (empty($returnType) || $returnType === 'void') {
            return 'void';
        }

        return $this->normalizeParameterType($returnType, str_starts_with($returnType, '?'));
    }

    /**
     * Format type for documentation
     *
     * @param string $type Type name
     * @param bool $nullable Whether nullable
     * @return string Formatted type for documentation
     */
    private function formatTypeForDoc(string $type, bool $nullable): string
    {
        if ($nullable && !str_starts_with($type, '?')) {
            return $type . '|null';
        }

        if (str_starts_with($type, '?')) {
            return substr($type, 1) . '|null';
        }

        return $type;
    }
}