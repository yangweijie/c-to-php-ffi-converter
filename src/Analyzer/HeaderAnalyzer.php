<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Analyzer;

use Yangweijie\CWrapper\Exception\AnalysisException;

/**
 * Analyzes C header files to extract function signatures, structures, and constants
 */
class HeaderAnalyzer implements AnalyzerInterface
{
    /**
     * Analyze a C header file
     *
     * @param string $path Path to the header file
     * @return AnalysisResult Analysis results
     * @throws AnalysisException If the file cannot be analyzed
     */
    public function analyze(string $path): AnalysisResult
    {
        if (!file_exists($path)) {
            throw new AnalysisException("Header file not found: {$path}");
        }

        if (!is_readable($path)) {
            throw new AnalysisException("Header file is not readable: {$path}");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new AnalysisException("Failed to read header file: {$path}");
        }

        // Remove comments and preprocess content
        $cleanContent = $this->preprocessContent($content);

        // Extract different elements
        $functions = $this->extractFunctions($cleanContent);
        $structures = $this->extractStructures($cleanContent);
        $constants = $this->extractConstants($content); // Use original content for constants
        $dependencies = $this->extractDependencies($content); // Use original content for includes

        return new AnalysisResult($functions, $structures, $constants, $dependencies);
    }

    /**
     * Preprocess content by removing comments and normalizing whitespace
     */
    private function preprocessContent(string $content): string
    {
        // Remove single-line comments
        $content = preg_replace('/\/\/.*$/m', '', $content);
        
        // Remove multi-line comments
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);
        
        // Normalize whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        return trim($content);
    }

    /**
     * Extract function signatures from header content
     *
     * @return array<FunctionSignature>
     */
    private function extractFunctions(string $content): array
    {
        $functions = [];
        
        // Pattern to match function declarations
        // Matches: return_type function_name(parameters);
        // Updated to handle function pointers in parameters
        $pattern = '/(\w+(?:\s*\*)*)\s+(\w+)\s*\(([^;]*?)\)\s*;/';
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $returnType = trim($match[1]);
                $functionName = trim($match[2]);
                $parametersStr = trim($match[3]);
                
                $parameters = $this->parseParameters($parametersStr);
                
                $functions[] = new FunctionSignature(
                    $functionName,
                    $returnType,
                    $parameters
                );
            }
        }
        
        return $functions;
    }

    /**
     * Parse function parameters string into structured format
     *
     * @return array<array{name: string, type: string}>
     */
    private function parseParameters(string $parametersStr): array
    {
        if (empty($parametersStr) || $parametersStr === 'void') {
            return [];
        }
        
        $parameters = [];
        
        // Handle function pointer parameters by counting parentheses
        $paramParts = $this->splitParametersRespectingParentheses($parametersStr);
        
        foreach ($paramParts as $param) {
            $param = trim($param);
            if (empty($param)) {
                continue;
            }
            
            // Handle function pointer parameters like "void (*callback)(int, const char*)"
            if (preg_match('/^(.+?)\s*\(\s*\*\s*(\w+)\s*\)\s*\(([^)]*)\)$/', $param, $matches)) {
                $returnType = trim($matches[1]);
                $name = trim($matches[2]);
                $funcParams = trim($matches[3]);
                $type = "{$returnType} (*{$name})({$funcParams})";
                
                $parameters[] = [
                    'name' => $name,
                    'type' => $type
                ];
            }
            // Handle regular parameters like "int a", "int* arr", "const char* str"
            else if (preg_match('/^(.+?)\s+(\w+)$/', $param, $matches)) {
                $type = trim($matches[1]);
                $name = trim($matches[2]);
                
                $parameters[] = [
                    'name' => $name,
                    'type' => $type
                ];
            } else {
                // Parameter without name (just type)
                $parameters[] = [
                    'name' => '',
                    'type' => $param
                ];
            }
        }
        
        return $parameters;
    }

    /**
     * Split parameters string respecting parentheses for function pointers
     *
     * @return array<string>
     */
    private function splitParametersRespectingParentheses(string $parametersStr): array
    {
        $parts = [];
        $current = '';
        $parenLevel = 0;
        
        for ($i = 0; $i < strlen($parametersStr); $i++) {
            $char = $parametersStr[$i];
            
            if ($char === '(') {
                $parenLevel++;
            } elseif ($char === ')') {
                $parenLevel--;
            } elseif ($char === ',' && $parenLevel === 0) {
                $parts[] = trim($current);
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $parts[] = trim($current);
        }
        
        return $parts;
    }

    /**
     * Extract structure definitions from header content
     *
     * @return array<StructureDefinition>
     */
    private function extractStructures(string $content): array
    {
        $structures = [];
        
        // Pattern to match typedef struct definitions
        $pattern = '/typedef\s+(struct|union)\s*\{([^}]+)\}\s*(\w+)\s*;/';
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $type = $match[1]; // 'struct' or 'union'
                $fieldsStr = $match[2];
                $structName = $match[3];
                
                $fields = $this->parseStructFields($fieldsStr);
                $isUnion = ($type === 'union');
                
                $structures[] = new StructureDefinition(
                    $structName,
                    $fields,
                    $isUnion
                );
            }
        }
        
        return $structures;
    }

    /**
     * Parse structure fields string into structured format
     *
     * @return array<array{name: string, type: string}>
     */
    private function parseStructFields(string $fieldsStr): array
    {
        $fields = [];
        
        // Split by semicolon to get individual field declarations
        $fieldDeclarations = explode(';', $fieldsStr);
        
        foreach ($fieldDeclarations as $declaration) {
            $declaration = trim($declaration);
            if (empty($declaration)) {
                continue;
            }
            
            // Parse field declaration like "int x" or "char* name"
            if (preg_match('/^(.+?)\s+(\w+)$/', $declaration, $matches)) {
                $type = trim($matches[1]);
                $name = trim($matches[2]);
                
                $fields[] = [
                    'name' => $name,
                    'type' => $type
                ];
            }
        }
        
        return $fields;
    }

    /**
     * Extract constants and preprocessor definitions
     *
     * @return array<string, mixed>
     */
    private function extractConstants(string $content): array
    {
        $constants = [];
        
        // Split content into lines and process each line
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Match #define statements with values (not header guards)
            if (preg_match('/^#define\s+(\w+)\s+(.+)$/', $line, $matches)) {
                $name = $matches[1];
                $value = trim($matches[2]);
                
                // Skip header guards (defines without values or just identifiers)
                if (empty($value) || $value === $name) {
                    continue;
                }
                
                // Try to convert value to appropriate type
                $constants[$name] = $this->parseConstantValue($value);
            }
        }
        
        return $constants;
    }

    /**
     * Parse constant value and convert to appropriate PHP type
     */
    private function parseConstantValue(string $value): mixed
    {
        // Remove trailing comments if any
        $value = preg_replace('/\/\/.*$/', '', $value);
        $value = trim($value);
        
        // Try to parse as number
        if (is_numeric($value)) {
            if (strpos($value, '.') !== false) {
                return (float) $value;
            }
            return (int) $value;
        }
        
        // Try to parse as string literal
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            return $matches[1]; // Return without quotes
        }
        
        // Return as string for other cases
        return $value;
    }

    /**
     * Extract include dependencies from header content
     *
     * @return array<string>
     */
    private function extractDependencies(string $content): array
    {
        $dependencies = [];
        
        // Pattern to match #include statements
        $pattern = '/#include\s+[<"]([^>"]+)[>"]/';
        
        if (preg_match_all($pattern, $content, $matches)) {
            $dependencies = array_unique($matches[1]);
        }
        
        return array_values($dependencies);
    }
}