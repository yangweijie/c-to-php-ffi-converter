<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Generator;

/**
 * Parses klitsche/ffigen output to extract correct type information
 */
class FFIGenOutputParser
{
    /**
     * Parse Methods.php file to extract function information with correct types
     *
     * @param string $methodsFilePath Path to the generated Methods.php file
     * @return array<string, array{name: string, returnType: string, parameters: array, rawMethod: string}>
     */
    public function parseMethodsFile(string $methodsFilePath): array
    {
        if (!file_exists($methodsFilePath)) {
            return [];
        }

        $content = file_get_contents($methodsFilePath);
        $functions = [];

        // Extract function definitions using regex
        $pattern = '/\/\*\*\s*\n(.*?)\*\/\s*\n\s*public static function (\w+)\((.*?)\)(?::\s*([^{]+))?\s*\{/s';
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $docComment = $match[1];
                $functionName = $match[2];
                $parametersString = $match[3];
                $returnType = isset($match[4]) ? trim($match[4]) : 'void';

                // Parse parameters
                $parameters = $this->parseParameters($parametersString);
                
                // Parse doc comment for additional type info
                $docInfo = $this->parseDocComment($docComment);

                $functions[$functionName] = [
                    'name' => $functionName,
                    'returnType' => $returnType,
                    'parameters' => $parameters,
                    'docComment' => $docInfo,
                    'rawMethod' => $match[0]
                ];
            }
        }

        return $functions;
    }

    /**
     * Parse parameter string from method signature
     *
     * @param string $parametersString Parameter string from method signature
     * @return array<array{name: string, type: string, nullable: bool}>
     */
    private function parseParameters(string $parametersString): array
    {
        $parameters = [];
        
        if (empty(trim($parametersString))) {
            return $parameters;
        }

        // Split parameters by comma, but be careful with nested types
        $paramParts = $this->splitParameters($parametersString);
        
        foreach ($paramParts as $param) {
            $param = trim($param);
            
            // Parse type and name
            if (preg_match('/^(\??[\\\\a-zA-Z_][\\\\a-zA-Z0-9_]*(?:\|[\\\\a-zA-Z_][\\\\a-zA-Z0-9_]*)*)\s+\$(\w+)$/', $param, $matches)) {
                $type = $matches[1];
                $name = $matches[2];
                $nullable = str_starts_with($type, '?');
                
                if ($nullable) {
                    $type = substr($type, 1);
                }
                
                $parameters[] = [
                    'name' => $name,
                    'type' => $type,
                    'nullable' => $nullable
                ];
            } elseif (preg_match('/^\$(\w+)$/', $param, $matches)) {
                // Parameter without type hint (mixed)
                $parameters[] = [
                    'name' => $matches[1],
                    'type' => 'mixed',
                    'nullable' => false
                ];
            }
        }

        return $parameters;
    }

    /**
     * Split parameters string, handling nested types
     *
     * @param string $parametersString Parameter string
     * @return array<string> Individual parameter strings
     */
    private function splitParameters(string $parametersString): array
    {
        $parameters = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';

        for ($i = 0; $i < strlen($parametersString); $i++) {
            $char = $parametersString[$i];
            
            if (!$inString) {
                if ($char === '"' || $char === "'") {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === '<' || $char === '(' || $char === '[') {
                    $depth++;
                } elseif ($char === '>' || $char === ')' || $char === ']') {
                    $depth--;
                } elseif ($char === ',' && $depth === 0) {
                    $parameters[] = trim($current);
                    $current = '';
                    continue;
                }
            } else {
                if ($char === $stringChar && ($i === 0 || $parametersString[$i-1] !== '\\')) {
                    $inString = false;
                }
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $parameters[] = trim($current);
        }

        return $parameters;
    }

    /**
     * Parse doc comment to extract parameter and return type information
     *
     * @param string $docComment Doc comment content
     * @return array{parameters: array, returnType: string|null}
     */
    private function parseDocComment(string $docComment): array
    {
        $parameters = [];
        $returnType = null;

        $lines = explode("\n", $docComment);
        
        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B*");
            
            // Parse @param lines
            if (preg_match('/^@param\s+([^\s]+)\s+\$(\w+)(?:\s+(.+))?$/', $line, $matches)) {
                $parameters[$matches[2]] = [
                    'type' => $matches[1],
                    'description' => $matches[3] ?? ''
                ];
            }
            
            // Parse @return lines
            if (preg_match('/^@return\s+([^\s]+)(?:\s+(.+))?$/', $line, $matches)) {
                $returnType = $matches[1];
            }
        }

        return [
            'parameters' => $parameters,
            'returnType' => $returnType
        ];
    }

    /**
     * Extract function grouping information for semantic organization
     *
     * @param array<string, array> $functions Parsed functions
     * @return array<string, array<string>> Groups of function names
     */
    public function groupFunctionsBySemantics(array $functions): array
    {
        $groups = [];

        foreach ($functions as $functionName => $functionInfo) {
            $group = $this->determineSemanticGroup($functionName);
            
            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }
            
            $groups[$group][] = $functionName;
        }

        return $groups;
    }

    /**
     * Determine semantic group for a function
     *
     * @param string $functionName Function name
     * @return string Group name
     */
    private function determineSemanticGroup(string $functionName): string
    {
        // UI component patterns
        $patterns = [
            'Window' => '/^ui(?:New)?Window/',
            'Button' => '/^ui(?:New)?Button/',
            'Entry' => '/^ui(?:New)?(?:Password|Search)?Entry/',
            'MultilineEntry' => '/^ui(?:New)?(?:NonWrapping)?MultilineEntry/',
            'Label' => '/^ui(?:New)?Label/',
            'Checkbox' => '/^ui(?:New)?Checkbox/',
            'Tab' => '/^ui(?:New)?Tab/',
            'Group' => '/^ui(?:New)?Group/',
            'Box' => '/^ui(?:New)?(?:Horizontal|Vertical)?Box/',
            'Spinbox' => '/^ui(?:New)?Spinbox/',
            'Slider' => '/^ui(?:New)?Slider/',
            'ProgressBar' => '/^ui(?:New)?ProgressBar/',
            'Separator' => '/^ui(?:New)?(?:Horizontal|Vertical)?Separator/',
            'Combobox' => '/^ui(?:New)?(?:Editable)?Combobox/',
            'RadioButtons' => '/^ui(?:New)?RadioButtons/',
            'DateTimePicker' => '/^ui(?:New)?(?:Date|Time|DateTime)Picker/',
            'Menu' => '/^ui(?:New)?Menu(?!Item)/',
            'MenuItem' => '/^uiMenuItem/',
            'Area' => '/^ui(?:New)?(?:Scrolling)?Area/',
            'Grid' => '/^ui(?:New)?Grid/',
            'Form' => '/^ui(?:New)?Form/',
            'Table' => '/^ui(?:New)?Table(?!Value|Model|Selection)/',
            'TableModel' => '/^ui(?:New)?TableModel/',
            'TableValue' => '/^ui(?:New)?TableValue/',
            'TableSelection' => '/^ui(?:Free)?TableSelection/',
            'FontButton' => '/^ui(?:New)?FontButton/',
            'ColorButton' => '/^ui(?:New)?ColorButton/',
            'Image' => '/^ui(?:New|Free)?Image/',
            'Control' => '/^uiControl/',
        ];

        foreach ($patterns as $group => $pattern) {
            if (preg_match($pattern, $functionName)) {
                return $group;
            }
        }

        // General UI functions
        if (str_starts_with($functionName, 'ui')) {
            return 'Ui';
        }

        return 'General';
    }
}