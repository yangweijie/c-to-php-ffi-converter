<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Documentation;

use Yangweijie\CWrapper\Config\ProjectConfig;
use Yangweijie\CWrapper\Generator\GeneratedCode;
use Yangweijie\CWrapper\Generator\WrapperClass;

/**
 * Generates README files with usage instructions
 */
class ReadmeGenerator
{
    /**
     * Generate README content for the generated wrapper
     *
     * @param GeneratedCode $code Generated code
     * @param ProjectConfig $config Project configuration
     * @param string $libraryName Library name
     * @return string Generated README content
     */
    public function generateReadme(GeneratedCode $code, ProjectConfig $config, string $libraryName): string
    {
        $sections = [];
        
        $sections[] = $this->generateTitle($libraryName);
        $sections[] = $this->generateDescription($libraryName);
        $sections[] = $this->generateRequirements();
        $sections[] = $this->generateInstallation($config);
        $sections[] = $this->generateUsage($code, $config);
        $sections[] = $this->generateExamples($code);
        $sections[] = $this->generateDependencies($config);
        $sections[] = $this->generateTroubleshooting();
        $sections[] = $this->generateLicense();
        
        return implode("\n\n", $sections);
    }

    /**
     * Generate title section
     *
     * @param string $libraryName Library name
     * @return string Title section
     */
    private function generateTitle(string $libraryName): string
    {
        return "# {$libraryName} PHP FFI Wrapper\n\nPHP FFI wrapper for the {$libraryName} C library, providing object-oriented access to C functions with automatic parameter validation and type conversion.";
    }

    /**
     * Generate description section
     *
     * @param string $libraryName Library name
     * @return string Description section
     */
    private function generateDescription(string $libraryName): string
    {
        return "## Description\n\nThis package provides a PHP wrapper for the {$libraryName} C library using PHP's FFI (Foreign Function Interface). The wrapper includes:\n\n- Object-oriented interface to C functions\n- Automatic parameter validation\n- Type conversion between PHP and C types\n- Comprehensive error handling\n- Full PHPDoc documentation";
    }

    /**
     * Generate requirements section
     *
     * @return string Requirements section
     */
    private function generateRequirements(): string
    {
        return "## Requirements\n\n- PHP 8.1 or higher\n- FFI extension enabled\n- The C library installed on your system\n- Composer for dependency management";
    }

    /**
     * Generate installation section
     *
     * @param ProjectConfig $config Project configuration
     * @return string Installation section
     */
    private function generateInstallation(ProjectConfig $config): string
    {
        $libraryFile = $config->getLibraryFile();
        $libraryPath = $libraryFile ? basename($libraryFile) : 'your_library.so';
        
        return "## Installation\n\n### 1. Install the C Library\n\nMake sure the C library is installed on your system. The library file should be accessible at:\n```\n{$libraryPath}\n```\n\n### 2. Install PHP Package\n\n```bash\ncomposer require your-vendor/package-name\n```\n\n### 3. Enable FFI Extension\n\nEnsure the FFI extension is enabled in your PHP configuration:\n```ini\nffi.enable=1\n```";
    }

    /**
     * Generate usage section
     *
     * @param GeneratedCode $code Generated code
     * @param ProjectConfig $config Project configuration
     * @return string Usage section
     */
    private function generateUsage(GeneratedCode $code, ProjectConfig $config): string
    {
        $namespace = $config->getNamespace();
        $firstClass = !empty($code->classes) ? $code->classes[0] : null;
        
        if (!$firstClass) {
            return "## Usage\n\nNo wrapper classes were generated.";
        }
        
        $className = $firstClass->name;
        $fullClassName = $namespace . '\\' . $className;
        
        return "## Usage\n\n### Basic Usage\n\n```php\n<?php\n\nrequire_once 'vendor/autoload.php';\n\nuse {$fullClassName};\n\n// Create wrapper instance\n\$wrapper = new {$className}();\n\n// Call C functions through the wrapper\n// \$result = \$wrapper->someMethod(\$param1, \$param2);\n```\n\n### Error Handling\n\n```php\n<?php\n\nuse {$fullClassName};\nuse Yangweijie\\CWrapper\\Exception\\ValidationException;\n\ntry {\n    \$wrapper = new {$className}();\n    \$result = \$wrapper->someMethod(\$invalidParam);\n} catch (ValidationException \$e) {\n    echo \"Parameter validation failed: \" . \$e->getMessage();\n} catch (\\FFI\\Exception \$e) {\n    echo \"FFI error: \" . \$e->getMessage();\n}\n```";
    }

    /**
     * Generate examples section
     *
     * @param GeneratedCode $code Generated code
     * @return string Examples section
     */
    private function generateExamples(GeneratedCode $code): string
    {
        if (empty($code->classes)) {
            return "## Examples\n\nNo examples available - no wrapper classes were generated.";
        }
        
        $examples = [];
        $examples[] = "## Examples";
        
        foreach (array_slice($code->classes, 0, 2) as $class) { // Limit to first 2 classes
            $examples[] = $this->generateClassExample($class);
        }
        
        return implode("\n\n", $examples);
    }

    /**
     * Generate example for a specific class
     *
     * @param WrapperClass $class Wrapper class
     * @return string Class example
     */
    private function generateClassExample(WrapperClass $class): string
    {
        $example = "### {$class->name} Example\n\n```php\n<?php\n\nuse {$class->namespace}\\{$class->name};\n\n\$wrapper = new {$class->name}();\n\n";
        
        // Add examples for first few methods
        $methodCount = 0;
        foreach (array_slice($class->methods, 0, 3) as $method) { // Limit to first 3 methods
            if ($methodCount > 0) {
                $example .= "\n";
            }
            $example .= $this->generateMethodExample($method);
            $methodCount++;
        }
        
        $example .= "\n```";
        
        return $example;
    }

    /**
     * Generate example for a method
     *
     * @param string $method Method code
     * @return string Method example
     */
    private function generateMethodExample(string $method): string
    {
        // Extract method name from method code (simplified)
        if (preg_match('/public function (\w+)\(([^)]*)\)/', $method, $matches)) {
            $methodName = $matches[1];
            $params = $matches[2];
            
            if (empty(trim($params))) {
                return "// Call method without parameters\n\$result = \$wrapper->{$methodName}();";
            } else {
                return "// Call method with parameters\n// \$result = \$wrapper->{$methodName}(\$param1, \$param2);";
            }
        }
        
        return "// Example method call\n// \$result = \$wrapper->someMethod();";
    }

    /**
     * Generate dependencies section
     *
     * @param ProjectConfig $config Project configuration
     * @return string Dependencies section
     */
    private function generateDependencies(ProjectConfig $config): string
    {
        $libraryFile = $config->getLibraryFile();
        $headerFiles = $config->getHeaderFiles();
        
        $content = "## Dependencies\n\n### C Library Dependencies\n\n";
        
        if ($libraryFile) {
            $content .= "- **Library File**: `{$libraryFile}`\n";
        }
        
        if (!empty($headerFiles)) {
            $content .= "- **Header Files**:\n";
            foreach ($headerFiles as $header) {
                $content .= "  - `{$header}`\n";
            }
        }
        
        $content .= "\n### System Requirements\n\n";
        $content .= "Make sure the C library is properly installed and accessible to PHP. ";
        $content .= "The library should be in your system's library path or you may need to ";
        $content .= "set the `LD_LIBRARY_PATH` environment variable.\n\n";
        $content .= "```bash\n# Example for Linux/macOS\nexport LD_LIBRARY_PATH=/path/to/library:\$LD_LIBRARY_PATH\n```";
        
        return $content;
    }

    /**
     * Generate troubleshooting section
     *
     * @return string Troubleshooting section
     */
    private function generateTroubleshooting(): string
    {
        return "## Troubleshooting\n\n### Common Issues\n\n#### FFI Extension Not Enabled\n\n```\nFatal error: Uncaught Error: Class 'FFI' not found\n```\n\n**Solution**: Enable the FFI extension in your PHP configuration:\n```ini\nffi.enable=1\n```\n\n#### Library Not Found\n\n```\nFFI\\Exception: Failed loading 'library.so'\n```\n\n**Solution**: \n- Ensure the C library is installed\n- Check the library path is correct\n- Set `LD_LIBRARY_PATH` if necessary\n\n#### Parameter Validation Errors\n\n```\nValidationException: Parameter validation failed\n```\n\n**Solution**: \n- Check parameter types match the expected C types\n- Ensure parameter values are within valid ranges\n- Review the method documentation for parameter requirements\n\n### Getting Help\n\nIf you encounter issues:\n1. Check the generated PHPDoc comments for method documentation\n2. Verify your C library installation\n3. Ensure FFI is properly configured\n4. Review the parameter validation requirements";
    }

    /**
     * Generate license section
     *
     * @return string License section
     */
    private function generateLicense(): string
    {
        return "## License\n\nThis generated wrapper code is provided as-is. Please refer to your project's license for usage terms.\n\nThe underlying C library may have its own license terms that you must comply with.";
    }
}