<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Generator;

use Yangweijie\CWrapper\Integration\ProcessedBindings;
use Yangweijie\CWrapper\Documentation\Documentation;
use Yangweijie\CWrapper\Config\ProjectConfig;

/**
 * Main wrapper generator that coordinates all sub-generators
 */
class WrapperGenerator implements GeneratorInterface
{
    private ClassGenerator $classGenerator;
    private StructGenerator $structGenerator;
    private ConstantGenerator $constantGenerator;
    private TemplateEngine $templateEngine;

    public function __construct(
        ?ClassGenerator $classGenerator = null,
        ?StructGenerator $structGenerator = null,
        ?ConstantGenerator $constantGenerator = null,
        ?TemplateEngine $templateEngine = null
    ) {
        $this->templateEngine = $templateEngine ?? new TemplateEngine();
        $this->classGenerator = $classGenerator ?? new ClassGenerator(null, $this->templateEngine);
        $this->structGenerator = $structGenerator ?? new StructGenerator(null, $this->templateEngine);
        $this->constantGenerator = $constantGenerator ?? new ConstantGenerator($this->templateEngine);
    }

    /**
     * Generate wrapper code from processed bindings
     *
     * @param ProcessedBindings $bindings Processed bindings to generate from
     * @return GeneratedCode Generated code result
     */
    public function generate(ProcessedBindings $bindings): GeneratedCode
    {
        $classes = [];
        $interfaces = [];
        $traits = [];

        // Group functions by common prefixes to create logical classes
        $functionGroups = $this->groupFunctionsByPrefix($bindings->functions);

        // Generate wrapper classes for function groups
        foreach ($functionGroups as $groupName => $functions) {
            $className = $this->convertGroupNameToClassName($groupName);
            $namespace = 'Generated\\Wrapper';
            
            $wrapperClass = $this->classGenerator->generateClass(
                $className,
                $namespace,
                $functions,
                [],
                []
            );
            
            $classes[] = $wrapperClass;
        }

        // Generate struct classes
        foreach ($bindings->structures as $structure) {
            $wrapperClass = $this->structGenerator->generateStructClass(
                $structure,
                'Generated\\Struct'
            );
            
            $classes[] = $wrapperClass;
        }

        // Generate constants class if there are constants
        if (!empty($bindings->constants)) {
            $constantsClass = $this->constantGenerator->generateConstantsClass(
                $bindings->constants,
                'Generated\\Constants',
                'Constants'
            );
            
            $classes[] = $constantsClass;
        }

        // Generate documentation (placeholder for now)
        $documentation = new Documentation(
            [],
            'Auto-generated PHP FFI wrapper classes',
            []
        );

        return new GeneratedCode($classes, $interfaces, $traits, $documentation);
    }

    /**
     * Generate all code files from generated code
     *
     * @param GeneratedCode $generatedCode Generated code to write
     * @param ProjectConfig $config Project configuration
     * @return array<string, string> Array of filename => content
     */
    public function generateCodeFiles(GeneratedCode $generatedCode, ProjectConfig $config): array
    {
        $files = [];

        foreach ($generatedCode->classes as $class) {
            $filename = $this->getClassFilename($class);
            
            // Determine the type of class and generate appropriate code
            if (str_contains($class->namespace, 'Struct')) {
                // This is a struct class - we need the original structure definition
                // For now, use the legacy method
                $content = $this->generateStructClassContent($class);
            } elseif (str_contains($class->namespace, 'Constants')) {
                $content = $this->constantGenerator->generateConstantsClassCode($class);
            } else {
                $content = $this->classGenerator->generateClassCode($class, $config->getLibraryFile());
            }
            
            $files[$filename] = $content;
        }

        return $files;
    }

    /**
     * Group functions by common prefixes
     *
     * @param array<\Yangweijie\CWrapper\Analyzer\FunctionSignature> $functions Functions to group
     * @return array<string, array<\Yangweijie\CWrapper\Analyzer\FunctionSignature>> Grouped functions
     */
    private function groupFunctionsByPrefix(array $functions): array
    {
        $groups = [];

        foreach ($functions as $function) {
            $prefix = $this->extractFunctionPrefix($function->name);
            
            if (!isset($groups[$prefix])) {
                $groups[$prefix] = [];
            }
            
            $groups[$prefix][] = $function;
        }

        return $groups;
    }

    /**
     * Extract function prefix for grouping
     *
     * @param string $functionName Function name
     * @return string Function prefix
     */
    private function extractFunctionPrefix(string $functionName): string
    {
        // Extract prefix before first underscore
        $parts = explode('_', $functionName);
        return $parts[0] ?? 'General';
    }

    /**
     * Convert group name to class name
     *
     * @param string $groupName Group name
     * @return string Class name
     */
    private function convertGroupNameToClassName(string $groupName): string
    {
        return ucfirst($groupName) . 'Wrapper';
    }

    /**
     * Get filename for a wrapper class
     *
     * @param WrapperClass $class Wrapper class
     * @return string Filename
     */
    private function getClassFilename(WrapperClass $class): string
    {
        return $class->name . '.php';
    }

    /**
     * Generate struct class content (temporary method)
     *
     * @param WrapperClass $class Wrapper class
     * @return string Class content
     */
    private function generateStructClassContent(WrapperClass $class): string
    {
        // This is a simplified version - in a real implementation,
        // we would need access to the original StructureDefinition
        $code = "<?php\n\n";
        $code .= "declare(strict_types=1);\n\n";
        $code .= "namespace {$class->namespace};\n\n";
        $code .= "use FFI;\n\n";
        $code .= "/**\n";
        $code .= " * Generated wrapper class for C struct {$class->name}\n";
        $code .= " */\n";
        $code .= "class {$class->name}\n";
        $code .= "{\n";

        // Add properties
        foreach ($class->properties as $property) {
            $code .= $property . "\n";
        }

        $code .= "\n";

        // Add methods
        foreach ($class->methods as $method) {
            $code .= $method . "\n";
        }

        $code .= "}\n";

        return $code;
    }
}