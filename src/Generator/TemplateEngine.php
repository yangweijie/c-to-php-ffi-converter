<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Generator;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\ArrayLoader;
use Yangweijie\CWrapper\Exception\GenerationException;

/**
 * Template engine for code generation using Twig
 */
class TemplateEngine
{
    private Environment $twig;
    private array $defaultTemplates;

    public function __construct(?string $templatePath = null)
    {
        $this->defaultTemplates = $this->getDefaultTemplates();
        
        if ($templatePath && is_dir($templatePath)) {
            // Use filesystem loader for custom templates with fallback to array loader
            $arrayLoader = new ArrayLoader($this->defaultTemplates);
            $loader = new FilesystemLoader($templatePath);
            // Create a chain loader to fall back to default templates
            $loader = new \Twig\Loader\ChainLoader([$loader, $arrayLoader]);
        } else {
            // Use array loader for default templates
            $loader = new ArrayLoader($this->defaultTemplates);
        }
        
        $this->twig = new Environment($loader, [
            'cache' => false,
            'debug' => true,
            'strict_variables' => true,
            'autoescape' => false, // Disable auto-escaping for code generation
        ]);
        
        // Add custom filters and functions
        $this->addCustomFilters();
        $this->addCustomFunctions();
    }

    /**
     * Render a template with data
     *
     * @param string $templateName Template name or inline template content
     * @param array<string, mixed> $data Template data
     * @return string Rendered template
     * @throws GenerationException If template rendering fails
     */
    public function render(string $templateName, array $data = []): string
    {
        try {
            // Handle empty template
            if (empty($templateName)) {
                return '';
            }
            
            // Check if this is an inline template (contains Twig syntax)
            if (str_contains($templateName, '{{') || str_contains($templateName, '{%')) {
                return $this->twig->createTemplate($templateName)->render($data);
            }
            
            return $this->twig->render($templateName, $data);
        } catch (\Throwable $e) {
            throw new GenerationException("Failed to render template '{$templateName}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Render wrapper class template
     *
     * @param WrapperClass $wrapperClass Wrapper class data
     * @param string $libraryPath Path to C library
     * @return string Rendered class code
     */
    public function renderWrapperClass(WrapperClass $wrapperClass, string $libraryPath): string
    {
        return $this->render('wrapper_class.php.twig', [
            'class' => $wrapperClass,
            'library_path' => $libraryPath,
        ]);
    }

    /**
     * Render struct class template
     *
     * @param WrapperClass $wrapperClass Wrapper class data
     * @param array<array{name: string, type: string}> $fields Struct fields
     * @param bool $isUnion Whether this is a union type
     * @return string Rendered class code
     */
    public function renderStructClass(WrapperClass $wrapperClass, array $fields, bool $isUnion = false): string
    {
        return $this->render('struct_class.php.twig', [
            'class' => $wrapperClass,
            'fields' => $fields,
            'is_union' => $isUnion,
        ]);
    }

    /**
     * Render constants class template
     *
     * @param WrapperClass $wrapperClass Wrapper class data
     * @return string Rendered class code
     */
    public function renderConstantsClass(WrapperClass $wrapperClass): string
    {
        return $this->render('constants_class.php.twig', [
            'class' => $wrapperClass,
        ]);
    }

    /**
     * Add custom Twig filters
     */
    private function addCustomFilters(): void
    {
        // Filter to convert C function name to PHP method name
        $this->twig->addFilter(new \Twig\TwigFilter('method_name', function (string $functionName): string {
            $name = preg_replace('/^[a-z]+_/', '', $functionName);
            $parts = explode('_', $name);
            $methodName = array_shift($parts);
            
            foreach ($parts as $part) {
                $methodName .= ucfirst($part);
            }
            
            return $methodName;
        }));

        // Filter to convert struct name to class name
        $this->twig->addFilter(new \Twig\TwigFilter('class_name', function (string $structName): string {
            $name = preg_replace('/^(struct|union)\s+/', '', $structName);
            $parts = explode('_', $name);
            $className = '';
            
            foreach ($parts as $part) {
                $className .= ucfirst($part);
            }
            
            return $className;
        }));

        // Filter to format constant values
        $this->twig->addFilter(new \Twig\TwigFilter('constant_value', function (mixed $value): string {
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
        }));

        // Filter to normalize constant names
        $this->twig->addFilter(new \Twig\TwigFilter('constant_name', function (string $name): string {
            $normalized = strtoupper($name);
            $normalized = preg_replace('/[^A-Z0-9_]/', '_', $normalized);
            
            if (preg_match('/^[0-9]/', $normalized)) {
                $normalized = '_' . $normalized;
            }
            
            return $normalized;
        }));
    }

    /**
     * Add custom Twig functions
     */
    private function addCustomFunctions(): void
    {
        // Function to map C types to PHP types
        $this->twig->addFunction(new \Twig\TwigFunction('php_type', function (string $cType): string {
            $typeMapper = new TypeMapper();
            return $typeMapper->mapCTypeToPhp($cType);
        }));

        // Function to get default value for PHP type
        $this->twig->addFunction(new \Twig\TwigFunction('default_value', function (string $phpType): string {
            return match ($phpType) {
                'int' => '0',
                'float' => '0.0',
                'string' => "''",
                'bool' => 'false',
                'array' => '[]',
                default => 'null'
            };
        }));

        // Function to generate parameter validation
        $this->twig->addFunction(new \Twig\TwigFunction('validation_code', function (string $paramName, string $cType): string {
            $typeMapper = new TypeMapper();
            return $typeMapper->generateValidation($paramName, $cType);
        }));
    }

    /**
     * Get default Twig templates
     *
     * @return array<string, string> Template name => template content
     */
    private function getDefaultTemplates(): array
    {
        return [
            'wrapper_class.php.twig' => $this->getWrapperClassTemplate(),
            'struct_class.php.twig' => $this->getStructClassTemplate(),
            'constants_class.php.twig' => $this->getConstantsClassTemplate(),
            'method.php.twig' => $this->getMethodTemplate(),
        ];
    }

    /**
     * Get wrapper class template
     */
    private function getWrapperClassTemplate(): string
    {
        return <<<'TWIG'
<?php

declare(strict_types=1);

namespace {{ class.namespace }};

use FFI;

/**
 * Generated wrapper class for {{ class.name }}
 */
class {{ class.name }}
{
{% if class.constants %}
{% for name, value in class.constants %}
    public const {{ name|constant_name }} = {{ value|constant_value }};
{% endfor %}

{% endif %}
{% for property in class.properties %}
{{ property|raw }}
{% endfor %}

    /**
     * Get FFI instance from Bootstrap
     *
     * @return FFI FFI instance
     */
    protected static function getFFI(): FFI
    {
        return Bootstrap::getFFI();
    }

{% for method in class.methods %}
{{ method|raw }}
{% endfor %}
}
TWIG;
    }

    /**
     * Get struct class template
     */
    private function getStructClassTemplate(): string
    {
        return <<<'TWIG'
<?php

declare(strict_types=1);

namespace {{ class.namespace }};

use FFI;

/**
 * Generated wrapper class for C {% if is_union %}union{% else %}struct{% endif %} {{ class.name }}
 */
class {{ class.name }}
{
{% for property in class.properties %}
{{ property|raw }}
{% endfor %}

{% for method in class.methods %}
{{ method|raw }}
{% endfor %}
}
TWIG;
    }

    /**
     * Get constants class template
     */
    private function getConstantsClassTemplate(): string
    {
        return <<<'TWIG'
<?php

declare(strict_types=1);

namespace {{ class.namespace }};

/**
 * Generated constants class from C preprocessor definitions
 */
class {{ class.name }}
{
{% for name, value in class.constants %}
    public const {{ name|constant_name }} = {{ value|constant_value }};
{% endfor %}

{% for method in class.methods %}
{{ method|raw }}
{% endfor %}
}
TWIG;
    }

    /**
     * Get method template
     */
    private function getMethodTemplate(): string
    {
        return <<<'TWIG'
    /**
     * {{ method.documentation|join('\n     * ') }}
{% for param in method.parameters %}
     * @param {{ php_type(param.type) }} ${{ param.name }}
{% endfor %}
{% if method.return_type != 'void' %}
     * @return {{ php_type(method.return_type) }}
{% endif %}
     */
    public static function {{ method.name|method_name }}({% for param in method.parameters %}{{ php_type(param.type) }} ${{ param.name }}{% if not loop.last %}, {% endif %}{% endfor %}){% if method.return_type != 'void' %}: {{ php_type(method.return_type) }}{% endif %}
    {
{% for param in method.parameters %}
{{ validation_code(param.name, param.type)|raw }}
{% endfor %}
{% if method.return_type != 'void' %}
        return static::getFFI()->{{ method.name }}({% for param in method.parameters %} ${{ param.name }}{% if not loop.last %}, {% endif %}{% endfor %});
{% else %}
        static::getFFI()->{{ method.name }}({% for param in method.parameters %} ${{ param.name }}{% if not loop.last %}, {% endif %}{% endfor %});
{% endif %}
    }
TWIG;
    }
}