<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Generator\MethodGenerator;
use Yangweijie\CWrapper\Generator\TypeMapper;
use Yangweijie\CWrapper\Analyzer\FunctionSignature;

class MethodGeneratorTest extends TestCase
{
    private MethodGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new MethodGenerator();
    }

    public function testGenerateMethod(): void
    {
        $function = new FunctionSignature(
            'test_add_numbers',
            'int',
            [
                ['name' => 'a', 'type' => 'int'],
                ['name' => 'b', 'type' => 'int']
            ],
            ['Adds two numbers together']
        );

        $method = $this->generator->generateMethod($function);

        $this->assertStringContainsString('public function addNumbers(int $a, int $b): int', $method);
        $this->assertStringContainsString('Wrapper for test_add_numbers', $method);
        $this->assertStringContainsString('Adds two numbers together', $method);
        $this->assertStringContainsString('$this->ffi->test_add_numbers($a, $b)', $method);
        $this->assertStringContainsString('@param int $a', $method);
        $this->assertStringContainsString('@param int $b', $method);
        $this->assertStringContainsString('@return int', $method);
    }

    public function testGenerateMethodWithVoidReturn(): void
    {
        $function = new FunctionSignature(
            'test_print_message',
            'void',
            [
                ['name' => 'message', 'type' => 'char*']
            ]
        );

        $method = $this->generator->generateMethod($function);

        $this->assertStringContainsString('public function printMessage(string $message)', $method);
        $this->assertStringNotContainsString(': void', $method);
        $this->assertStringNotContainsString('return', $method);
        $this->assertStringContainsString('$this->ffi->test_print_message($message);', $method);
    }

    public function testGenerateMethodWithPointerParameters(): void
    {
        $function = new FunctionSignature(
            'test_process_data',
            'int',
            [
                ['name' => 'data', 'type' => 'void*'],
                ['name' => 'size', 'type' => 'size_t']
            ]
        );

        $method = $this->generator->generateMethod($function);

        $this->assertStringContainsString('public function processData($data, int $size): int', $method);
        $this->assertStringContainsString('@param mixed $data', $method);
        $this->assertStringContainsString('@param int $size', $method);
    }

    public function testGenerateMethodWithNoParameters(): void
    {
        $function = new FunctionSignature(
            'test_get_version',
            'char*',
            []
        );

        $method = $this->generator->generateMethod($function);

        $this->assertStringContainsString('public function getVersion(): string', $method);
        $this->assertStringContainsString('$this->ffi->test_get_version()', $method);
    }

    public function testConvertFunctionName(): void
    {
        $function1 = new FunctionSignature('lib_create_object', 'void*', []);
        $function2 = new FunctionSignature('simple_function', 'int', []);
        $function3 = new FunctionSignature('get_user_name', 'char*', []);

        $method1 = $this->generator->generateMethod($function1);
        $method2 = $this->generator->generateMethod($function2);
        $method3 = $this->generator->generateMethod($function3);

        $this->assertStringContainsString('function createObject()', $method1);
        $this->assertStringContainsString('function function()', $method2);
        $this->assertStringContainsString('function userName()', $method3);
    }

    public function testGenerateMethodWithValidation(): void
    {
        $function = new FunctionSignature(
            'test_validate_input',
            'bool',
            [
                ['name' => 'number', 'type' => 'int'],
                ['name' => 'text', 'type' => 'char*'],
                ['name' => 'flag', 'type' => 'bool']
            ]
        );

        $method = $this->generator->generateMethod($function);

        $this->assertStringContainsString('if (!is_int($number))', $method);
        $this->assertStringContainsString('if (!is_string($text))', $method);
        $this->assertStringContainsString('if (!is_bool($flag))', $method);
        $this->assertStringContainsString('InvalidArgumentException', $method);
    }

    public function testGenerateMethodWithFloatParameters(): void
    {
        $function = new FunctionSignature(
            'test_calculate',
            'double',
            [
                ['name' => 'value1', 'type' => 'float'],
                ['name' => 'value2', 'type' => 'double']
            ]
        );

        $method = $this->generator->generateMethod($function);

        $this->assertStringContainsString('public function calculate(float $value1, float $value2): float', $method);
        $this->assertStringContainsString('if (!is_numeric($value1))', $method);
        $this->assertStringContainsString('if (!is_numeric($value2))', $method);
    }
}