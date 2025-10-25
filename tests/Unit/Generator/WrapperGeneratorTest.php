<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Generator\WrapperGenerator;
use Yangweijie\CWrapper\Integration\ProcessedBindings;
use Yangweijie\CWrapper\Analyzer\FunctionSignature;
use Yangweijie\CWrapper\Analyzer\StructureDefinition;
use Yangweijie\CWrapper\Config\ProjectConfig;

class WrapperGeneratorTest extends TestCase
{
    private WrapperGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new WrapperGenerator();
    }

    public function testGenerate(): void
    {
        $functions = [
            new FunctionSignature(
                'math_add',
                'int',
                [
                    ['name' => 'a', 'type' => 'int'],
                    ['name' => 'b', 'type' => 'int']
                ]
            ),
            new FunctionSignature(
                'math_subtract',
                'int',
                [
                    ['name' => 'a', 'type' => 'int'],
                    ['name' => 'b', 'type' => 'int']
                ]
            ),
            new FunctionSignature(
                'string_length',
                'int',
                [
                    ['name' => 'str', 'type' => 'char*']
                ]
            )
        ];

        $structures = [
            new StructureDefinition(
                'point',
                [
                    ['name' => 'x', 'type' => 'float'],
                    ['name' => 'y', 'type' => 'float']
                ]
            )
        ];

        $constants = [
            'MAX_SIZE' => 1024,
            'PI' => 3.14159
        ];

        $bindings = new ProcessedBindings($functions, $structures, $constants);
        $generatedCode = $this->generator->generate($bindings);

        // Should have wrapper classes for function groups, struct classes, and constants class
        $this->assertGreaterThan(0, count($generatedCode->classes));
        
        // Check that we have classes for different function groups
        $classNames = array_map(fn($class) => $class->name, $generatedCode->classes);
        $this->assertContains('MathWrapper', $classNames);
        $this->assertContains('StringWrapper', $classNames);
        $this->assertContains('Point', $classNames);
        $this->assertContains('Constants', $classNames);
    }

    public function testGenerateWithEmptyBindings(): void
    {
        $bindings = new ProcessedBindings([], [], []);
        $generatedCode = $this->generator->generate($bindings);

        $this->assertEmpty($generatedCode->classes);
        $this->assertEmpty($generatedCode->interfaces);
        $this->assertEmpty($generatedCode->traits);
        $this->assertNotNull($generatedCode->documentation);
    }

    public function testGenerateWithOnlyFunctions(): void
    {
        $functions = [
            new FunctionSignature(
                'test_function',
                'void',
                []
            )
        ];

        $bindings = new ProcessedBindings($functions, [], []);
        $generatedCode = $this->generator->generate($bindings);

        $this->assertCount(1, $generatedCode->classes);
        $this->assertEquals('TestWrapper', $generatedCode->classes[0]->name);
    }

    public function testGenerateWithOnlyStructures(): void
    {
        $structures = [
            new StructureDefinition(
                'user_data',
                [
                    ['name' => 'id', 'type' => 'int'],
                    ['name' => 'name', 'type' => 'char*']
                ]
            )
        ];

        $bindings = new ProcessedBindings([], $structures, []);
        $generatedCode = $this->generator->generate($bindings);

        $this->assertCount(1, $generatedCode->classes);
        $this->assertEquals('UserData', $generatedCode->classes[0]->name);
        $this->assertEquals('Generated\\Struct', $generatedCode->classes[0]->namespace);
    }

    public function testGenerateWithOnlyConstants(): void
    {
        $constants = ['TEST_CONST' => 42];

        $bindings = new ProcessedBindings([], [], $constants);
        $generatedCode = $this->generator->generate($bindings);

        $this->assertCount(1, $generatedCode->classes);
        $this->assertEquals('Constants', $generatedCode->classes[0]->name);
        $this->assertEquals('Generated\\Constants', $generatedCode->classes[0]->namespace);
    }

    public function testGroupFunctionsByPrefix(): void
    {
        $functions = [
            new FunctionSignature('math_add', 'int', []),
            new FunctionSignature('math_subtract', 'int', []),
            new FunctionSignature('string_copy', 'char*', []),
            new FunctionSignature('string_length', 'int', []),
            new FunctionSignature('simple_function', 'void', [])
        ];

        $bindings = new ProcessedBindings($functions, [], []);
        $generatedCode = $this->generator->generate($bindings);

        $classNames = array_map(fn($class) => $class->name, $generatedCode->classes);
        
        $this->assertContains('MathWrapper', $classNames);
        $this->assertContains('StringWrapper', $classNames);
        $this->assertContains('SimpleWrapper', $classNames);
    }

    public function testGenerateCodeFiles(): void
    {
        $functions = [
            new FunctionSignature(
                'test_function',
                'int',
                [['name' => 'param', 'type' => 'int']]
            )
        ];

        $bindings = new ProcessedBindings($functions, [], []);
        $generatedCode = $this->generator->generate($bindings);

        $config = $this->createMock(ProjectConfig::class);
        $config->method('getLibraryFile')->willReturn('/path/to/test.so');

        $files = $this->generator->generateCodeFiles($generatedCode, $config);

        $this->assertNotEmpty($files);
        $this->assertArrayHasKey('TestWrapper.php', $files);
        
        $content = $files['TestWrapper.php'];
        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('class TestWrapper', $content);
        $this->assertStringContainsString('/path/to/test.so', $content);
    }

    public function testExtractFunctionPrefix(): void
    {
        $functions = [
            new FunctionSignature('prefix_function_name', 'void', []),
            new FunctionSignature('another_test', 'void', []),
            new FunctionSignature('simple', 'void', []),
            new FunctionSignature('no_underscore', 'void', [])
        ];

        $bindings = new ProcessedBindings($functions, [], []);
        $generatedCode = $this->generator->generate($bindings);

        $classNames = array_map(fn($class) => $class->name, $generatedCode->classes);
        
        $this->assertContains('PrefixWrapper', $classNames);
        $this->assertContains('AnotherWrapper', $classNames);
        $this->assertContains('SimpleWrapper', $classNames);
        $this->assertContains('NoWrapper', $classNames);
    }

    public function testConvertGroupNameToClassName(): void
    {
        $functions = [
            new FunctionSignature('test_function', 'void', []),
            new FunctionSignature('UPPER_FUNCTION', 'void', []),
            new FunctionSignature('mixed_Case_function', 'void', [])
        ];

        $bindings = new ProcessedBindings($functions, [], []);
        $generatedCode = $this->generator->generate($bindings);

        $classNames = array_map(fn($class) => $class->name, $generatedCode->classes);
        
        $this->assertContains('TestWrapper', $classNames);
        $this->assertContains('UPPERWrapper', $classNames);
        $this->assertContains('MixedWrapper', $classNames);
    }

    public function testGetClassFilename(): void
    {
        $functions = [new FunctionSignature('test_function', 'void', [])];
        $bindings = new ProcessedBindings($functions, [], []);
        $generatedCode = $this->generator->generate($bindings);

        $config = $this->createMock(ProjectConfig::class);
        $config->method('getLibraryFile')->willReturn('/test.so');

        $files = $this->generator->generateCodeFiles($generatedCode, $config);
        
        $this->assertArrayHasKey('TestWrapper.php', $files);
    }
}