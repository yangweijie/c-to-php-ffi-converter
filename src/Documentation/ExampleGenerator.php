<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Documentation;

use Yangweijie\CWrapper\Analyzer\FunctionSignature;
use Yangweijie\CWrapper\Config\ProjectConfig;
use Yangweijie\CWrapper\Generator\GeneratedCode;
use Yangweijie\CWrapper\Generator\WrapperClass;

/**
 * Generates code examples for common usage patterns
 */
class ExampleGenerator
{
    /**
     * Generate usage examples for generated wrapper classes
     *
     * @param GeneratedCode $code Generated code
     * @param ProjectConfig $config Project configuration
     * @param array<FunctionSignature> $signatures Function signatures
     * @return array<string> Generated examples
     */
    public function generateExamples(GeneratedCode $code, ProjectConfig $config, array $signatures): array
    {
        $examples = [];
        
        // Generate basic usage example
        $examples['basic_usage'] = $this->generateBasicUsageExample($code, $config);
        
        // Generate error handling example
        $examples['error_handling'] = $this->generateErrorHandlingExample($code, $config);
        
        // Generate advanced usage examples for each class
        foreach ($code->classes as $class) {
            $classSignatures = $this->getSignaturesForClass($class, $signatures);
            $examples["class_{$class->name}"] = $this->generateClassUsageExample($class, $classSignatures, $config);
        }
        
        // Generate common patterns example
        $examples['common_patterns'] = $this->generateCommonPatternsExample($code, $signatures, $config);
        
        return $examples;
    }

    /**
     * Generate basic usage example
     *
     * @param GeneratedCode $code Generated code
     * @param ProjectConfig $config Project configuration
     * @return string Basic usage example
     */
    private function generateBasicUsageExample(GeneratedCode $code, ProjectConfig $config): string
    {
        if (empty($code->classes)) {
            return "<?php\n\n// No wrapper classes were generated\n";
        }
        
        $firstClass = $code->classes[0];
        $namespace = $config->getNamespace();
        $fullClassName = $namespace . '\\' . $firstClass->name;
        
        $example = "<?php\n\n";
        $example .= "/**\n";
        $example .= " * Basic Usage Example\n";
        $example .= " * \n";
        $example .= " * This example demonstrates basic usage of the generated FFI wrapper.\n";
        $example .= " */\n\n";
        $example .= "require_once 'vendor/autoload.php';\n\n";
        $example .= "use {$fullClassName};\n";
        $example .= "use Yangweijie\\CWrapper\\Exception\\ValidationException;\n\n";
        $example .= "try {\n";
        $example .= "    // Create wrapper instance\n";
        $example .= "    \$wrapper = new {$firstClass->name}();\n\n";
        $example .= "    // Example method calls\n";
        
        // Add example method calls
        $methodCount = 0;
        foreach (array_slice($firstClass->methods, 0, 2) as $method) {
            if ($methodCount > 0) {
                $example .= "\n";
            }
            $example .= $this->generateMethodCallExample($method, '    ');
            $methodCount++;
        }
        
        $example .= "\n} catch (ValidationException \$e) {\n";
        $example .= "    echo \"Parameter validation failed: \" . \$e->getMessage() . \"\\n\";\n";
        $example .= "} catch (\\FFI\\Exception \$e) {\n";
        $example .= "    echo \"FFI error: \" . \$e->getMessage() . \"\\n\";\n";
        $example .= "} catch (\\Exception \$e) {\n";
        $example .= "    echo \"General error: \" . \$e->getMessage() . \"\\n\";\n";
        $example .= "}\n";
        
        return $example;
    }

    /**
     * Generate error handling example
     *
     * @param GeneratedCode $code Generated code
     * @param ProjectConfig $config Project configuration
     * @return string Error handling example
     */
    private function generateErrorHandlingExample(GeneratedCode $code, ProjectConfig $config): string
    {
        if (empty($code->classes)) {
            return "<?php\n\n// No wrapper classes were generated\n";
        }
        
        $firstClass = $code->classes[0];
        $namespace = $config->getNamespace();
        $fullClassName = $namespace . '\\' . $firstClass->name;
        
        $example = "<?php\n\n";
        $example .= "/**\n";
        $example .= " * Error Handling Example\n";
        $example .= " * \n";
        $example .= " * This example demonstrates proper error handling when using the FFI wrapper.\n";
        $example .= " */\n\n";
        $example .= "require_once 'vendor/autoload.php';\n\n";
        $example .= "use {$fullClassName};\n";
        $example .= "use Yangweijie\\CWrapper\\Exception\\ValidationException;\n";
        $example .= "use Yangweijie\\CWrapper\\Exception\\FFIConverterException;\n\n";
        $example .= "function safeWrapperCall(callable \$callback): mixed\n";
        $example .= "{\n";
        $example .= "    try {\n";
        $example .= "        return \$callback();\n";
        $example .= "    } catch (ValidationException \$e) {\n";
        $example .= "        // Handle parameter validation errors\n";
        $example .= "        error_log(\"Validation error: \" . \$e->getMessage());\n";
        $example .= "        return null;\n";
        $example .= "    } catch (\\FFI\\Exception \$e) {\n";
        $example .= "        // Handle FFI-specific errors\n";
        $example .= "        error_log(\"FFI error: \" . \$e->getMessage());\n";
        $example .= "        return null;\n";
        $example .= "    } catch (FFIConverterException \$e) {\n";
        $example .= "        // Handle converter-specific errors\n";
        $example .= "        error_log(\"Converter error: \" . \$e->getMessage());\n";
        $example .= "        return null;\n";
        $example .= "    }\n";
        $example .= "}\n\n";
        $example .= "\$wrapper = new {$firstClass->name}();\n\n";
        $example .= "// Safe method calls with error handling\n";
        $example .= "\$result1 = safeWrapperCall(fn() => \$wrapper->someMethod('valid_param'));\n";
        $example .= "\$result2 = safeWrapperCall(fn() => \$wrapper->anotherMethod(42));\n\n";
        $example .= "if (\$result1 !== null) {\n";
        $example .= "    echo \"Method 1 succeeded: \" . \$result1 . \"\\n\";\n";
        $example .= "} else {\n";
        $example .= "    echo \"Method 1 failed\\n\";\n";
        $example .= "}\n";
        
        return $example;
    }

    /**
     * Generate class-specific usage example
     *
     * @param WrapperClass $class Wrapper class
     * @param array<FunctionSignature> $signatures Function signatures for this class
     * @param ProjectConfig $config Project configuration
     * @return string Class usage example
     */
    private function generateClassUsageExample(WrapperClass $class, array $signatures, ProjectConfig $config): string
    {
        $namespace = $config->getNamespace();
        $fullClassName = $namespace . '\\' . $class->name;
        
        $example = "<?php\n\n";
        $example .= "/**\n";
        $example .= " * {$class->name} Usage Example\n";
        $example .= " * \n";
        $example .= " * This example demonstrates usage of the {$class->name} wrapper class.\n";
        $example .= " */\n\n";
        $example .= "require_once 'vendor/autoload.php';\n\n";
        $example .= "use {$fullClassName};\n\n";
        $example .= "\$wrapper = new {$class->name}();\n\n";
        
        // Generate examples for each method
        foreach (array_slice($signatures, 0, 5) as $signature) { // Limit to 5 methods
            $example .= $this->generateSignatureExample($signature);
            $example .= "\n";
        }
        
        return $example;
    }

    /**
     * Generate common patterns example
     *
     * @param GeneratedCode $code Generated code
     * @param array<FunctionSignature> $signatures Function signatures
     * @param ProjectConfig $config Project configuration
     * @return string Common patterns example
     */
    private function generateCommonPatternsExample(GeneratedCode $code, array $signatures, ProjectConfig $config): string
    {
        $example = "<?php\n\n";
        $example .= "/**\n";
        $example .= " * Common Usage Patterns\n";
        $example .= " * \n";
        $example .= " * This example demonstrates common patterns when working with C libraries via FFI.\n";
        $example .= " */\n\n";
        $example .= "require_once 'vendor/autoload.php';\n\n";
        
        if (!empty($code->classes)) {
            $firstClass = $code->classes[0];
            $namespace = $config->getNamespace();
            $fullClassName = $namespace . '\\' . $firstClass->name;
            
            $example .= "use {$fullClassName};\n\n";
            $example .= "class LibraryManager\n";
            $example .= "{\n";
            $example .= "    private {$firstClass->name} \$wrapper;\n\n";
            $example .= "    public function __construct()\n";
            $example .= "    {\n";
            $example .= "        \$this->wrapper = new {$firstClass->name}();\n";
            $example .= "    }\n\n";
            $example .= "    public function initialize(): bool\n";
            $example .= "    {\n";
            $example .= "        try {\n";
            $example .= "            // Initialize the library\n";
            $example .= "            // \$this->wrapper->init();\n";
            $example .= "            return true;\n";
            $example .= "        } catch (\\Exception \$e) {\n";
            $example .= "            error_log(\"Library initialization failed: \" . \$e->getMessage());\n";
            $example .= "            return false;\n";
            $example .= "        }\n";
            $example .= "    }\n\n";
            $example .= "    public function cleanup(): void\n";
            $example .= "    {\n";
            $example .= "        try {\n";
            $example .= "            // Cleanup resources\n";
            $example .= "            // \$this->wrapper->cleanup();\n";
            $example .= "        } catch (\\Exception \$e) {\n";
            $example .= "            error_log(\"Cleanup failed: \" . \$e->getMessage());\n";
            $example .= "        }\n";
            $example .= "    }\n\n";
            $example .= "    public function processData(string \$data): ?string\n";
            $example .= "    {\n";
            $example .= "        try {\n";
            $example .= "            // Process data through the C library\n";
            $example .= "            // return \$this->wrapper->processString(\$data);\n";
            $example .= "            return \$data; // Placeholder\n";
            $example .= "        } catch (\\Exception \$e) {\n";
            $example .= "            error_log(\"Data processing failed: \" . \$e->getMessage());\n";
            $example .= "            return null;\n";
            $example .= "        }\n";
            $example .= "    }\n";
            $example .= "}\n\n";
            $example .= "// Usage\n";
            $example .= "\$manager = new LibraryManager();\n";
            $example .= "if (\$manager->initialize()) {\n";
            $example .= "    \$result = \$manager->processData('example data');\n";
            $example .= "    if (\$result !== null) {\n";
            $example .= "        echo \"Processed: \" . \$result . \"\\n\";\n";
            $example .= "    }\n";
            $example .= "    \$manager->cleanup();\n";
            $example .= "}\n";
        }
        
        return $example;
    }

    /**
     * Generate example for a specific function signature
     *
     * @param FunctionSignature $signature Function signature
     * @return string Signature example
     */
    private function generateSignatureExample(FunctionSignature $signature): string
    {
        $methodName = $this->convertFunctionNameToMethodName($signature->name);
        
        $example = "// {$signature->name}() wrapper\n";
        
        if (empty($signature->parameters)) {
            $example .= "\$result = \$wrapper->{$methodName}();\n";
        } else {
            $params = [];
            foreach ($signature->parameters as $param) {
                $params[] = $this->generateExampleValue($param['type'], $param['name']);
            }
            $example .= "\$result = \$wrapper->{$methodName}(" . implode(', ', $params) . ");\n";
        }
        
        if ($signature->returnType !== 'void') {
            $example .= "echo \"Result: \" . \$result . \"\\n\";\n";
        }
        
        return $example;
    }

    /**
     * Generate method call example from method code
     *
     * @param string $method Method code
     * @param string $indent Indentation
     * @return string Method call example
     */
    private function generateMethodCallExample(string $method, string $indent = ''): string
    {
        // Extract method name and parameters (simplified parsing)
        if (preg_match('/public function (\w+)\(([^)]*)\)/', $method, $matches)) {
            $methodName = $matches[1];
            $params = trim($matches[2]);
            
            if (empty($params)) {
                return $indent . "// \$result = \$wrapper->{$methodName}();";
            } else {
                return $indent . "// \$result = \$wrapper->{$methodName}(\$param1, \$param2);";
            }
        }
        
        return $indent . "// \$result = \$wrapper->someMethod();";
    }

    /**
     * Get function signatures that belong to a specific class
     *
     * @param WrapperClass $class Wrapper class
     * @param array<FunctionSignature> $signatures All function signatures
     * @return array<FunctionSignature> Class-specific signatures
     */
    private function getSignaturesForClass(WrapperClass $class, array $signatures): array
    {
        // For now, return all signatures. In a real implementation,
        // you would filter based on some criteria (e.g., function name prefix)
        return array_slice($signatures, 0, 5); // Limit to 5 for examples
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
}