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
     * @param ProjectConfig|null $config Project configuration for namespace and other settings
     * @return GeneratedCode Generated code result
     */
    public function generate(ProcessedBindings $bindings, ?ProjectConfig $config = null): GeneratedCode
    {
        $classes = [];
        $interfaces = [];
        $traits = [];

        // Group functions by common prefixes to create logical classes
        $functionGroups = $this->groupFunctionsByPrefix($bindings->functions);

        // Determine namespace to use
        $baseNamespace = $config ? $config->getNamespace() : 'Generated\\Wrapper';
        
        // Generate wrapper classes for function groups
        foreach ($functionGroups as $groupName => $functions) {
            $className = $this->convertGroupNameToClassName($groupName);
            
            $wrapperClass = $this->classGenerator->generateClass(
                $className,
                $baseNamespace,
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
}