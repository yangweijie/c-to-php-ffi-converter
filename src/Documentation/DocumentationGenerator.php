<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Documentation;

use Yangweijie\CWrapper\Analyzer\FunctionSignature;
use Yangweijie\CWrapper\Config\ProjectConfig;
use Yangweijie\CWrapper\Generator\GeneratedCode;

/**
 * Main documentation generator that coordinates all documentation components
 */
class DocumentationGenerator implements DocumentationGeneratorInterface
{
    public function __construct(
        private PHPDocGenerator $phpDocGenerator = new PHPDocGenerator(),
        private ReadmeGenerator $readmeGenerator = new ReadmeGenerator(),
        private ExampleGenerator $exampleGenerator = new ExampleGenerator()
    ) {
    }

    /**
     * Generate comprehensive documentation for generated code
     *
     * @param GeneratedCode $code Generated code to document
     * @return Documentation Generated documentation
     */
    public function generateDocumentation(GeneratedCode $code): Documentation
    {
        // For this implementation, we'll create basic documentation
        // In a real scenario, you'd have access to more context like ProjectConfig and FunctionSignatures
        
        $phpDocComments = $this->generatePHPDocComments($code);
        $readmeContent = $this->generateReadmeContent($code);
        $examples = $this->generateExamples($code);
        
        return new Documentation($phpDocComments, $readmeContent, $examples);
    }

    /**
     * Generate complete documentation with full context
     *
     * @param GeneratedCode $code Generated code
     * @param ProjectConfig $config Project configuration
     * @param array<FunctionSignature> $signatures Function signatures
     * @param string $libraryName Library name
     * @return Documentation Generated documentation
     */
    public function generateCompleteDocumentation(
        GeneratedCode $code,
        ProjectConfig $config,
        array $signatures,
        string $libraryName
    ): Documentation {
        $phpDocComments = [];
        
        // Generate PHPDoc comments for each class
        foreach ($code->classes as $class) {
            $classSignatures = $this->getSignaturesForClass($class->name, $signatures);
            $classDocs = $this->phpDocGenerator->generateClassMethodDocs($class, $classSignatures);
            $phpDocComments = array_merge($phpDocComments, $classDocs);
            
            // Add class-level documentation
            $phpDocComments[$class->name . '_class'] = $this->phpDocGenerator->generateClassDoc($class, $libraryName);
        }
        
        // Generate README content
        $readmeContent = $this->readmeGenerator->generateReadme($code, $config, $libraryName);
        
        // Generate examples
        $examples = $this->exampleGenerator->generateExamples($code, $config, $signatures);
        
        return new Documentation($phpDocComments, $readmeContent, $examples);
    }

    /**
     * Generate PHPDoc comments (basic version)
     *
     * @param GeneratedCode $code Generated code
     * @return array<string> PHPDoc comments
     */
    private function generatePHPDocComments(GeneratedCode $code): array
    {
        $comments = [];
        
        foreach ($code->classes as $class) {
            $comments[$class->name . '_class'] = $this->phpDocGenerator->generateClassDoc($class, 'Unknown Library');
        }
        
        return $comments;
    }

    /**
     * Generate README content (basic version)
     *
     * @param GeneratedCode $code Generated code
     * @return string README content
     */
    private function generateReadmeContent(GeneratedCode $code): string
    {
        if (empty($code->classes)) {
            return "# Generated FFI Wrapper\n\nNo wrapper classes were generated.";
        }
        
        $content = "# Generated FFI Wrapper\n\n";
        $content .= "This package contains automatically generated PHP FFI wrapper classes.\n\n";
        $content .= "## Generated Classes\n\n";
        
        foreach ($code->classes as $class) {
            $content .= "- `{$class->namespace}\\{$class->name}`\n";
        }
        
        $content .= "\n## Usage\n\n";
        $content .= "```php\n";
        $content .= "<?php\n\n";
        $content .= "require_once 'vendor/autoload.php';\n\n";
        
        if (!empty($code->classes)) {
            $firstClass = $code->classes[0];
            $content .= "use {$firstClass->namespace}\\{$firstClass->name};\n\n";
            $content .= "\$wrapper = new {$firstClass->name}();\n";
            $content .= "// Use the wrapper methods...\n";
        }
        
        $content .= "```\n";
        
        return $content;
    }

    /**
     * Generate examples (basic version)
     *
     * @param GeneratedCode $code Generated code
     * @return array<string> Examples
     */
    private function generateExamples(GeneratedCode $code): array
    {
        $examples = [];
        
        if (!empty($code->classes)) {
            $firstClass = $code->classes[0];
            $examples['basic'] = "<?php\n\nuse {$firstClass->namespace}\\{$firstClass->name};\n\n\$wrapper = new {$firstClass->name}();\n// Example usage...";
        }
        
        return $examples;
    }

    /**
     * Get function signatures for a specific class
     *
     * @param string $className Class name
     * @param array<FunctionSignature> $signatures All signatures
     * @return array<FunctionSignature> Class-specific signatures
     */
    private function getSignaturesForClass(string $className, array $signatures): array
    {
        // In a real implementation, you would filter signatures based on some criteria
        // For now, return all signatures
        return $signatures;
    }
}