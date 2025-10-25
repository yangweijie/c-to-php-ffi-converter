<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Generator;

use Yangweijie\CWrapper\Analyzer\FunctionSignature;

/**
 * Generates PHP wrapper methods with FFI calls
 */
class MethodGenerator
{
    private TypeMapper $typeMapper;

    public function __construct(?TypeMapper $typeMapper = null)
    {
        $this->typeMapper = $typeMapper ?? new TypeMapper();
    }

    /**
     * Generate a wrapper method from a C function signature
     *
     * @param FunctionSignature $function Function signature to wrap
     * @param string $generationType Generation type: 'object' or 'functional'
     * @param string $className Class name for context (optional)
     * @return string Generated method code
     */
    public function generateMethod(FunctionSignature $function, string $generationType = 'object', string $className = ''): string
    {
        $methodName = $this->convertFunctionName($function->name, $generationType, $className);
        $parameters = $this->generateParameters($function->parameters);
        $parameterList = $this->generateParameterList($function->parameters);
        $returnType = $this->mapReturnType($function->returnType);
        $ffiCall = $this->generateFFICall($function);

        $code = "    /**\n";
        $code .= "     * Wrapper for {$function->name}\n";
        
        // Add parameter documentation with improved type mapping
        foreach ($function->parameters as $param) {
            $phpType = $this->mapParameterType($param['type']);
            $code .= "     * @param {$phpType} \${$param['name']}\n";
        }
        
        if ($returnType !== 'void') {
            $code .= "     * @return {$returnType}\n";
        }
        
        // Add any existing documentation
        foreach ($function->documentation as $doc) {
            $code .= "     * {$doc}\n";
        }
        
        $code .= "     */\n";
        
        // Generate method signature based on generation type
        if ($generationType === 'functional') {
            // For functional mode, use the original C function name and make it static
            $code .= "    public static function {$function->name}({$parameters})";
        } else {
            // For object mode, use converted method name and make it static (for Bootstrap pattern)
            $code .= "    public static function {$methodName}({$parameters})";
        }
        
        if ($returnType !== 'void') {
            $code .= ": {$returnType}";
        }
        
        $code .= "\n    {\n";
        
        // Add parameter validation
        $code .= $this->generateParameterValidation($function->parameters);
        
        // Add FFI call
        if ($returnType !== 'void') {
            $code .= "        return {$ffiCall};\n";
        } else {
            $code .= "        {$ffiCall};\n";
        }
        
        $code .= "    }\n";

        return $code;
    }

    /**
     * Convert C function name to PHP method name
     *
     * @param string $functionName C function name
     * @param string $generationType Generation type
     * @param string $className Class name for context (optional)
     * @return string PHP method name
     */
    private function convertFunctionName(string $functionName, string $generationType = 'object', string $className = ''): string
    {
        if ($generationType === 'functional') {
            // For functional mode, keep original function name
            return $functionName;
        }
        
        // For object mode, simplify method names
        return $this->simplifyMethodName($functionName, $className);
    }

    /**
     * Simplify method name for object-oriented classes
     *
     * @param string $functionName Original C function name
     * @param string $className Class name for context
     * @return string Simplified method name
     */
    private function simplifyMethodName(string $functionName, string $className): string
    {
        // Extract component name from class name (e.g., "UiButton" -> "Button")
        $componentName = '';
        if (str_starts_with($className, 'Ui')) {
            $componentName = substr($className, 2); // Remove "Ui" prefix
        }
        
        // Handle "New" functions - convert to "new"
        if (str_contains($functionName, 'New' . $componentName)) {
            return 'new';
        }
        
        // Remove ui prefix and component name from function name
        $simplifiedName = $functionName;
        
        // Remove "ui" prefix
        if (str_starts_with($simplifiedName, 'ui')) {
            $simplifiedName = substr($simplifiedName, 2);
        }
        
        // Remove component name prefix if present
        if ($componentName && str_starts_with($simplifiedName, $componentName)) {
            $simplifiedName = substr($simplifiedName, strlen($componentName));
        }
        
        // Convert to camelCase
        if (empty($simplifiedName)) {
            return 'invoke'; // Fallback for edge cases
        }
        
        // Convert first letter to lowercase
        return lcfirst($simplifiedName);
    }

    /**
     * Map parameter type with improved logic
     *
     * @param string $cType C type
     * @return string PHP type
     */
    private function mapParameterType(string $cType): string
    {
        $cleanType = trim($cType);
        
        // Handle specific UI object pointers (these should not be null)
        if (str_ends_with($cleanType, '*')) {
            $baseType = trim(substr($cleanType, 0, -1));
            
            // String types
            if ($baseType === 'char' || $baseType === 'const char') {
                return 'string';
            }
            
            // UI object types - these are required parameters, not nullable
            if (str_starts_with($baseType, 'ui') || 
                str_starts_with($baseType, 'struct ui') ||
                $this->typeMapper->isUIObjectType($baseType)) {
                return '\\FFI\\CData';
            }
            
            // Generic void pointer for callbacks and data
            if ($baseType === 'void') {
                return 'mixed';
            }
            
            // Other pointers
            return '\\FFI\\CData';
        }
        
        // Use TypeMapper for basic types
        return $this->typeMapper->mapCTypeToPhp($cType, false);
    }

    /**
     * Map return type with improved logic
     *
     * @param string $cType C type
     * @return string PHP type
     */
    private function mapReturnType(string $cType): string
    {
        $cleanType = trim($cType);
        
        // Handle pointer return types
        if (str_ends_with($cleanType, '*')) {
            $baseType = trim(substr($cleanType, 0, -1));
            
            // String types
            if ($baseType === 'char' || $baseType === 'const char') {
                return '?string'; // String returns can be null
            }
            
            // UI object types - these can return null on failure
            if (str_starts_with($baseType, 'ui') || 
                str_starts_with($baseType, 'struct ui') ||
                $this->typeMapper->isUIObjectType($baseType)) {
                return '?\\FFI\\CData';
            }
            
            // Generic void pointer
            if ($baseType === 'void') {
                return 'mixed';
            }
            
            // Other pointers
            return '?\\FFI\\CData';
        }
        
        // Use TypeMapper for basic types
        return $this->typeMapper->mapCTypeToPhp($cType, false);
    }

    /**
     * Generate parameter list for method signature
     *
     * @param array<array{name: string, type: string}> $parameters Function parameters
     * @return string Parameter list string
     */
    private function generateParameters(array $parameters): string
    {
        $paramStrings = [];
        
        foreach ($parameters as $param) {
            $phpType = $this->mapParameterType($param['type']);
            $paramString = '';
            
            if ($phpType !== 'mixed') {
                $paramString .= $phpType . ' ';
            }
            
            $paramString .= '$' . $param['name'];
            $paramStrings[] = $paramString;
        }
        
        return implode(', ', $paramStrings);
    }

    /**
     * Generate parameter list for FFI call
     *
     * @param array<array{name: string, type: string}> $parameters Function parameters
     * @return string Parameter list for FFI call
     */
    private function generateParameterList(array $parameters): string
    {
        $paramNames = [];
        
        foreach ($parameters as $param) {
            $paramNames[] = '$' . $param['name'];
        }
        
        return implode(', ', $paramNames);
    }

    /**
     * Generate FFI function call
     *
     * @param FunctionSignature $function Function signature
     * @return string FFI call code
     */
    private function generateFFICall(FunctionSignature $function): string
    {
        $paramList = $this->generateParameterList($function->parameters);
        return "static::getFFI()->{$function->name}({$paramList})";
    }

    /**
     * Generate parameter validation code
     *
     * @param array<array{name: string, type: string}> $parameters Function parameters
     * @return string Validation code
     */
    private function generateParameterValidation(array $parameters): string
    {
        $validation = '';
        
        foreach ($parameters as $param) {
            $validation .= $this->typeMapper->generateValidation($param['name'], $param['type']);
        }
        
        return $validation;
    }
}