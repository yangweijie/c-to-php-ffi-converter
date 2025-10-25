<?php

declare(strict_types=1);

namespace Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Analyzer\FunctionSignature;
use Yangweijie\CWrapper\Config\ProjectConfig;
use Yangweijie\CWrapper\Documentation\Documentation;
use Yangweijie\CWrapper\Documentation\DocumentationGenerator;
use Yangweijie\CWrapper\Documentation\ExampleGenerator;
use Yangweijie\CWrapper\Documentation\PHPDocGenerator;
use Yangweijie\CWrapper\Documentation\ReadmeGenerator;
use Yangweijie\CWrapper\Generator\GeneratedCode;
use Yangweijie\CWrapper\Generator\WrapperClass;

class DocumentationGeneratorTest extends TestCase
{
    private DocumentationGenerator $generator;
    private ProjectConfig $config;
    private GeneratedCode $code;
    private array $signatures;

    protected function setUp(): void
    {
        $this->generator = new DocumentationGenerator();
        
        $this->config = new ProjectConfig(
            headerFiles: ['/path/to/header.h'],
            libraryFile: '/path/to/library.so',
            outputPath: './generated',
            namespace: 'Test\\FFI'
        );

        $wrapperClass = new WrapperClass(
            'TestWrapper',
            'Test\\FFI',
            [
                'public function method1(): int { return 0; }',
                'public function method2(string $param): void { }'
            ],
            [],
            []
        );

        $this->code = new GeneratedCode(
            [$wrapperClass],
            [],
            [],
            new Documentation([], '', [])
        );

        $this->signatures = [
            new FunctionSignature('test_func1', 'int', []),
            new FunctionSignature('test_func2', 'void', [['name' => 'param', 'type' => 'const char*']])
        ];
    }

    public function testGenerateDocumentationReturnsDocumentationObject(): void
    {
        $documentation = $this->generator->generateDocumentation($this->code);

        $this->assertInstanceOf(Documentation::class, $documentation);
        $this->assertIsArray($documentation->phpDocComments);
        $this->assertIsString($documentation->readmeContent);
        $this->assertIsArray($documentation->examples);
    }

    public function testGenerateDocumentationWithBasicContent(): void
    {
        $documentation = $this->generator->generateDocumentation($this->code);

        // Check PHPDoc comments
        $this->assertArrayHasKey('TestWrapper_class', $documentation->phpDocComments);
        $this->assertStringContainsString('PHP FFI wrapper for Unknown Library', $documentation->phpDocComments['TestWrapper_class']);

        // Check README content
        $this->assertStringContainsString('# Generated FFI Wrapper', $documentation->readmeContent);
        $this->assertStringContainsString('Test\\FFI\\TestWrapper', $documentation->readmeContent);

        // Check examples
        $this->assertArrayHasKey('basic', $documentation->examples);
        $this->assertStringContainsString('use Test\\FFI\\TestWrapper', $documentation->examples['basic']);
    }

    public function testGenerateCompleteDocumentationWithFullContext(): void
    {
        $documentation = $this->generator->generateCompleteDocumentation(
            $this->code,
            $this->config,
            $this->signatures,
            'TestLibrary'
        );

        $this->assertInstanceOf(Documentation::class, $documentation);

        // Check that PHPDoc comments include method documentation
        $this->assertNotEmpty($documentation->phpDocComments);
        $this->assertArrayHasKey('TestWrapper_class', $documentation->phpDocComments);

        // Check README content includes library name
        $this->assertStringContainsString('TestLibrary', $documentation->readmeContent);
        $this->assertStringContainsString('# TestLibrary PHP FFI Wrapper', $documentation->readmeContent);

        // Check examples include all expected types
        $this->assertArrayHasKey('basic_usage', $documentation->examples);
        $this->assertArrayHasKey('error_handling', $documentation->examples);
        $this->assertArrayHasKey('common_patterns', $documentation->examples);
    }

    public function testGenerateDocumentationWithEmptyCode(): void
    {
        $emptyCode = new GeneratedCode([], [], [], new Documentation([], '', []));
        
        $documentation = $this->generator->generateDocumentation($emptyCode);

        $this->assertInstanceOf(Documentation::class, $documentation);
        $this->assertStringContainsString('No wrapper classes were generated', $documentation->readmeContent);
        $this->assertEmpty($documentation->phpDocComments);
    }

    public function testGenerateCompleteDocumentationWithEmptyCode(): void
    {
        $emptyCode = new GeneratedCode([], [], [], new Documentation([], '', []));
        
        $documentation = $this->generator->generateCompleteDocumentation(
            $emptyCode,
            $this->config,
            $this->signatures,
            'EmptyLibrary'
        );

        $this->assertInstanceOf(Documentation::class, $documentation);
        $this->assertStringContainsString('EmptyLibrary', $documentation->readmeContent);
        $this->assertEmpty($documentation->phpDocComments);
    }

    public function testGenerateDocumentationWithMultipleClasses(): void
    {
        $secondClass = new WrapperClass(
            'SecondWrapper',
            'Test\\FFI',
            ['public function anotherMethod(): string { return ""; }'],
            [],
            []
        );

        $multiClassCode = new GeneratedCode(
            [$this->code->classes[0], $secondClass],
            [],
            [],
            new Documentation([], '', [])
        );

        $documentation = $this->generator->generateDocumentation($multiClassCode);

        $this->assertArrayHasKey('TestWrapper_class', $documentation->phpDocComments);
        $this->assertArrayHasKey('SecondWrapper_class', $documentation->phpDocComments);
        $this->assertStringContainsString('TestWrapper', $documentation->readmeContent);
        $this->assertStringContainsString('SecondWrapper', $documentation->readmeContent);
    }

    public function testGenerateCompleteDocumentationWithCustomComponents(): void
    {
        $mockPhpDocGenerator = $this->createMock(PHPDocGenerator::class);
        $mockReadmeGenerator = $this->createMock(ReadmeGenerator::class);
        $mockExampleGenerator = $this->createMock(ExampleGenerator::class);

        $mockPhpDocGenerator->expects($this->once())
            ->method('generateClassMethodDocs')
            ->willReturn(['method1' => '/** Mock PHPDoc */']);

        $mockPhpDocGenerator->expects($this->once())
            ->method('generateClassDoc')
            ->willReturn('/** Mock class doc */');

        $mockReadmeGenerator->expects($this->once())
            ->method('generateReadme')
            ->willReturn('# Mock README');

        $mockExampleGenerator->expects($this->once())
            ->method('generateExamples')
            ->willReturn(['mock' => '<?php // Mock example']);

        $generator = new DocumentationGenerator(
            $mockPhpDocGenerator,
            $mockReadmeGenerator,
            $mockExampleGenerator
        );

        $documentation = $generator->generateCompleteDocumentation(
            $this->code,
            $this->config,
            $this->signatures,
            'MockLibrary'
        );

        $this->assertStringContainsString('Mock PHPDoc', $documentation->phpDocComments['method1']);
        $this->assertStringContainsString('Mock class doc', $documentation->phpDocComments['TestWrapper_class']);
        $this->assertStringContainsString('Mock README', $documentation->readmeContent);
        $this->assertStringContainsString('Mock example', $documentation->examples['mock']);
    }

    public function testDocumentationStructureConsistency(): void
    {
        $documentation = $this->generator->generateCompleteDocumentation(
            $this->code,
            $this->config,
            $this->signatures,
            'ConsistencyTest'
        );

        // Verify all required components are present
        $this->assertIsArray($documentation->phpDocComments);
        $this->assertIsString($documentation->readmeContent);
        $this->assertIsArray($documentation->examples);

        // Verify content is not empty
        $this->assertNotEmpty($documentation->phpDocComments);
        $this->assertNotEmpty($documentation->readmeContent);
        $this->assertNotEmpty($documentation->examples);

        // Verify content contains expected elements
        $this->assertStringContainsString('ConsistencyTest', $documentation->readmeContent);
        foreach ($documentation->phpDocComments as $comment) {
            $this->assertStringContainsString('/**', $comment);
            $this->assertStringContainsString('*/', $comment);
        }
        foreach ($documentation->examples as $example) {
            $this->assertStringContainsString('<?php', $example);
        }
    }

    public function testGenerateDocumentationWithComplexSignatures(): void
    {
        $complexSignatures = [
            new FunctionSignature(
                'complex_func',
                'struct_result*',
                [
                    ['name' => 'input', 'type' => 'const struct_input*'],
                    ['name' => 'callback', 'type' => 'void (*)(int)'],
                    ['name' => 'flags', 'type' => 'unsigned int']
                ],
                ['This is a complex function with callback']
            )
        ];

        $documentation = $this->generator->generateCompleteDocumentation(
            $this->code,
            $this->config,
            $complexSignatures,
            'ComplexLibrary'
        );

        $this->assertInstanceOf(Documentation::class, $documentation);
        $this->assertNotEmpty($documentation->phpDocComments);
        $this->assertStringContainsString('ComplexLibrary', $documentation->readmeContent);
    }
}