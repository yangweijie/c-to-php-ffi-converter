<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Generator\TemplateEngine;
use Yangweijie\CWrapper\Generator\WrapperClass;
use Yangweijie\CWrapper\Exception\GenerationException;

class TemplateEngineTest extends TestCase
{
    private TemplateEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new TemplateEngine();
    }

    public function testRenderWrapperClass(): void
    {
        $wrapperClass = new WrapperClass(
            'TestWrapper',
            'Test\\Namespace',
            ['    public function testMethod(): int { return 42; }'],
            ['    private int $testProperty;'],
            ['TEST_CONST' => 42]
        );

        $code = $this->engine->renderWrapperClass($wrapperClass, '/path/to/library.so');

        $this->assertStringContainsString('<?php', $code);
        $this->assertStringContainsString('namespace Test\\Namespace;', $code);
        $this->assertStringContainsString('class TestWrapper', $code);
        $this->assertStringContainsString('private FFI $ffi;', $code);
        $this->assertStringContainsString('/path/to/library.so', $code);
        $this->assertStringContainsString('public const TEST_CONST = 42;', $code);
        $this->assertStringContainsString('public function testMethod(): int', $code);
    }

    public function testRenderStructClass(): void
    {
        $wrapperClass = new WrapperClass(
            'TestStruct',
            'Test\\Struct',
            ['    public function getId(): int { return $this->id; }'],
            ['    private int $id;'],
            []
        );

        $fields = [
            ['name' => 'id', 'type' => 'int'],
            ['name' => 'name', 'type' => 'char*']
        ];

        $code = $this->engine->renderStructClass($wrapperClass, $fields, false);

        $this->assertStringContainsString('<?php', $code);
        $this->assertStringContainsString('namespace Test\\Struct;', $code);
        $this->assertStringContainsString('class TestStruct', $code);
        $this->assertStringContainsString('Generated wrapper class for C struct', $code);
        $this->assertStringContainsString('public function getId(): int', $code);
    }

    public function testRenderUnionClass(): void
    {
        $wrapperClass = new WrapperClass(
            'TestUnion',
            'Test\\Union',
            [],
            [],
            []
        );

        $fields = [
            ['name' => 'intVal', 'type' => 'int'],
            ['name' => 'floatVal', 'type' => 'float']
        ];

        $code = $this->engine->renderStructClass($wrapperClass, $fields, true);

        $this->assertStringContainsString('Generated wrapper class for C union', $code);
    }

    public function testRenderConstantsClass(): void
    {
        $wrapperClass = new WrapperClass(
            'Constants',
            'Test\\Constants',
            ['    public static function getAllConstants(): array { return []; }'],
            [],
            [
                'MAX_SIZE' => 1024,
                'DEFAULT_NAME' => 'test',
                'ENABLED' => true
            ]
        );

        $code = $this->engine->renderConstantsClass($wrapperClass);

        $this->assertStringContainsString('<?php', $code);
        $this->assertStringContainsString('namespace Test\\Constants;', $code);
        $this->assertStringContainsString('class Constants', $code);
        $this->assertStringContainsString('Generated constants class from C preprocessor definitions', $code);
        $this->assertStringContainsString('public const MAX_SIZE = 1024;', $code);
        $this->assertStringContainsString('public const DEFAULT_NAME = \'test\';', $code);
        $this->assertStringContainsString('public const ENABLED = true;', $code);
    }

    public function testCustomFilters(): void
    {
        // Test method_name filter
        $result = $this->engine->render('{{ "test_function_name"|method_name }}', []);
        $this->assertEquals('functionName', trim($result));

        // Test class_name filter
        $result = $this->engine->render('{{ "struct test_struct"|class_name }}', []);
        $this->assertEquals('TestStruct', trim($result));

        // Test constant_value filter
        $result = $this->engine->render('{{ value|constant_value }}', ['value' => 'test string']);
        $this->assertEquals("'test string'", trim($result));

        $result = $this->engine->render('{{ value|constant_value }}', ['value' => true]);
        $this->assertEquals('true', trim($result));

        // Test constant_name filter
        $result = $this->engine->render('{{ "test-const@name"|constant_name }}', []);
        $this->assertEquals('TEST_CONST_NAME', trim($result));
    }

    public function testCustomFunctions(): void
    {
        // Test php_type function
        $result = $this->engine->render('{{ php_type("int") }}', []);
        $this->assertEquals('int', trim($result));

        $result = $this->engine->render('{{ php_type("char*") }}', []);
        $this->assertEquals('string', trim($result));

        // Test default_value function
        $result = $this->engine->render('{{ default_value("int") }}', []);
        $this->assertEquals('0', trim($result));

        $result = $this->engine->render('{{ default_value("string") }}', []);
        $this->assertEquals("''", trim($result));

        // Test validation_code function
        $result = $this->engine->render('{{ validation_code("param", "int") }}', []);
        $this->assertStringContainsString('is_int($param)', $result);
    }

    public function testRenderWithInvalidTemplate(): void
    {
        $this->expectException(GenerationException::class);
        $this->expectExceptionMessage('Failed to render template');

        $this->engine->render('non_existent_template.twig', []);
    }

    public function testRenderWithMissingVariable(): void
    {
        $this->expectException(GenerationException::class);

        // This should fail because strict_variables is enabled
        $this->engine->render('{{ missing_variable }}', []);
    }

    public function testRenderEmptyTemplate(): void
    {
        $result = $this->engine->render('', []);
        $this->assertEquals('', $result);
    }

    public function testRenderWithComplexData(): void
    {
        $wrapperClass = new WrapperClass(
            'ComplexWrapper',
            'Test\\Complex',
            [
                '    public function method1(): void {}',
                '    public function method2(int $param): string { return "test"; }'
            ],
            [
                '    private int $prop1;',
                '    private string $prop2;'
            ],
            [
                'CONST1' => 100,
                'CONST2' => 'value',
                'CONST3' => [1, 2, 3]
            ]
        );

        $code = $this->engine->renderWrapperClass($wrapperClass, '/test/lib.so');

        $this->assertStringContainsString('class ComplexWrapper', $code);
        $this->assertStringContainsString('public const CONST1 = 100;', $code);
        $this->assertStringContainsString('public const CONST2 = \'value\';', $code);
        $this->assertStringContainsString('public const CONST3 = array', $code);
        $this->assertStringContainsString('public function method1(): void', $code);
        $this->assertStringContainsString('public function method2(int $param): string', $code);
        $this->assertStringContainsString('private int $prop1;', $code);
        $this->assertStringContainsString('private string $prop2;', $code);
    }
}