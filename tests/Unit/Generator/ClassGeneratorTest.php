<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Generator\ClassGenerator;
use Yangweijie\CWrapper\Generator\MethodGenerator;
use Yangweijie\CWrapper\Generator\TemplateEngine;
use Yangweijie\CWrapper\Analyzer\FunctionSignature;
use Yangweijie\CWrapper\Analyzer\StructureDefinition;

class ClassGeneratorTest extends TestCase
{
    private ClassGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ClassGenerator();
    }

    public function testGenerateClass(): void
    {
        $functions = [
            new FunctionSignature(
                'test_function',
                'int',
                [
                    ['name' => 'param1', 'type' => 'int'],
                    ['name' => 'param2', 'type' => 'char*']
                ],
                ['Test function documentation']
            )
        ];

        $structures = [
            new StructureDefinition(
                'test_struct',
                [
                    ['name' => 'field1', 'type' => 'int'],
                    ['name' => 'field2', 'type' => 'char*']
                ]
            )
        ];

        $constants = ['TEST_CONST' => 42];

        $wrapperClass = $this->generator->generateClass(
            'TestWrapper',
            'Test\\Namespace',
            $functions,
            $structures,
            $constants
        );

        $this->assertEquals('TestWrapper', $wrapperClass->name);
        $this->assertEquals('Test\\Namespace', $wrapperClass->namespace);
        $this->assertCount(1, $wrapperClass->methods);
        $this->assertCount(1, $wrapperClass->properties);
        $this->assertEquals(['TEST_CONST' => 42], $wrapperClass->constants);
    }

    public function testGenerateClassCode(): void
    {
        $functions = [
            new FunctionSignature(
                'test_add',
                'int',
                [
                    ['name' => 'a', 'type' => 'int'],
                    ['name' => 'b', 'type' => 'int']
                ]
            )
        ];

        $wrapperClass = $this->generator->generateClass(
            'MathWrapper',
            'Test\\Math',
            $functions
        );

        $code = $this->generator->generateClassCode($wrapperClass, '/path/to/library.so');

        $this->assertStringContainsString('<?php', $code);
        $this->assertStringContainsString('namespace Test\\Math;', $code);
        $this->assertStringContainsString('class MathWrapper', $code);
        $this->assertStringContainsString('private FFI $ffi;', $code);
        $this->assertStringContainsString('/path/to/library.so', $code);
    }

    public function testGenerateClassWithEmptyFunctions(): void
    {
        $wrapperClass = $this->generator->generateClass(
            'EmptyWrapper',
            'Test\\Empty',
            []
        );

        $this->assertEquals('EmptyWrapper', $wrapperClass->name);
        $this->assertEquals('Test\\Empty', $wrapperClass->namespace);
        $this->assertEmpty($wrapperClass->methods);
        $this->assertEmpty($wrapperClass->properties);
        $this->assertEmpty($wrapperClass->constants);
    }

    public function testGeneratePropertyFromStruct(): void
    {
        $structure = new StructureDefinition(
            'user_data',
            [
                ['name' => 'id', 'type' => 'int'],
                ['name' => 'name', 'type' => 'char*']
            ]
        );

        $wrapperClass = $this->generator->generateClass(
            'TestWrapper',
            'Test\\Namespace',
            [],
            [$structure]
        );

        $this->assertCount(1, $wrapperClass->properties);
        $property = $wrapperClass->properties[0];
        $this->assertStringContainsString('user_data', $property);
        $this->assertStringContainsString('\\FFI\\CData', $property);
    }

    public function testGenerateClassWithUnionStruct(): void
    {
        $union = new StructureDefinition(
            'test_union',
            [
                ['name' => 'intVal', 'type' => 'int'],
                ['name' => 'floatVal', 'type' => 'float']
            ],
            true // isUnion
        );

        $wrapperClass = $this->generator->generateClass(
            'TestWrapper',
            'Test\\Namespace',
            [],
            [$union]
        );

        $this->assertCount(1, $wrapperClass->properties);
        $property = $wrapperClass->properties[0];
        $this->assertStringContainsString('test_union union', $property);
    }
}