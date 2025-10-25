<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Documentation;

use Yangweijie\CWrapper\Analyzer\FunctionSignature;
use Yangweijie\CWrapper\Generator\WrapperClass;

/**
 * Generates PHPDoc comments for wrapper methods
 */
class PHPDocGenerator
{
    /**
     * Generate PHPDoc comment for a wrapper method
     *
     * @param FunctionSignature $signature C function signature
     * @param string $methodName PHP method name
     * @return string Generated PHPDoc comment
     */
    public function generateMethodDoc(FunctionSignature $signature, string $methodName): string
    {
        $lines = [];
        
        // Add description
        $description = $this->generateDescription($signature, $methodName);
        $lines[] = "/**";
        $lines[] = " * {$description}";
        
        // Add original C function reference
        $lines[] = " *";
        $lines[] = " * @see C function: {$signature->name}()";
        
        // Add parameter documentation
        if (!empty($signature->parameters)) {
            $lines[] = " *";
            foreach ($signature->parameters as $param) {
                $phpType = $this->mapCTypeToPhpType($param['type']);
                $lines[] = " * @param {$phpType} \${$param['name']} {$this->generateParamDescription($param)}";
            }
        }
        
        // Add return type documentation
        if ($signature->returnType !== 'void') {
            $phpReturnType = $this->mapCTypeToPhpType($signature->returnType);
            $lines[] = " *";
            $lines[] = " * @return {$phpReturnType} {$this->generateReturnDescription($signature->returnType)}";
        }
        
        // Add usage example
        $example = $this->generateUsageExample($signature, $methodName);
        if ($example) {
            $lines[] = " *";
            $lines[] = " * @example";
            foreach (explode("\n", $example) as $exampleLine) {
                $lines[] = " * " . $exampleLine;
            }
        }
        
        // Add throws documentation for validation
        $lines[] = " *";
        $lines[] = " * @throws \\Yangweijie\\CWrapper\\Exception\\ValidationException When parameter validation fails";
        
        $lines[] = " */";
        
        return implode("\n", $lines);
    }

    /**
     * Generate PHPDoc comments for all methods in a wrapper class
     *
     * @param WrapperClass $class Wrapper class
     * @param array<FunctionSignature> $signatures Function signatures
     * @return array<string, string> Method name to PHPDoc mapping
     */
    public function generateClassMethodDocs(WrapperClass $class, array $signatures): array
    {
        $docs = [];
        
        foreach ($signatures as $signature) {
            $methodName = $this->convertFunctionNameToMethodName($signature->name);
            $docs[$methodName] = $this->generateMethodDoc($signature, $methodName);
        }
        
        return $docs;
    }

    /**
     * Generate class-level PHPDoc comment
     *
     * @param WrapperClass $class Wrapper class
     * @param string $libraryName C library name
     * @return string Generated class PHPDoc comment
     */
    public function generateClassDoc(WrapperClass $class, string $libraryName): string
    {
        $lines = [];
        $lines[] = "/**";
        $lines[] = " * PHP FFI wrapper for {$libraryName} library";
        $lines[] = " *";
        $lines[] = " * This class provides object-oriented access to {$libraryName} C library functions";
        $lines[] = " * with automatic parameter validation and type conversion.";
        $lines[] = " *";
        $lines[] = " * @package {$class->namespace}";
        $lines[] = " */";
        
        return implode("\n", $lines);
    }

    /**
     * Generate description for a method
     *
     * @param FunctionSignature $signature Function signature
     * @param string $methodName Method name
     * @return string Method description
     */
    private function generateDescription(FunctionSignature $signature, string $methodName): string
    {
        // Use existing documentation if available
        if (!empty($signature->documentation)) {
            return trim($signature->documentation[0]);
        }
        
        // Generate generic description based on function name
        $action = $this->inferActionFromFunctionName($signature->name);
        return "Wrapper for {$signature->name}() - {$action}";
    }

    /**
     * Generate parameter description
     *
     * @param array{name: string, type: string} $param Parameter info
     * @return string Parameter description
     */
    private function generateParamDescription(array $param): string
    {
        $type = $param['type'];
        
        // Generate description based on parameter type
        if (str_contains($type, 'char*') || str_contains($type, 'const char*')) {
            return "String parameter";
        }
        
        if (str_contains($type, '*')) {
            return "Pointer parameter";
        }
        
        if (str_contains($type, 'char')) {
            return "String parameter";
        }
        
        if (str_contains($type, 'int') || str_contains($type, 'long') || str_contains($type, 'short')) {
            return "Integer parameter";
        }
        
        if (str_contains($type, 'float') || str_contains($type, 'double')) {
            return "Floating point parameter";
        }
        
        return "Parameter of type {$type}";
    }

    /**
     * Generate return type description
     *
     * @param string $returnType C return type
     * @return string Return description
     */
    private function generateReturnDescription(string $returnType): string
    {
        if (str_contains($returnType, '*')) {
            return "Pointer to result";
        }
        
        if (str_contains($returnType, 'char')) {
            return "String result";
        }
        
        if (str_contains($returnType, 'int') || str_contains($returnType, 'long') || str_contains($returnType, 'short')) {
            return "Integer result";
        }
        
        if (str_contains($returnType, 'float') || str_contains($returnType, 'double')) {
            return "Floating point result";
        }
        
        return "Result of type {$returnType}";
    }

    /**
     * Generate usage example for a method
     *
     * @param FunctionSignature $signature Function signature
     * @param string $methodName Method name
     * @return string|null Usage example or null if not applicable
     */
    private function generateUsageExample(FunctionSignature $signature, string $methodName): ?string
    {
        // Only generate examples for simple functions
        if (count($signature->parameters) > 3) {
            return null;
        }
        
        $example = "\$wrapper = new \\{$signature->name}Wrapper();\n";
        
        if (empty($signature->parameters)) {
            $example .= "\$result = \$wrapper->{$methodName}();";
        } else {
            $params = [];
            foreach ($signature->parameters as $i => $param) {
                $params[] = $this->generateExampleValue($param['type'], $param['name']);
            }
            $example .= "\$result = \$wrapper->{$methodName}(" . implode(', ', $params) . ");";
        }
        
        return $example;
    }

    /**
     * Generate example value for a parameter type
     *
     * @param string $type C type
     * @param string $name Parameter name
     * @return string Example value
     */
    private function generateExampleValue(string $type, string $name): string
    {
        if (str_contains($type, 'char*') || str_contains($type, 'const char*')) {
            return "'example_string'";
        }
        
        if (str_contains($type, '*')) {
            return "\${$name}_ptr";
        }
        
        if (str_contains($type, 'int') || str_contains($type, 'long') || str_contains($type, 'short')) {
            return '42';
        }
        
        if (str_contains($type, 'float') || str_contains($type, 'double')) {
            return '3.14';
        }
        
        return "\${$name}";
    }

    /**
     * Map C type to PHP type for documentation
     *
     * @param string $cType C type
     * @return string PHP type
     */
    private function mapCTypeToPhpType(string $cType): string
    {
        // Remove const qualifier
        $cType = str_replace('const ', '', $cType);
        
        // Handle pointers
        if (str_contains($cType, 'char*')) {
            return 'string';
        }
        
        if (str_contains($cType, '*')) {
            return 'mixed'; // Generic pointer
        }
        
        // Handle basic types
        if (str_contains($cType, 'int') || str_contains($cType, 'long') || str_contains($cType, 'short')) {
            return 'int';
        }
        
        if (str_contains($cType, 'float') || str_contains($cType, 'double')) {
            return 'float';
        }
        
        if (str_contains($cType, 'char')) {
            return 'string';
        }
        
        if ($cType === 'void') {
            return 'void';
        }
        
        return 'mixed';
    }

    /**
     * Convert C function name to PHP method name
     *
     * @param string $functionName C function name
     * @return string PHP method name
     */
    private function convertFunctionNameToMethodName(string $functionName): string
    {
        // Remove common prefixes (lib_, test_, etc.)
        $methodName = preg_replace('/^[a-z]+_/', '', $functionName);
        
        // If nothing was removed, use the full name
        if ($methodName === $functionName) {
            $methodName = $functionName;
        }
        
        // Convert to camelCase
        return lcfirst(str_replace('_', '', ucwords($methodName, '_')));
    }

    /**
     * Infer action from function name
     *
     * @param string $functionName Function name
     * @return string Inferred action
     */
    private function inferActionFromFunctionName(string $functionName): string
    {
        $name = strtolower($functionName);
        
        if (str_contains($name, 'create') || str_contains($name, 'new') || str_contains($name, 'init')) {
            return 'creates or initializes a resource';
        }
        
        if (str_contains($name, 'destroy') || str_contains($name, 'free') || str_contains($name, 'cleanup')) {
            return 'destroys or frees a resource';
        }
        
        if (str_contains($name, 'get') || str_contains($name, 'read') || str_contains($name, 'fetch')) {
            return 'retrieves data';
        }
        
        if (str_contains($name, 'set') || str_contains($name, 'write') || str_contains($name, 'update')) {
            return 'sets or updates data';
        }
        
        if (str_contains($name, 'process') || str_contains($name, 'execute') || str_contains($name, 'run')) {
            return 'processes or executes an operation';
        }
        
        return 'performs an operation';
    }
}