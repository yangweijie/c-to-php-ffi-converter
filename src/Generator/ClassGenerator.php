<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Generator;

use Yangweijie\CWrapper\Analyzer\FunctionSignature;
use Yangweijie\CWrapper\Analyzer\StructureDefinition;

/**
 * Generates PHP wrapper classes from C function groups
 */
class ClassGenerator
{
    private MethodGenerator $methodGenerator;
    private TemplateEngine $templateEngine;

    public function __construct(?MethodGenerator $methodGenerator = null, ?TemplateEngine $templateEngine = null)
    {
        $this->methodGenerator = $methodGenerator ?? new MethodGenerator();
        $this->templateEngine = $templateEngine ?? new TemplateEngine();
    }

    /**
     * Generate a wrapper class from a group of functions
     *
     * @param string $className Name of the class to generate
     * @param string $namespace Namespace for the class
     * @param array<FunctionSignature> $functions Functions to include in the class
     * @param array<StructureDefinition> $structures Structures to include as properties
     * @param array<string, mixed> $constants Constants to include in the class
     * @param string $generationType Generation type: 'object' or 'functional'
     * @return WrapperClass Generated wrapper class
     */
    public function generateClass(
        string $className,
        string $namespace,
        array $functions,
        array $structures = [],
        array $constants = [],
        string $generationType = 'object'
    ): WrapperClass {
        $methods = [];
        $properties = [];
        $classConstants = [];

        // Generate methods from functions
        foreach ($functions as $function) {
            $methods[] = $this->methodGenerator->generateMethod($function, $generationType, $className);
        }

        // Generate properties from structures
        foreach ($structures as $structure) {
            $properties[] = $this->generatePropertyFromStruct($structure);
        }

        // Process constants
        foreach ($constants as $name => $value) {
            $classConstants[$name] = $value;
        }

        return new WrapperClass(
            $className,
            $namespace,
            $methods,
            $properties,
            $classConstants
        );
    }

    /**
     * Generate a property definition from a C structure
     *
     * @param StructureDefinition $structure Structure to convert to property
     * @return string Property definition code
     */
    private function generatePropertyFromStruct(StructureDefinition $structure): string
    {
        $propertyName = lcfirst($structure->name);
        $structType = $structure->isUnion ? 'union' : 'struct';
        
        $code = "    /**\n";
        $code .= "     * {$structure->name} {$structType}\n";
        $code .= "     */\n";
        $code .= "    private \\FFI\\CData \${$propertyName};\n";

        return $code;
    }

    /**
     * Generate complete class code using templates
     *
     * @param WrapperClass $wrapperClass Wrapper class to generate code for
     * @param string $libraryPath Path to the C library
     * @return string Complete PHP class code
     */
    public function generateClassCode(WrapperClass $wrapperClass, string $libraryPath): string
    {
        return $this->templateEngine->renderWrapperClass($wrapperClass, $libraryPath);
    }

    /**
     * Generate complete class code (legacy method for backward compatibility)
     *
     * @param WrapperClass $wrapperClass Wrapper class to generate code for
     * @param string $libraryPath Path to the C library
     * @return string Complete PHP class code
     */
    public function generateClassCodeLegacy(WrapperClass $wrapperClass, string $libraryPath): string
    {
        $code = "<?php\n\n";
        $code .= "declare(strict_types=1);\n\n";
        $code .= "namespace {$wrapperClass->namespace};\n\n";
        $code .= "use FFI;\n\n";
        $code .= "/**\n";
        $code .= " * Generated wrapper class for {$wrapperClass->name}\n";
        $code .= " */\n";
        $code .= "class {$wrapperClass->name}\n";
        $code .= "{\n";
        $code .= "    private FFI \$ffi;\n\n";

        // Add constants
        if (!empty($wrapperClass->constants)) {
            foreach ($wrapperClass->constants as $name => $value) {
                $code .= "    public const {$name} = " . var_export($value, true) . ";\n";
            }
            $code .= "\n";
        }

        // Add properties
        foreach ($wrapperClass->properties as $property) {
            $code .= $property . "\n";
        }

        if (!empty($wrapperClass->properties)) {
            $code .= "\n";
        }

        // Add constructor
        $code .= "    public function __construct()\n";
        $code .= "    {\n";
        $code .= "        \$this->ffi = FFI::cdef('', '{$libraryPath}');\n";
        $code .= "    }\n\n";

        // Add methods
        foreach ($wrapperClass->methods as $method) {
            $code .= $method . "\n";
        }

        $code .= "}\n";

        return $code;
    }
}