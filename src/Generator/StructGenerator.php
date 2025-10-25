<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Generator;

use Yangweijie\CWrapper\Analyzer\StructureDefinition;

/**
 * Generates PHP classes for C struct definitions
 */
class StructGenerator
{
    private TypeMapper $typeMapper;
    private TemplateEngine $templateEngine;

    public function __construct(?TypeMapper $typeMapper = null, ?TemplateEngine $templateEngine = null)
    {
        $this->typeMapper = $typeMapper ?? new TypeMapper();
        $this->templateEngine = $templateEngine ?? new TemplateEngine();
    }

    /**
     * Generate a PHP class from a C structure definition
     *
     * @param StructureDefinition $structure Structure to convert
     * @param string $namespace Namespace for the generated class
     * @return WrapperClass Generated wrapper class for the struct
     */
    public function generateStructClass(StructureDefinition $structure, string $namespace): WrapperClass
    {
        $className = $this->convertStructName($structure->name);
        $properties = [];
        $methods = [];

        // Generate properties for each field
        foreach ($structure->fields as $field) {
            $properties[] = $this->generateProperty($field);
        }

        // Generate constructor
        $methods[] = $this->generateConstructor($structure);

        // Generate getter and setter methods
        foreach ($structure->fields as $field) {
            $methods[] = $this->generateGetter($field);
            $methods[] = $this->generateSetter($field);
        }

        // Generate toArray method
        $methods[] = $this->generateToArrayMethod($structure);

        // Generate fromArray method
        $methods[] = $this->generateFromArrayMethod($structure);

        return new WrapperClass(
            $className,
            $namespace,
            $methods,
            $properties,
            []
        );
    }

    /**
     * Generate complete struct class code using templates
     *
     * @param WrapperClass $wrapperClass Wrapper class for the struct
     * @param StructureDefinition $structure Original structure definition
     * @return string Complete PHP class code
     */
    public function generateStructClassCode(WrapperClass $wrapperClass, StructureDefinition $structure): string
    {
        return $this->templateEngine->renderStructClass($wrapperClass, $structure->fields, $structure->isUnion);
    }

    /**
     * Convert C struct name to PHP class name
     *
     * @param string $structName C struct name
     * @return string PHP class name
     */
    private function convertStructName(string $structName): string
    {
        // Remove struct/union prefix if present
        $name = preg_replace('/^(struct|union)\s+/', '', $structName);
        
        // Convert to PascalCase
        $parts = explode('_', $name);
        $className = '';
        
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        
        return $className;
    }

    /**
     * Generate a property for a struct field
     *
     * @param array{name: string, type: string} $field Struct field
     * @return string Property definition code
     */
    private function generateProperty(array $field): string
    {
        $phpType = $this->typeMapper->mapCTypeToPhp($field['type']);
        
        $code = "    /**\n";
        $code .= "     * {$field['name']} field (C type: {$field['type']})\n";
        $code .= "     */\n";
        $code .= "    private {$phpType} \${$field['name']};\n";

        return $code;
    }

    /**
     * Generate constructor for the struct class
     *
     * @param StructureDefinition $structure Structure definition
     * @return string Constructor code
     */
    private function generateConstructor(StructureDefinition $structure): string
    {
        $parameters = [];
        $assignments = [];

        foreach ($structure->fields as $field) {
            $phpType = $this->typeMapper->mapCTypeToPhp($field['type']);
            $defaultValue = $this->getDefaultValue($phpType);
            
            if ($phpType !== 'mixed') {
                $parameters[] = "{$phpType} \${$field['name']} = {$defaultValue}";
            } else {
                $parameters[] = "\${$field['name']} = {$defaultValue}";
            }
            
            $assignments[] = "        \$this->{$field['name']} = \${$field['name']};";
        }

        $parameterList = implode(', ', $parameters);
        $assignmentCode = implode("\n", $assignments);

        $code = "    /**\n";
        $code .= "     * Constructor\n";
        foreach ($structure->fields as $field) {
            $phpType = $this->typeMapper->mapCTypeToPhp($field['type']);
            $code .= "     * @param {$phpType} \${$field['name']}\n";
        }
        $code .= "     */\n";
        $code .= "    public function __construct({$parameterList})\n";
        $code .= "    {\n";
        $code .= $assignmentCode . "\n";
        $code .= "    }\n";

        return $code;
    }

    /**
     * Generate getter method for a field
     *
     * @param array{name: string, type: string} $field Struct field
     * @return string Getter method code
     */
    private function generateGetter(array $field): string
    {
        $phpType = $this->typeMapper->mapCTypeToPhp($field['type']);
        $methodName = 'get' . ucfirst($field['name']);

        $code = "    /**\n";
        $code .= "     * Get {$field['name']} field\n";
        $code .= "     * @return {$phpType}\n";
        $code .= "     */\n";
        $code .= "    public function {$methodName}(): {$phpType}\n";
        $code .= "    {\n";
        $code .= "        return \$this->{$field['name']};\n";
        $code .= "    }\n";

        return $code;
    }

    /**
     * Generate setter method for a field
     *
     * @param array{name: string, type: string} $field Struct field
     * @return string Setter method code
     */
    private function generateSetter(array $field): string
    {
        $phpType = $this->typeMapper->mapCTypeToPhp($field['type']);
        $methodName = 'set' . ucfirst($field['name']);

        $code = "    /**\n";
        $code .= "     * Set {$field['name']} field\n";
        $code .= "     * @param {$phpType} \${$field['name']}\n";
        $code .= "     */\n";
        $code .= "    public function {$methodName}({$phpType} \${$field['name']}): void\n";
        $code .= "    {\n";
        $code .= "        \$this->{$field['name']} = \${$field['name']};\n";
        $code .= "    }\n";

        return $code;
    }

    /**
     * Generate toArray method
     *
     * @param StructureDefinition $structure Structure definition
     * @return string toArray method code
     */
    private function generateToArrayMethod(StructureDefinition $structure): string
    {
        $code = "    /**\n";
        $code .= "     * Convert struct to array\n";
        $code .= "     * @return array<string, mixed>\n";
        $code .= "     */\n";
        $code .= "    public function toArray(): array\n";
        $code .= "    {\n";
        $code .= "        return [\n";

        foreach ($structure->fields as $field) {
            $code .= "            '{$field['name']}' => \$this->{$field['name']},\n";
        }

        $code .= "        ];\n";
        $code .= "    }\n";

        return $code;
    }

    /**
     * Generate fromArray method
     *
     * @param StructureDefinition $structure Structure definition
     * @return string fromArray method code
     */
    private function generateFromArrayMethod(StructureDefinition $structure): string
    {
        $className = $this->convertStructName($structure->name);

        $code = "    /**\n";
        $code .= "     * Create struct from array\n";
        $code .= "     * @param array<string, mixed> \$data\n";
        $code .= "     * @return self\n";
        $code .= "     */\n";
        $code .= "    public static function fromArray(array \$data): self\n";
        $code .= "    {\n";
        $code .= "        return new self(\n";

        $parameters = [];
        foreach ($structure->fields as $field) {
            $parameters[] = "            \$data['{$field['name']}'] ?? " . $this->getDefaultValue($this->typeMapper->mapCTypeToPhp($field['type']));
        }

        $code .= implode(",\n", $parameters) . "\n";
        $code .= "        );\n";
        $code .= "    }\n";

        return $code;
    }

    /**
     * Get default value for a PHP type
     *
     * @param string $phpType PHP type name
     * @return string Default value
     */
    private function getDefaultValue(string $phpType): string
    {
        return match ($phpType) {
            'int' => '0',
            'float' => '0.0',
            'string' => "''",
            'bool' => 'false',
            'array' => '[]',
            default => 'null'
        };
    }
}