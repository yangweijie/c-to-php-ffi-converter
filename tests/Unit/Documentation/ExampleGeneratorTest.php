<?php

declare(strict_types=1);

namespace Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Analyzer\FunctionSignature;
use Yangweijie\CWrapper\Config\ProjectConfig;
use Yangweijie\CWrapper\Documentation\Documentation;
use Yangweijie\CWrapper\Documentation\ExampleGenerator;
use Yangweijie\CWrapper\Generator\GeneratedCode;
use Yangweijie\CWrapper\Generator\WrapperClass;

class ExampleGeneratorTest extends TestCase
{
    private ExampleGenerator $generator;
    private ProjectConfig $config;
    private GeneratedCode $code;
    private array $signatures;

    protected function setUp(): void
    {
        $this->generator = new ExampleGenerator();
        
        $this->config = new ProjectConfig(
            headerFiles: ['/path/to/header.h'],
            libraryFile: '/path/to/library.so',
            outputPath: './generated',
            namespace: 'Example\\FFI'
        );

        $wrapperClass = new WrapperClass(
            'TestWrapper',
            'Example\\FFI',
            [
                'public function simpleMethod(): int { return 0; }',
                'public function methodWithParams(string $str, int $num): void { }'
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
            new FunctionSignature('simple_func', 'int', []),
            new FunctionSignature('param_func', 'void', [
                ['name' => 'str', 'type' => 'const char*'],
                ['name' => 'num', 'type' => 'int']
            ]),
            new FunctionSignature('complex_func', 'float', [
                ['name' => 'buffer', 'type' => 'void*'],
                ['name' => 'size', 'type' => 'size_t'],
                ['name' => 'flags', 'type' => 'int']
            ])
        ];
    }

    public function testGenerateExamplesReturnsAllExpectedTypes(): void
    {
        $examples = $this->generator->generateExamples($this->code, $this->config, $this->signatures);

        $this->assertIsArray($examples);
        $this->assertArrayHasKey('basic_usage', $examples);
        $this->assertArrayHasKey('error_handling', $examples);
        $this->assertArrayHasKey('common_patterns', $examples);
        $this->assertArrayHasKey('class_TestWrapper', $examples);
    }

    public function testBasicUsageExampleStructure(): void
    {
        $examples = $this->generator->generateExamples($this->code, $this->config, $this->signatures);
        $basicExample = $examples['basic_usage'];

        $this->assertStringContainsString('<?php', $basicExample);
        $this->assertStringContainsString('/**', $basicExample);
        $this->assertStringContainsString('Basic Usage Example', $basicExample);
        $this->assertStringContainsString('require_once \'vendor/autoload.php\'', $basicExample);
        $this->assertStringContainsString('use Example\\FFI\\TestWrapper', $basicExample);
        $this->assertStringContainsString('new TestWrapper()', $basicExample);
        $this->assertStringContainsString('try {', $basicExample);
        $this->assertStringContainsString('} catch (ValidationException', $basicExample);
        $this->assertStringContainsString('} catch (\\FFI\\Exception', $basicExample);
    }

    public function testErrorHandlingExampleContent(): void
    {
        $examples = $this->generator->generateExamples($this->code, $this->config, $this->signatures);
        $errorExample = $examples['error_handling'];

        $this->assertStringContainsString('Error Handling Example', $errorExample);
        $this->assertStringContainsString('function safeWrapperCall', $errorExample);
        $this->assertStringContainsString('ValidationException', $errorExample);
        $this->assertStringContainsString('FFIConverterException', $errorExample);
        $this->assertStringContainsString('error_log', $errorExample);
        $this->assertStringContainsString('return null', $errorExample);
    }

    public function testClassSpecificExampleGeneration(): void
    {
        $examples = $this->generator->generateExamples($this->code, $this->config, $this->signatures);
        $classExample = $examples['class_TestWrapper'];

        $this->assertStringContainsString('TestWrapper Usage Example', $classExample);
        $this->assertStringContainsString('use Example\\FFI\\TestWrapper', $classExample);
        $this->assertStringContainsString('new TestWrapper()', $classExample);
        $this->assertStringContainsString('simple_func() wrapper', $classExample);
        $this->assertStringContainsString('param_func() wrapper', $classExample);
    }

    public function testCommonPatternsExampleStructure(): void
    {
        $examples = $this->generator->generateExamples($this->code, $this->config, $this->signatures);
        $patternsExample = $examples['common_patterns'];

        $this->assertStringContainsString('Common Usage Patterns', $patternsExample);
        $this->assertStringContainsString('class LibraryManager', $patternsExample);
        $this->assertStringContainsString('public function initialize()', $patternsExample);
        $this->assertStringContainsString('public function cleanup()', $patternsExample);
        $this->assertStringContainsString('function processData(', $patternsExample);
        $this->assertStringContainsString('$manager = new LibraryManager()', $patternsExample);
    }

    public function testExampleValueGeneration(): void
    {
        $signature = new FunctionSignature('test_func', 'void', [
            ['name' => 'str_param', 'type' => 'const char*'],
            ['name' => 'int_param', 'type' => 'int'],
            ['name' => 'float_param', 'type' => 'float'],
            ['name' => 'ptr_param', 'type' => 'void*']
        ]);

        $examples = $this->generator->generateExamples($this->code, $this->config, [$signature]);
        $classExample = $examples['class_TestWrapper'];

        $this->assertStringContainsString('\'example_string\'', $classExample);
        $this->assertStringContainsString('42', $classExample);
        $this->assertStringContainsString('3.14', $classExample);
        $this->assertStringContainsString('$ptr_param_ptr', $classExample);
    }

    public function testEmptyCodeHandling(): void
    {
        $emptyCode = new GeneratedCode([], [], [], new Documentation([], '', []));
        
        $examples = $this->generator->generateExamples($emptyCode, $this->config, $this->signatures);

        $this->assertArrayHasKey('basic_usage', $examples);
        $this->assertStringContainsString('No wrapper classes were generated', $examples['basic_usage']);
    }

    public function testFunctionNameToMethodNameConversion(): void
    {
        $testSignatures = [
            new FunctionSignature('lib_create_object', 'void*', []),
            new FunctionSignature('lib_destroy_object', 'void', [['name' => 'obj', 'type' => 'void*']]),
            new FunctionSignature('simple_func', 'int', [])
        ];

        $examples = $this->generator->generateExamples($this->code, $this->config, $testSignatures);
        $classExample = $examples['class_TestWrapper'];

        // Check that function names are converted to camelCase method names
        $this->assertStringContainsString('createObject', $classExample);
        $this->assertStringContainsString('destroyObject', $classExample);
        $this->assertStringContainsString('func()', $classExample); // simple_func becomes func
    }

    public function testSignatureExampleGeneration(): void
    {
        $voidSignature = new FunctionSignature('void_func', 'void', []);
        $returnSignature = new FunctionSignature('return_func', 'int', [['name' => 'param', 'type' => 'int']]);

        $examples = $this->generator->generateExamples($this->code, $this->config, [$voidSignature, $returnSignature]);
        $classExample = $examples['class_TestWrapper'];

        // Void functions shouldn't have result echo
        $this->assertStringContainsString('$wrapper->func()', $classExample);
        
        // Functions with return values should have result handling
        $this->assertStringContainsString('$wrapper->func(42)', $classExample);
        $this->assertStringContainsString('echo "Result: "', $classExample);
    }

    public function testMultipleClassesHandling(): void
    {
        $secondClass = new WrapperClass(
            'SecondWrapper',
            'Example\\FFI',
            ['public function anotherMethod(): void { }'],
            [],
            []
        );

        $multiClassCode = new GeneratedCode(
            [$this->code->classes[0], $secondClass],
            [],
            [],
            new Documentation([], '', [])
        );

        $examples = $this->generator->generateExamples($multiClassCode, $this->config, $this->signatures);

        $this->assertArrayHasKey('class_TestWrapper', $examples);
        $this->assertArrayHasKey('class_SecondWrapper', $examples);
        
        $this->assertStringContainsString('TestWrapper Usage Example', $examples['class_TestWrapper']);
        $this->assertStringContainsString('SecondWrapper Usage Example', $examples['class_SecondWrapper']);
    }

    public function testExampleLimiting(): void
    {
        // Create many signatures to test limiting
        $manySignatures = [];
        for ($i = 0; $i < 10; $i++) {
            $manySignatures[] = new FunctionSignature("func_{$i}", 'void', []);
        }

        $examples = $this->generator->generateExamples($this->code, $this->config, $manySignatures);
        $classExample = $examples['class_TestWrapper'];

        // Should be limited to 5 methods in class examples
        $funcCount = substr_count($classExample, 'func_');
        $this->assertLessThanOrEqual(5, $funcCount);
    }
}