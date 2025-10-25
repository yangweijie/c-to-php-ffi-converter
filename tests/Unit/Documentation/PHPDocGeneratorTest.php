<?php

declare(strict_types=1);

namespace Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Analyzer\FunctionSignature;
use Yangweijie\CWrapper\Documentation\PHPDocGenerator;
use Yangweijie\CWrapper\Generator\WrapperClass;

class PHPDocGeneratorTest extends TestCase
{
    private PHPDocGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PHPDocGenerator();
    }

    public function testGenerateMethodDocWithSimpleFunction(): void
    {
        $signature = new FunctionSignature(
            'test_function',
            'int',
            [
                ['name' => 'param1', 'type' => 'int'],
                ['name' => 'param2', 'type' => 'const char*']
            ]
        );

        $doc = $this->generator->generateMethodDoc($signature, 'testFunction');

        $this->assertStringContainsString('/**', $doc);
        $this->assertStringContainsString('Wrapper for test_function()', $doc);
        $this->assertStringContainsString('@see C function: test_function()', $doc);
        $this->assertStringContainsString('@param int $param1', $doc);
        $this->assertStringContainsString('@param string $param2', $doc);
        $this->assertStringContainsString('@return int', $doc);
        $this->assertStringContainsString('@throws', $doc);
        $this->assertStringContainsString('*/', $doc);
    }

    public function testGenerateMethodDocWithVoidReturn(): void
    {
        $signature = new FunctionSignature(
            'void_function',
            'void',
            [['name' => 'param1', 'type' => 'int']]
        );

        $doc = $this->generator->generateMethodDoc($signature, 'voidFunction');

        $this->assertStringContainsString('@param int $param1', $doc);
        $this->assertStringNotContainsString('@return', $doc);
    }

    public function testGenerateMethodDocWithNoParameters(): void
    {
        $signature = new FunctionSignature('simple_function', 'int', []);

        $doc = $this->generator->generateMethodDoc($signature, 'simpleFunction');

        $this->assertStringContainsString('Wrapper for simple_function()', $doc);
        $this->assertStringNotContainsString('@param', $doc);
        $this->assertStringContainsString('@return int', $doc);
    }

    public function testGenerateMethodDocWithExistingDocumentation(): void
    {
        $signature = new FunctionSignature(
            'documented_function',
            'int',
            [['name' => 'param1', 'type' => 'int']],
            ['This function does something important']
        );

        $doc = $this->generator->generateMethodDoc($signature, 'documentedFunction');

        $this->assertStringContainsString('This function does something important', $doc);
    }

    public function testGenerateMethodDocWithPointerTypes(): void
    {
        $signature = new FunctionSignature(
            'pointer_function',
            'void*',
            [
                ['name' => 'buffer', 'type' => 'void*'],
                ['name' => 'size', 'type' => 'size_t']
            ]
        );

        $doc = $this->generator->generateMethodDoc($signature, 'pointerFunction');

        $this->assertStringContainsString('@param mixed $buffer Pointer parameter', $doc);
        $this->assertStringContainsString('@param mixed $size', $doc);
        $this->assertStringContainsString('@return mixed Pointer to result', $doc);
    }

    public function testGenerateClassMethodDocs(): void
    {
        $class = new WrapperClass(
            'TestWrapper',
            'Test\\Namespace',
            ['method1', 'method2'],
            [],
            []
        );

        $signatures = [
            new FunctionSignature('test_func1', 'int', []),
            new FunctionSignature('test_func2', 'void', [['name' => 'param', 'type' => 'int']])
        ];

        $docs = $this->generator->generateClassMethodDocs($class, $signatures);

        $this->assertIsArray($docs);
        $this->assertCount(2, $docs);
        $this->assertArrayHasKey('func1', $docs);
        $this->assertArrayHasKey('func2', $docs);
    }

    public function testGenerateClassDoc(): void
    {
        $class = new WrapperClass(
            'TestWrapper',
            'Test\\Namespace',
            [],
            [],
            []
        );

        $doc = $this->generator->generateClassDoc($class, 'TestLibrary');

        $this->assertStringContainsString('/**', $doc);
        $this->assertStringContainsString('PHP FFI wrapper for TestLibrary library', $doc);
        $this->assertStringContainsString('@package Test\\Namespace', $doc);
        $this->assertStringContainsString('*/', $doc);
    }

    public function testGenerateUsageExample(): void
    {
        $signature = new FunctionSignature(
            'simple_func',
            'int',
            [
                ['name' => 'value', 'type' => 'int'],
                ['name' => 'name', 'type' => 'const char*']
            ]
        );

        $doc = $this->generator->generateMethodDoc($signature, 'simpleFunc');

        $this->assertStringContainsString('@example', $doc);
        $this->assertStringContainsString('$wrapper = new', $doc);
        $this->assertStringContainsString('simpleFunc(42, \'example_string\')', $doc);
    }

    public function testTypeMapping(): void
    {
        $testCases = [
            ['int', 'int'],
            ['long', 'int'],
            ['float', 'float'],
            ['double', 'float'],
            ['char*', 'string'],
            ['const char*', 'string'],
            ['void*', 'mixed'],
            ['void', 'void'],
            ['unknown_type', 'mixed']
        ];

        foreach ($testCases as [$cType, $expectedPhpType]) {
            $signature = new FunctionSignature('test_func', $cType, []);
            $doc = $this->generator->generateMethodDoc($signature, 'testFunc');
            
            if ($cType !== 'void') {
                $this->assertStringContainsString("@return {$expectedPhpType}", $doc);
            }
        }
    }

    public function testParameterDescriptionGeneration(): void
    {
        $signature = new FunctionSignature(
            'test_func',
            'void',
            [
                ['name' => 'str_param', 'type' => 'char*'],
                ['name' => 'int_param', 'type' => 'int'],
                ['name' => 'float_param', 'type' => 'float'],
                ['name' => 'ptr_param', 'type' => 'void*']
            ]
        );

        $doc = $this->generator->generateMethodDoc($signature, 'testFunc');

        $this->assertStringContainsString('String parameter', $doc);
        $this->assertStringContainsString('Integer parameter', $doc);
        $this->assertStringContainsString('Floating point parameter', $doc);
        $this->assertStringContainsString('Pointer parameter', $doc);
    }
}