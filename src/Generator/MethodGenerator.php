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
     * @return string Generated method code
     */
    public function generateMethod(FunctionSignature $function): string
    {
        $methodName = $this->convertFunctionName($function->name);
        $parameters = $this->generateParameters($function->parameters);
        $parameterList = $this->generateParameterList($function->parameters);
        $returnType = $this->typeMapper->mapCTypeToPhp($function->returnType);
        $ffiCall = $this->generateFFICall($function);

        $code = "    /**\n";
        $code .= "     * Wrapper for {$function->name}\n";
        
        // Add parameter documentation
        foreach ($function->parameters as $param) {
            $phpType = $this->typeMapper->mapCTypeToPhp($param['type']);
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
        $code .= "    public function {$methodName}({$parameters})";
        
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
     * @return string PHP method name
     */
    private function convertFunctionName(string $functionName): string
    {
        // Remove common C prefixes and convert to camelCase
        $name = preg_replace('/^[a-z]+_/', '', $functionName);
        $parts = explode('_', $name);
        $methodName = array_shift($parts);
        
        foreach ($parts as $part) {
            $methodName .= ucfirst($part);
        }
        
        return $methodName;
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
            $phpType = $this->typeMapper->mapCTypeToPhp($param['type']);
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
        return "\$this->ffi->{$function->name}({$paramList})";
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