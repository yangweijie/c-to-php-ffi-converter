<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Generator;

/**
 * Generates PHP constants for C preprocessor definitions
 */
class ConstantGenerator
{
    private TemplateEngine $templateEngine;

    public function __construct(?TemplateEngine $templateEngine = null)
    {
        $this->templateEngine = $templateEngine ?? new TemplateEngine();
    }
    /**
     * Generate PHP constants from C preprocessor definitions
     *
     * @param array<string, mixed> $constants Constants to generate
     * @param string $namespace Namespace for the constants class
     * @param string $className Name of the constants class
     * @return WrapperClass Generated wrapper class containing constants
     */
    public function generateConstantsClass(array $constants, string $namespace, string $className = 'Constants'): WrapperClass
    {
        $classConstants = [];
        $methods = [];

        // Process each constant
        foreach ($constants as $name => $value) {
            $classConstants[$this->normalizeConstantName($name)] = $value;
        }

        // Generate getAllConstants method
        $methods[] = $this->generateGetAllConstantsMethod($classConstants);

        // Generate getConstant method
        $methods[] = $this->generateGetConstantMethod();

        // Generate hasConstant method
        $methods[] = $this->generateHasConstantMethod();

        return new WrapperClass(
            $className,
            $namespace,
            $methods,
            [],
            $classConstants
        );
    }

    /**
     * Generate complete constants class code using templates
     *
     * @param WrapperClass $wrapperClass Wrapper class containing constants
     * @return string Complete PHP class code
     */
    public function generateConstantsClassCode(WrapperClass $wrapperClass): string
    {
        return $this->templateEngine->renderConstantsClass($wrapperClass);
    }

    /**
     * Generate constants as class properties instead of constants
     *
     * @param array<string, mixed> $constants Constants to generate
     * @return array<string> Property definitions
     */
    public function generateConstantProperties(array $constants): array
    {
        $properties = [];

        foreach ($constants as $name => $value) {
            $normalizedName = $this->normalizeConstantName($name);
            $formattedValue = $this->formatConstantValue($value);
            
            $property = "    /**\n";
            $property .= "     * Constant {$name}\n";
            $property .= "     */\n";
            $property .= "    public static \${$normalizedName} = {$formattedValue};\n";
            
            $properties[] = $property;
        }

        return $properties;
    }

    /**
     * Normalize constant name for PHP
     *
     * @param string $name Original constant name
     * @return string Normalized constant name
     */
    private function normalizeConstantName(string $name): string
    {
        // Convert to uppercase and replace invalid characters
        $normalized = strtoupper($name);
        $normalized = preg_replace('/[^A-Z0-9_]/', '_', $normalized);
        
        // Ensure it starts with a letter or underscore
        if (preg_match('/^[0-9]/', $normalized)) {
            $normalized = '_' . $normalized;
        }
        
        return $normalized;
    }

    /**
     * Format constant value for PHP code
     *
     * @param mixed $value Constant value
     * @return string Formatted value
     */
    private function formatConstantValue(mixed $value): string
    {
        if (is_string($value)) {
            return "'" . addslashes($value) . "'";
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_null($value)) {
            return 'null';
        }
        
        if (is_array($value)) {
            return var_export($value, true);
        }
        
        return (string) $value;
    }

    /**
     * Generate getAllConstants method
     *
     * @param array<string, mixed> $constants Class constants
     * @return string Method code
     */
    private function generateGetAllConstantsMethod(array $constants): string
    {
        $code = "    /**\n";
        $code .= "     * Get all constants as an array\n";
        $code .= "     * @return array<string, mixed>\n";
        $code .= "     */\n";
        $code .= "    public static function getAllConstants(): array\n";
        $code .= "    {\n";
        $code .= "        return [\n";

        foreach ($constants as $name => $value) {
            $formattedValue = $this->formatConstantValue($value);
            $code .= "            '{$name}' => {$formattedValue},\n";
        }

        $code .= "        ];\n";
        $code .= "    }\n";

        return $code;
    }

    /**
     * Generate getConstant method
     *
     * @return string Method code
     */
    private function generateGetConstantMethod(): string
    {
        $code = "    /**\n";
        $code .= "     * Get a constant value by name\n";
        $code .= "     * @param string \$name Constant name\n";
        $code .= "     * @return mixed Constant value\n";
        $code .= "     * @throws \\InvalidArgumentException If constant doesn't exist\n";
        $code .= "     */\n";
        $code .= "    public static function getConstant(string \$name): mixed\n";
        $code .= "    {\n";
        $code .= "        \$constants = self::getAllConstants();\n";
        $code .= "        \n";
        $code .= "        if (!array_key_exists(\$name, \$constants)) {\n";
        $code .= "            throw new \\InvalidArgumentException(\"Constant '{\$name}' does not exist\");\n";
        $code .= "        }\n";
        $code .= "        \n";
        $code .= "        return \$constants[\$name];\n";
        $code .= "    }\n";

        return $code;
    }

    /**
     * Generate hasConstant method
     *
     * @return string Method code
     */
    private function generateHasConstantMethod(): string
    {
        $code = "    /**\n";
        $code .= "     * Check if a constant exists\n";
        $code .= "     * @param string \$name Constant name\n";
        $code .= "     * @return bool True if constant exists\n";
        $code .= "     */\n";
        $code .= "    public static function hasConstant(string \$name): bool\n";
        $code .= "    {\n";
        $code .= "        \$constants = self::getAllConstants();\n";
        $code .= "        return array_key_exists(\$name, \$constants);\n";
        $code .= "    }\n";

        return $code;
    }
}