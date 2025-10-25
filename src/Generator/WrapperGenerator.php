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
    private MethodGenerator $methodGenerator;

    public function __construct(
        ?ClassGenerator $classGenerator = null,
        ?StructGenerator $structGenerator = null,
        ?ConstantGenerator $constantGenerator = null,
        ?TemplateEngine $templateEngine = null,
        ?MethodGenerator $methodGenerator = null
    ) {
        $this->templateEngine = $templateEngine ?? new TemplateEngine();
        $this->methodGenerator = $methodGenerator ?? new MethodGenerator();
        $this->classGenerator = $classGenerator ?? new ClassGenerator($this->methodGenerator, $this->templateEngine);
        $this->structGenerator = $structGenerator ?? new StructGenerator(null, $this->templateEngine);
        $this->constantGenerator = $constantGenerator ?? new ConstantGenerator($this->templateEngine);
    }

    /**
     * Generate wrapper code from processed bindings
     *
     * @param ProcessedBindings $bindings Processed bindings to generate from
     * @param ProjectConfig|null $config Project configuration for namespace and other settings
     * @return GeneratedCode Generated code result
     */
    public function generate(ProcessedBindings $bindings, ?ProjectConfig $config = null): GeneratedCode
    {
        $classes = [];
        $interfaces = [];
        $traits = [];

        // Determine namespace to use
        $baseNamespace = $config ? $config->getNamespace() : 'Generated\\Wrapper';
        $generationType = $config ? $config->getGenerationType() : 'object';
        
        // Try to use improved generation if Methods.php exists
        $outputPath = $config ? $config->getOutputPath() : './generated';
        $methodsFilePath = $outputPath . '/Methods.php';
        
        if (file_exists($methodsFilePath)) {
            // Use improved generation based on klitsche/ffigen output
            $classes = $this->generateImprovedClasses($methodsFilePath, $baseNamespace, $generationType);
        } else {
            // Fallback to original generation
            if ($generationType === 'object') {
                $functionGroups = $this->groupFunctionsByPrefix($bindings->functions);
                
                foreach ($functionGroups as $groupName => $functions) {
                    $className = $this->convertGroupNameToClassName($groupName);
                    
                    $wrapperClass = $this->classGenerator->generateClass(
                        $className,
                        $baseNamespace,
                        $functions,
                        [],
                        [],
                        $generationType
                    );
                    
                    $classes[] = $wrapperClass;
                }
            } else {
                $wrapperClass = $this->generateFunctionalWrapper($bindings->functions, $baseNamespace);
                $classes[] = $wrapperClass;
            }
        }

        // Generate struct classes
        foreach ($bindings->structures as $structure) {
            $wrapperClass = $this->structGenerator->generateStructClass(
                $structure,
                $baseNamespace . '\\Struct'
            );
            
            $classes[] = $wrapperClass;
        }

        // Generate constants class if there are constants
        if (!empty($bindings->constants)) {
            $constantsClass = $this->constantGenerator->generateConstantsClass(
                $bindings->constants,
                $baseNamespace,
                'Constants'
            );
            
            $classes[] = $constantsClass;
        }

        // Generate Bootstrap class for centralized FFI management
        if ($config) {
            $bootstrapClass = $this->generateBootstrapClass($config, $baseNamespace);
            $classes[] = $bootstrapClass;
        }

        // Create temporary GeneratedCode object for documentation generation
        $tempDocumentation = new Documentation([], '', []);
        $generatedCode = new GeneratedCode($classes, $interfaces, $traits, $tempDocumentation);
        
        // Generate proper documentation
        $documentation = $this->generateDocumentation($generatedCode, $config);

        // Return with proper documentation
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
            if ($class->name === 'Bootstrap') {
                // Special handling for Bootstrap class
                $content = $this->generateBootstrapClassCode($class);
            } elseif (str_contains($class->namespace, 'Struct')) {
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
     * Extract semantic component name for grouping
     *
     * @param string $functionName Function name
     * @return string Component name for grouping
     */
    private function extractFunctionPrefix(string $functionName): string
    {
        // Handle UI library functions with semantic grouping
        if (str_starts_with($functionName, 'ui')) {
            return $this->extractUiComponentName($functionName);
        }
        
        // For other libraries, use improved prefix extraction
        $parts = explode('_', $functionName);
        
        // Try to identify component patterns
        if (count($parts) >= 2) {
            $prefix = $parts[0];
            $component = $parts[1];
            
            // Look for "New" pattern (e.g., "uiNewButton" -> "Button")
            if (str_starts_with($component, 'New')) {
                return substr($component, 3); // Remove "New" prefix
            }
            
            // Look for component names in function name
            $componentName = $this->identifyComponentFromFunction($functionName);
            if ($componentName) {
                return $componentName;
            }
        }
        
        return $parts[0] ?? 'General';
    }

    /**
     * Extract UI component name from UI library functions
     *
     * @param string $functionName Function name starting with 'ui'
     * @return string Component name
     */
    private function extractUiComponentName(string $functionName): string
    {
        // Define UI component patterns
        $componentPatterns = [
            'Window' => ['uiWindow', 'uiNewWindow'],
            'Button' => ['uiButton', 'uiNewButton'],
            'Box' => ['uiBox', 'uiNewHorizontalBox', 'uiNewVerticalBox'],
            'Checkbox' => ['uiCheckbox', 'uiNewCheckbox'],
            'Entry' => ['uiEntry', 'uiNewEntry', 'uiNewPasswordEntry', 'uiNewSearchEntry'],
            'Label' => ['uiLabel', 'uiNewLabel'],
            'Tab' => ['uiTab', 'uiNewTab'],
            'Group' => ['uiGroup', 'uiNewGroup'],
            'Spinbox' => ['uiSpinbox', 'uiNewSpinbox'],
            'Slider' => ['uiSlider', 'uiNewSlider'],
            'ProgressBar' => ['uiProgressBar', 'uiNewProgressBar'],
            'Separator' => ['uiSeparator', 'uiNewHorizontalSeparator', 'uiNewVerticalSeparator'],
            'Combobox' => ['uiCombobox', 'uiNewCombobox', 'uiEditableCombobox', 'uiNewEditableCombobox'],
            'RadioButtons' => ['uiRadioButtons', 'uiNewRadioButtons'],
            'DateTimePicker' => ['uiDateTimePicker', 'uiNewDateTimePicker', 'uiNewDatePicker', 'uiNewTimePicker'],
            'MultilineEntry' => ['uiMultilineEntry', 'uiNewMultilineEntry', 'uiNewNonWrappingMultilineEntry'],
            'MenuItem' => ['uiMenuItem'],
            'Menu' => ['uiMenu', 'uiNewMenu'],
            'Area' => ['uiArea', 'uiNewArea', 'uiNewScrollingArea'],
            'DrawPath' => ['uiDrawPath', 'uiDrawNewPath'],
            'DrawMatrix' => ['uiDrawMatrix'],
            'Attribute' => ['uiAttribute', 'uiNewFamilyAttribute', 'uiNewSizeAttribute', 'uiNewWeightAttribute', 'uiNewItalicAttribute', 'uiNewStretchAttribute', 'uiNewColorAttribute', 'uiNewBackgroundAttribute', 'uiNewUnderlineAttribute', 'uiNewUnderlineColorAttribute'],
            'AttributedString' => ['uiAttributedString', 'uiNewAttributedString'],
            'OpenTypeFeatures' => ['uiOpenTypeFeatures', 'uiNewOpenTypeFeatures'],
            'FontDescriptor' => ['uiFontDescriptor'],
            'DrawTextLayout' => ['uiDrawTextLayout', 'uiDrawNewTextLayout'],
            'FontButton' => ['uiFontButton', 'uiNewFontButton'],
            'ColorButton' => ['uiColorButton', 'uiNewColorButton'],
            'Form' => ['uiForm', 'uiNewForm'],
            'Grid' => ['uiGrid', 'uiNewGrid'],
            'Image' => ['uiImage', 'uiNewImage'],
            'TableValue' => ['uiTableValue', 'uiNewTableValueString', 'uiNewTableValueImage', 'uiNewTableValueInt', 'uiNewTableValueColor'],
            'TableModel' => ['uiTableModel', 'uiNewTableModel'],
            'Table' => ['uiTable', 'uiNewTable'],
            'TableSelection' => ['uiTableSelection'],
            'Control' => ['uiControl'],
        ];

        // Check each component pattern
        foreach ($componentPatterns as $component => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_starts_with($functionName, $pattern)) {
                    return $component;
                }
            }
        }

        // Special handling for "uiNew*" functions - extract component from function name
        if (str_starts_with($functionName, 'uiNew')) {
            $componentName = substr($functionName, 5); // Remove "uiNew" prefix
            
            // Check if this component exists in our patterns
            foreach ($componentPatterns as $component => $patterns) {
                if (strtolower($componentName) === strtolower($component)) {
                    return $component;
                }
            }
            
            // If not found, return the component name as-is
            return $componentName;
        }

        // Check for general UI functions that don't belong to specific components
        $generalUiFunctions = [
            'uiInit', 'uiUninit', 'uiFreeInitError', 'uiMain', 'uiMainSteps', 'uiMainStep', 
            'uiQuit', 'uiQueueMain', 'uiTimer', 'uiOnShouldQuit', 'uiFreeText',
            'uiOpenFile', 'uiSaveFile', 'uiMsgBox', 'uiMsgBoxError',
            'uiAllocControl', 'uiFreeControl', 'uiUserBugCannotSetParentOnToplevel',
            'uiDrawStroke', 'uiDrawFill', 'uiDrawTransform', 'uiDrawClip', 'uiDrawSave', 'uiDrawRestore',
            'uiFreeAttribute', 'uiAttributeGetType', 'uiAttributeFamily', 'uiAttributeSize', 'uiAttributeWeight',
            'uiAttributeItalic', 'uiAttributeStretch', 'uiAttributeColor', 'uiAttributeUnderline', 'uiAttributeUnderlineColor',
            'uiFreeAttributedString', 'uiAttributedStringString', 'uiAttributedStringLen', 'uiAttributedStringAppendUnattributed',
            'uiAttributedStringInsertAtUnattributed', 'uiAttributedStringDelete', 'uiAttributedStringSetAttribute',
            'uiAttributedStringForEachAttribute', 'uiAttributedStringNumGraphemes', 'uiAttributedStringByteIndexToGrapheme',
            'uiAttributedStringGraphemeToByteIndex', 'uiFreeOpenTypeFeatures', 'uiOpenTypeFeaturesClone',
            'uiOpenTypeFeaturesAdd', 'uiOpenTypeFeaturesRemove', 'uiOpenTypeFeaturesGet', 'uiOpenTypeFeaturesForEach',
            'uiNewFeaturesAttribute', 'uiAttributeFeatures', 'uiLoadControlFont', 'uiFreeFontDescriptor',
            'uiDrawFreeTextLayout', 'uiDrawText', 'uiDrawTextLayoutExtents', 'uiFreeFontButtonFont',
            'uiFreeImage', 'uiImageAppend', 'uiFreeTableValue', 'uiTableValueGetType', 'uiTableValueString',
            'uiTableValueImage', 'uiTableValueInt', 'uiTableValueColor', 'uiFreeTableModel', 'uiTableModelRowInserted',
            'uiTableModelRowChanged', 'uiTableModelRowDeleted', 'uiFreeTableSelection'
        ];

        foreach ($generalUiFunctions as $generalFunction) {
            if (str_starts_with($functionName, $generalFunction)) {
                return 'Ui'; // General UI functions
            }
        }

        // Fallback: try to extract component from function name
        return $this->identifyComponentFromFunction($functionName) ?: 'Ui';
    }

    /**
     * Identify component name from function name using heuristics
     *
     * @param string $functionName Function name
     * @return string|null Component name or null if not found
     */
    private function identifyComponentFromFunction(string $functionName): ?string
    {
        // Remove common prefixes
        $cleanName = $functionName;
        $prefixes = ['ui', 'new', 'create', 'make', 'init'];
        
        foreach ($prefixes as $prefix) {
            if (str_starts_with(strtolower($cleanName), $prefix)) {
                $cleanName = substr($cleanName, strlen($prefix));
                break;
            }
        }

        // Look for capital letters that indicate component boundaries
        if (preg_match('/^([A-Z][a-z]+)/', $cleanName, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Convert group name to class name
     *
     * @param string $groupName Group name
     * @return string Class name
     */
    private function convertGroupNameToClassName(string $groupName): string
    {
        // For UI components, use "Ui" prefix
        if ($groupName === 'Ui') {
            return 'Ui'; // General UI functions
        }
        
        // For specific UI components, use "Ui" + component name
        if (in_array($groupName, [
            'Window', 'Button', 'Box', 'Checkbox', 'Entry', 'Label', 'Tab', 'Group',
            'Spinbox', 'Slider', 'ProgressBar', 'Separator', 'Combobox', 'RadioButtons',
            'DateTimePicker', 'MultilineEntry', 'MenuItem', 'Menu', 'Area', 'DrawPath',
            'DrawMatrix', 'Attribute', 'AttributedString', 'OpenTypeFeatures', 'FontDescriptor',
            'DrawTextLayout', 'FontButton', 'ColorButton', 'Form', 'Grid', 'Image',
            'TableValue', 'TableModel', 'Table', 'TableSelection', 'Control'
        ])) {
            return 'Ui' . $groupName;
        }
        
        // For other libraries, use the group name as-is with proper casing
        return ucfirst($groupName);
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

    /**
     * Generate Bootstrap class for centralized FFI management
     *
     * @param ProjectConfig $config Project configuration
     * @param string $namespace Base namespace
     * @return WrapperClass Bootstrap class
     */
    private function generateBootstrapClass(ProjectConfig $config, string $namespace): WrapperClass
    {
        $className = 'Bootstrap';
        $libraryPath = $config->getLibraryFile();
        
        // Create properties
        $properties = [
            'private static ?\\FFI $ffi = null;',
            'public const LIBRARY_PATH = \'' . addslashes($libraryPath) . '\';'
        ];

        // Create methods
        $methods = [
            $this->generateGetFFIMethod(),
            $this->generateInitializeMethod()
        ];

        return new WrapperClass(
            $className,
            $namespace,
            $methods,
            $properties,
            []
        );
    }

    /**
     * Generate getFFI method for Bootstrap class
     *
     * @return string Method code
     */
    private function generateGetFFIMethod(): string
    {
        return '    /**
     * Get shared FFI instance
     *
     * @return \\FFI Shared FFI instance
     */
    public static function getFFI(): \\FFI
    {
        if (self::$ffi === null) {
            self::initialize();
        }
        
        return self::$ffi;
    }';
    }

    /**
     * Generate initialize method for Bootstrap class
     *
     * @return string Method code
     */
    private function generateInitializeMethod(): string
    {
        return '    /**
     * Initialize FFI instance with library
     *
     * @param string|null $headerFile Optional header file path
     * @throws \\RuntimeException If library cannot be loaded
     */
    public static function initialize(?string $headerFile = null): void
    {
        if (self::$ffi !== null) {
            return; // Already initialized
        }

        $headerContent = \'\';
        if ($headerFile && file_exists($headerFile)) {
            $headerContent = file_get_contents($headerFile);
        }

        try {
            self::$ffi = \\FFI::cdef($headerContent, self::LIBRARY_PATH);
        } catch (\\Throwable $e) {
            throw new \\RuntimeException(
                \'Failed to initialize FFI with library: \' . self::LIBRARY_PATH . \'. Error: \' . $e->getMessage(),
                0,
                $e
            );
        }
    }';
    }

    /**
     * Generate Bootstrap class code
     *
     * @param WrapperClass $class Bootstrap class
     * @return string Class code
     */
    private function generateBootstrapClassCode(WrapperClass $class): string
    {
        $code = "<?php\n\n";
        $code .= "declare(strict_types=1);\n\n";
        $code .= "namespace {$class->namespace};\n\n";
        $code .= "use FFI;\n\n";
        $code .= "/**\n";
        $code .= " * Bootstrap class for centralized FFI management\n";
        $code .= " */\n";
        $code .= "class {$class->name}\n";
        $code .= "{\n";

        // Add properties
        foreach ($class->properties as $property) {
            $code .= "    {$property}\n";
        }

        $code .= "\n";

        // Add methods
        foreach ($class->methods as $method) {
            $code .= $method . "\n\n";
        }

        $code .= "}\n";

        return $code;
    }

    /**
     * Generate comprehensive documentation
     *
     * @param GeneratedCode $generatedCode Generated code
     * @param ProjectConfig|null $config Project configuration
     * @return Documentation Generated documentation
     */
    private function generateDocumentation(GeneratedCode $generatedCode, ?ProjectConfig $config = null): Documentation
    {
        $readmeContent = $this->generateReadmeContent($generatedCode, $config);
        $examples = $this->generateUsageExamples($generatedCode, $config);
        
        return new Documentation(
            [], // PHPDoc comments are already in the generated classes
            $readmeContent,
            $examples
        );
    }

    /**
     * Generate README content
     *
     * @param GeneratedCode $generatedCode Generated code
     * @param ProjectConfig|null $config Project configuration
     * @return string README content
     */
    private function generateReadmeContent(GeneratedCode $generatedCode, ?ProjectConfig $config = null): string
    {
        $namespace = $config ? $config->getNamespace() : 'Generated\\Wrapper';
        $libraryPath = $config ? $config->getLibraryFile() : 'path/to/your/library';
        
        $content = "# Generated FFI Wrapper Classes\n\n";
        $content .= "This directory contains auto-generated PHP FFI wrapper classes for C library functions.\n\n";
        
        // Overview section
        $content .= "## Overview\n\n";
        $content .= "These wrapper classes provide a convenient PHP interface to C library functions using PHP's FFI extension.\n";
        $content .= "The classes are organized by functionality to make them easy to use and understand.\n\n";
        
        // Requirements section
        $content .= "## Requirements\n\n";
        $content .= "- PHP 8.1 or higher\n";
        $content .= "- FFI extension enabled\n";
        $content .= "- The C library file: `{$libraryPath}`\n\n";
        
        // Installation section
        $content .= "## Installation\n\n";
        $content .= "1. Ensure the FFI extension is enabled in your php.ini:\n";
        $content .= "   ```ini\n";
        $content .= "   extension=ffi\n";
        $content .= "   ffi.enable=true\n";
        $content .= "   ```\n\n";
        $content .= "2. Make sure the C library is accessible at the configured path\n\n";
        
        // Usage section
        $content .= "## Usage\n\n";
        $content .= "### Basic Usage\n\n";
        $content .= "```php\n";
        $content .= "<?php\n";
        $content .= "require_once 'vendor/autoload.php';\n\n";
        $content .= "use {$namespace}\\Bootstrap;\n\n";
        $content .= "// Initialize the FFI library\n";
        $content .= "Bootstrap::initialize();\n\n";
        $content .= "// Now you can use the wrapper classes\n";
        $content .= "```\n\n";
        
        // Generated classes section
        $content .= "## Generated Classes\n\n";
        $content .= "The following wrapper classes have been generated:\n\n";
        
        foreach ($generatedCode->classes as $class) {
            if ($class->name === 'Bootstrap') {
                $content .= "### {$class->name}\n";
                $content .= "Centralized FFI management class. Use this to initialize the library.\n\n";
            } elseif (str_starts_with($class->name, 'Ui')) {
                $componentName = substr($class->name, 2); // Remove "Ui" prefix
                $content .= "### {$class->name}\n";
                $content .= "Wrapper for {$componentName}-related functions.\n\n";
            } else {
                $content .= "### {$class->name}\n";
                $content .= "Wrapper class for related functions.\n\n";
            }
        }
        
        // Examples section
        $content .= "## Examples\n\n";
        $generationType = $config ? $config->getGenerationType() : 'object';
        $content .= $this->generateExampleUsage($generatedCode, $namespace, $generationType);
        
        // Notes section
        $content .= "## Important Notes\n\n";
        $content .= "- Always call `Bootstrap::initialize()` before using any wrapper classes\n";
        $content .= "- These are auto-generated classes - do not modify them directly\n";
        $content .= "- All methods are static and thread-safe\n";
        $content .= "- Memory management is handled automatically by PHP's FFI extension\n\n";
        
        // Troubleshooting section
        $content .= "## Troubleshooting\n\n";
        $content .= "### FFI Extension Not Found\n";
        $content .= "Make sure the FFI extension is installed and enabled in your php.ini file.\n\n";
        $content .= "### Library Not Found\n";
        $content .= "Verify that the C library file exists at: `{$libraryPath}`\n\n";
        $content .= "### Runtime Errors\n";
        $content .= "Check that all function parameters match the expected C types and are not null when required.\n\n";
        
        return $content;
    }

    /**
     * Generate usage examples
     *
     * @param GeneratedCode $generatedCode Generated code
     * @param ProjectConfig|null $config Project configuration
     * @return array<string> Usage examples
     */
    private function generateUsageExamples(GeneratedCode $generatedCode, ?ProjectConfig $config = null): array
    {
        $examples = [];
        $namespace = $config ? $config->getNamespace() : 'Generated\\Wrapper';
        
        // Find UI-related classes for examples
        $uiClasses = array_filter($generatedCode->classes, fn($class) => str_starts_with($class->name, 'Ui') && $class->name !== 'Ui');
        
        if (!empty($uiClasses)) {
            $examples[] = $this->generateUIExample($uiClasses, $namespace);
        }
        
        return $examples;
    }

    /**
     * Generate example usage code
     *
     * @param GeneratedCode $generatedCode Generated code
     * @param string $namespace Namespace
     * @param string $generationType Generation type
     * @return string Example code
     */
    private function generateExampleUsage(GeneratedCode $generatedCode, string $namespace, string $generationType = 'object'): string
    {
        if ($generationType === 'functional') {
            return $this->generateFunctionalExample($generatedCode, $namespace);
        } else {
            return $this->generateObjectExample($generatedCode, $namespace);
        }
    }

    /**
     * Generate object-oriented example
     */
    private function generateObjectExample(GeneratedCode $generatedCode, string $namespace): string
    {
        $example = "### Object-Oriented Example\n\n";
        $example .= "```php\n";
        $example .= "<?php\n";
        $example .= "require_once 'vendor/autoload.php';\n\n";
        $example .= "use {$namespace}\\Bootstrap;\n";
        
        // Find some example classes
        $uiClasses = array_filter($generatedCode->classes, fn($class) => str_starts_with($class->name, 'Ui') && $class->name !== 'Ui');
        
        if (!empty($uiClasses)) {
            $firstClass = reset($uiClasses);
            $example .= "use {$namespace}\\{$firstClass->name};\n\n";
            
            $example .= "// Initialize the FFI library\n";
            $example .= "Bootstrap::initialize();\n\n";
            
            if ($firstClass->name === 'UiWindow') {
                $example .= "// Create a new window\n";
                $example .= "\$window = UiWindow::uiNewWindow('My Application', 800, 600, 1);\n\n";
                $example .= "// Set window title\n";
                $example .= "UiWindow::uiWindowSetTitle(\$window, 'Updated Title');\n\n";
                $example .= "// Show the window\n";
                $example .= "UiWindow::uiWindowShow(\$window);\n";
            } elseif ($firstClass->name === 'UiButton') {
                $example .= "// Create a new button\n";
                $example .= "\$button = UiButton::uiNewButton('Click Me');\n\n";
                $example .= "// Get button text\n";
                $example .= "\$text = UiButton::uiButtonText(\$button);\n";
                $example .= "echo \"Button text: \" . \$text . \"\\n\";\n\n";
                $example .= "// Set new button text\n";
                $example .= "UiButton::uiButtonSetText(\$button, 'New Text');\n";
            } else {
                $example .= "// Use the {$firstClass->name} class\n";
                $example .= "// Check the class methods for available functions\n";
            }
        } else {
            $example .= "\n// Initialize the FFI library\n";
            $example .= "Bootstrap::initialize();\n\n";
            $example .= "// Use the generated wrapper classes\n";
            $example .= "// Check individual class files for available methods\n";
        }
        
        $example .= "```\n\n";
        
        return $example;
    }

    /**
     * Generate functional/procedural example
     */
    private function generateFunctionalExample(GeneratedCode $generatedCode, string $namespace): string
    {
        $example = "### Functional/Procedural Example\n\n";
        $example .= "```php\n";
        $example .= "<?php\n";
        $example .= "require_once 'vendor/autoload.php';\n\n";
        $example .= "use {$namespace}\\Bootstrap;\n";
        $example .= "use {$namespace}\\Functions;\n\n";
        
        $example .= "// Initialize the FFI library\n";
        $example .= "Bootstrap::initialize();\n\n";
        
        $example .= "// Use C functions directly\n";
        $example .= "\$result = Functions::uiInit(null);\n\n";
        
        $example .= "// Create a window using C function names\n";
        $example .= "\$window = Functions::uiNewWindow('My Application', 800, 600, 1);\n\n";
        
        $example .= "// Create a button\n";
        $example .= "\$button = Functions::uiNewButton('Click Me');\n\n";
        
        $example .= "// Set button text\n";
        $example .= "Functions::uiButtonSetText(\$button, 'New Text');\n\n";
        
        $example .= "// Start the main loop\n";
        $example .= "Functions::uiMain();\n";
        
        $example .= "```\n\n";
        
        return $example;
    }

    /**
     * Generate UI-specific example
     *
     * @param array $uiClasses UI classes
     * @param string $namespace Namespace
     * @return string UI example
     */
    private function generateUIExample(array $uiClasses, string $namespace): string
    {
        $example = "<?php\n";
        $example .= "require_once 'vendor/autoload.php';\n\n";
        $example .= "use {$namespace}\\Bootstrap;\n";
        
        foreach (array_slice($uiClasses, 0, 3) as $class) {
            $example .= "use {$namespace}\\{$class->name};\n";
        }
        
        $example .= "\n// Initialize the UI library\n";
        $example .= "Bootstrap::initialize();\n\n";
        $example .= "// Your UI code here...\n";
        
        return $example;
    }

    /**
     * Generate functional/procedural wrapper
     *
     * @param array<\Yangweijie\CWrapper\Analyzer\FunctionSignature> $functions Functions to wrap
     * @param string $namespace Namespace
     * @return WrapperClass Functional wrapper class
     */
    private function generateFunctionalWrapper(array $functions, string $namespace): WrapperClass
    {
        $className = 'Functions';
        $methods = [];
        
        // Generate all functions as static methods in a single class
        foreach ($functions as $function) {
            $methods[] = $this->methodGenerator->generateMethod($function, 'functional', $className);
        }
        
        return new WrapperClass(
            $className,
            $namespace,
            $methods,
            [], // No properties for functional wrapper
            []  // No constants
        );
    }

    /**
     * Generate improved classes based on klitsche/ffigen output
     *
     * @param string $methodsFilePath Path to Methods.php file
     * @param string $baseNamespace Base namespace
     * @param string $generationType Generation type
     * @return array<WrapperClass> Generated wrapper classes
     */
    private function generateImprovedClasses(string $methodsFilePath, string $baseNamespace, string $generationType): array
    {
        $parser = new FFIGenOutputParser();
        $improvedGenerator = new ImprovedMethodGenerator($parser);
        
        // Parse klitsche/ffigen output
        $functions = $parser->parseMethodsFile($methodsFilePath);
        
        if (empty($functions)) {
            return [];
        }

        $classes = [];

        if ($generationType === 'object') {
            // Group functions semantically
            $functionGroups = $parser->groupFunctionsBySemantics($functions);
            
            foreach ($functionGroups as $groupName => $functionNames) {
                $className = $this->convertGroupNameToClassName($groupName);
                $methods = [];
                
                foreach ($functionNames as $functionName) {
                    if (isset($functions[$functionName])) {
                        $methods[] = $improvedGenerator->generateImprovedMethod(
                            $functionName,
                            $functions[$functionName],
                            $className,
                            $generationType
                        );
                    }
                }
                
                if (!empty($methods)) {
                    $classes[] = new WrapperClass(
                        $className,
                        $baseNamespace,
                        $methods,
                        [], // No properties
                        []  // No constants
                    );
                }
            }
        } else {
            // Generate functional wrapper
            $methods = [];
            
            foreach ($functions as $functionName => $functionInfo) {
                $methods[] = $improvedGenerator->generateImprovedMethod(
                    $functionName,
                    $functionInfo,
                    'Functions',
                    $generationType
                );
            }
            
            if (!empty($methods)) {
                $classes[] = new WrapperClass(
                    'Functions',
                    $baseNamespace,
                    $methods,
                    [],
                    []
                );
            }
        }

        return $classes;
    }
}