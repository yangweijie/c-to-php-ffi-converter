<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Generator\StructGenerator;
use Yangweijie\CWrapper\Analyzer\StructureDefinition;

class StructGeneratorTest extends TestCase
{
    private StructGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new StructGenerator();
    }

    public function testGenerateStructClass(): void
    {
        $structure = new StructureDefinition(
            'user_data',
            [
                ['name' => 'id', 'type' => 'int'],
                ['name' => 'name', 'type' => 'char*'],
                ['name' => 'age', 'type' => 'unsigned int']
            ]
        );

        $wrapperClass = $this->generator->generateStructClass($structure, 'Test\\Struct');

        $this->assertEquals('UserData', $wrapperClass->name);
        $this->assertEquals('Test\\Struct', $wrapperClass->namespace);
        $this->assertCount(3, $wrapperClass->properties);
        $this->assertGreaterThan(0, count($wrapperClass->methods));
    }

    public function testGenerateStructClassCode(): void
    {
        $structure = new StructureDefinition(
            'point',
            [
                ['name' => 'x', 'type' => 'float'],
                ['name' => 'y', 'type' => 'float']
            ]
        );

        $wrapperClass = $this->generator->generateStructClass($structure, 'Test\\Struct');
        $code = $this->generator->generateStructClassCode($wrapperClass, $structure);

        $this->assertStringContainsString('<?php', $code);
        $this->assertStringContainsString('namespace Test\\Struct;', $code);
        $this->assertStringContainsString('class Point', $code);
        $this->assertStringContainsString('Generated wrapper class for C struct Point', $code);
    }

    public function testGenerateUnionClass(): void
    {
        $union = new StructureDefinition(
            'value_union',
            [
                ['name' => 'intValue', 'type' => 'int'],
                ['name' => 'floatValue', 'type' => 'float'],
                ['name' => 'stringValue', 'type' => 'char*']
            ],
            true // isUnion
        );

        $wrapperClass = $this->generator->generateStructClass($union, 'Test\\Union');
        $code = $this->generator->generateStructClassCode($wrapperClass, $union);

        $this->assertEquals('ValueUnion', $wrapperClass->name);
        $this->assertStringContainsString('Generated wrapper class for C union ValueUnion', $code);
    }

    public function testConvertStructName(): void
    {
        $structure1 = new StructureDefinition('simple_struct', []);
        $structure2 = new StructureDefinition('struct complex_data_type', []);
        $structure3 = new StructureDefinition('union test_union', []);

        $class1 = $this->generator->generateStructClass($structure1, 'Test');
        $class2 = $this->generator->generateStructClass($structure2, 'Test');
        $class3 = $this->generator->generateStructClass($structure3, 'Test');

        $this->assertEquals('SimpleStruct', $class1->name);
        $this->assertEquals('ComplexDataType', $class2->name);
        $this->assertEquals('TestUnion', $class3->name);
    }

    public function testGenerateConstructor(): void
    {
        $structure = new StructureDefinition(
            'test_struct',
            [
                ['name' => 'id', 'type' => 'int'],
                ['name' => 'name', 'type' => 'char*'],
                ['name' => 'active', 'type' => 'bool']
            ]
        );

        $wrapperClass = $this->generator->generateStructClass($structure, 'Test');
        
        // Find constructor method
        $constructorFound = false;
        foreach ($wrapperClass->methods as $method) {
            if (str_contains($method, '__construct')) {
                $constructorFound = true;
                $this->assertStringContainsString('int $id = 0', $method);
                $this->assertStringContainsString('string $name = \'\'', $method);
                $this->assertStringContainsString('bool $active = false', $method);
                $this->assertStringContainsString('$this->id = $id;', $method);
                $this->assertStringContainsString('$this->name = $name;', $method);
                $this->assertStringContainsString('$this->active = $active;', $method);
                break;
            }
        }
        
        $this->assertTrue($constructorFound, 'Constructor method not found');
    }

    public function testGenerateGettersAndSetters(): void
    {
        $structure = new StructureDefinition(
            'test_struct',
            [
                ['name' => 'value', 'type' => 'int']
            ]
        );

        $wrapperClass = $this->generator->generateStructClass($structure, 'Test');
        
        $getterFound = false;
        $setterFound = false;
        
        foreach ($wrapperClass->methods as $method) {
            if (str_contains($method, 'getValue()')) {
                $getterFound = true;
                $this->assertStringContainsString('return $this->value;', $method);
            }
            if (str_contains($method, 'setValue(int $value)')) {
                $setterFound = true;
                $this->assertStringContainsString('$this->value = $value;', $method);
            }
        }
        
        $this->assertTrue($getterFound, 'Getter method not found');
        $this->assertTrue($setterFound, 'Setter method not found');
    }

    public function testGenerateToArrayMethod(): void
    {
        $structure = new StructureDefinition(
            'test_struct',
            [
                ['name' => 'id', 'type' => 'int'],
                ['name' => 'name', 'type' => 'char*']
            ]
        );

        $wrapperClass = $this->generator->generateStructClass($structure, 'Test');
        
        $toArrayFound = false;
        foreach ($wrapperClass->methods as $method) {
            if (str_contains($method, 'toArray()')) {
                $toArrayFound = true;
                $this->assertStringContainsString('\'id\' => $this->id', $method);
                $this->assertStringContainsString('\'name\' => $this->name', $method);
                break;
            }
        }
        
        $this->assertTrue($toArrayFound, 'toArray method not found');
    }

    public function testGenerateFromArrayMethod(): void
    {
        $structure = new StructureDefinition(
            'test_struct',
            [
                ['name' => 'id', 'type' => 'int'],
                ['name' => 'name', 'type' => 'char*']
            ]
        );

        $wrapperClass = $this->generator->generateStructClass($structure, 'Test');
        
        $fromArrayFound = false;
        foreach ($wrapperClass->methods as $method) {
            if (str_contains($method, 'fromArray(array $data)')) {
                $fromArrayFound = true;
                $this->assertStringContainsString('return new self(', $method);
                $this->assertStringContainsString('$data[\'id\'] ?? 0', $method);
                $this->assertStringContainsString('$data[\'name\'] ?? \'\'', $method);
                break;
            }
        }
        
        $this->assertTrue($fromArrayFound, 'fromArray method not found');
    }
}