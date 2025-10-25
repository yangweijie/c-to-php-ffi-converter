<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Integration;

use Yangweijie\CWrapper\Analyzer\FunctionSignature;
use Yangweijie\CWrapper\Analyzer\StructureDefinition;
use Yangweijie\CWrapper\Exception\AnalysisException;

/**
 * Processes generated bindings from klitsche/ffigen output
 */
class BindingProcessor
{
    /**
     * Process FFIGen binding result into structured data
     *
     * @param BindingResult $result FFIGen binding result
     * @return ProcessedBindings Processed bindings with functions, structures, and constants
     * @throws AnalysisException If binding processing fails
     */
    public function process(BindingResult $result): ProcessedBindings
    {
        if (!$result->success) {
            throw new AnalysisException('Cannot process failed binding result: ' . implode(', ', $result->errors));
        }

        $constants = $this->processConstants($result->constantsFile);
        $functions = $this->processMethods($result->methodsFile);
        $structures = $this->extractStructures($result->methodsFile);

        return new ProcessedBindings($functions, $structures, $constants);
    }

    /**
     * Process constants.php file to extract constant definitions
     *
     * @param string $constantsFile Path to constants.php file
     * @return array<string, mixed> Extracted constants
     * @throws AnalysisException If constants file cannot be processed
     */
    private function processConstants(string $constantsFile): array
    {
        if (!file_exists($constantsFile)) {
            throw new AnalysisException("Constants file not found: {$constantsFile}");
        }

        $content = file_get_contents($constantsFile);
        if ($content === false) {
            throw new AnalysisException("Failed to read constants file: {$constantsFile}");
        }

        $constants = [];
        
        // Parse PHP constants using regex
        // Look for patterns like: const CONSTANT_NAME = value;
        if (preg_match_all('/const\s+([A-Z_][A-Z0-9_]*)\s*=\s*([^;]+);/i', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = $match[1];
                $value = trim($match[2]);
                
                // Try to evaluate the value safely
                $constants[$name] = $this->parseConstantValue($value);
            }
        }

        return $constants;
    }

    /**
     * Process Methods.php file to extract function signatures
     *
     * @param string $methodsFile Path to Methods.php file
     * @return array<FunctionSignature> Extracted function signatures
     * @throws AnalysisException If methods file cannot be processed
     */
    private function processMethods(string $methodsFile): array
    {
        if (!file_exists($methodsFile)) {
            throw new AnalysisException("Methods file not found: {$methodsFile}");
        }

        $content = file_get_contents($methodsFile);
        if ($content === false) {
            throw new AnalysisException("Failed to read methods file: {$methodsFile}");
        }

        $functions = [];
        
        // Parse function definitions using regex
        // Look for patterns like: public function functionName(type $param): returnType
        $pattern = '/public\s+function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]*)\)\s*:\s*([^{]+)/';
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $functionName = $match[1];
                $parametersString = trim($match[2]);
                $returnType = trim($match[3]);
                
                $parameters = $this->parseParameters($parametersString);
                $documentation = $this->extractDocumentation($content, $functionName);
                
                $functions[] = new FunctionSignature(
                    $functionName,
                    $returnType,
                    $parameters,
                    $documentation
                );
            }
        }

        return $functions;
    }

    /**
     * Extract structure definitions from Methods.php file
     *
     * @param string $methodsFile Path to Methods.php file
     * @return array<StructureDefinition> Extracted structure definitions
     */
    private function extractStructures(string $methodsFile): array
    {
        if (!file_exists($methodsFile)) {
            return [];
        }

        $content = file_get_contents($methodsFile);
        if ($content === false) {
            return [];
        }

        $structures = [];
        
        // Look for struct-related comments or type definitions
        // This is a simplified approach - klitsche/ffigen may not generate explicit struct info
        // We'll extract what we can from comments and type hints
        
        $pattern = '/\/\*\*[^*]*\*\s*struct\s+([a-zA-Z_][a-zA-Z0-9_]*)[^*]*\*\//';
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $structName = $match[1];
                
                // For now, create empty structure definitions
                // In a real implementation, we'd need to parse the actual struct fields
                $structures[] = new StructureDefinition($structName, []);
            }
        }

        return $structures;
    }

    /**
     * Parse function parameters from parameter string
     *
     * @param string $parametersString Parameter string from function signature
     * @return array<array{name: string, type: string}> Parsed parameters
     */
    private function parseParameters(string $parametersString): array
    {
        if (empty(trim($parametersString))) {
            return [];
        }

        $parameters = [];
        $paramParts = explode(',', $parametersString);
        
        foreach ($paramParts as $param) {
            $param = trim($param);
            
            // Parse parameter like "type $name" or "?type $name"
            if (preg_match('/(\??[a-zA-Z_][a-zA-Z0-9_\\\\]*)\s+\$([a-zA-Z_][a-zA-Z0-9_]*)/', $param, $matches)) {
                $parameters[] = [
                    'name' => $matches[2],
                    'type' => $matches[1],
                ];
            }
        }

        return $parameters;
    }

    /**
     * Extract documentation for a specific function
     *
     * @param string $content File content
     * @param string $functionName Function name to find documentation for
     * @return array<string> Documentation lines
     */
    private function extractDocumentation(string $content, string $functionName): array
    {
        $documentation = [];
        
        // Look for PHPDoc comment before the function
        $pattern = '/\/\*\*([^*]*(?:\*(?!\/)[^*]*)*)\*\/\s*public\s+function\s+' . preg_quote($functionName, '/') . '/';
        
        if (preg_match($pattern, $content, $matches)) {
            $docContent = $matches[1];
            
            // Clean up the documentation
            $lines = explode("\n", $docContent);
            foreach ($lines as $line) {
                $line = trim($line, " \t*");
                if (!empty($line)) {
                    $documentation[] = $line;
                }
            }
        }

        return $documentation;
    }

    /**
     * Parse constant value from string representation
     *
     * @param string $value String representation of constant value
     * @return mixed Parsed constant value
     */
    private function parseConstantValue(string $value): mixed
    {
        $value = trim($value);
        
        // Handle different value types
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }
        
        if ($value === 'true' || $value === 'false') {
            return $value === 'true';
        }
        
        if ($value === 'null') {
            return null;
        }
        
        // Handle quoted strings
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }
        
        // Return as string for other cases
        return $value;
    }
}