<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Generator\TypeMapper;

class TypeMapperTest extends TestCase
{
    private TypeMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new TypeMapper();
    }

    public function testMapCTypeToPhp(): void
    {
        // Basic types
        $this->assertEquals('void', $this->mapper->mapCTypeToPhp('void'));
        $this->assertEquals('int', $this->mapper->mapCTypeToPhp('int'));
        $this->assertEquals('int', $this->mapper->mapCTypeToPhp('long'));
        $this->assertEquals('int', $this->mapper->mapCTypeToPhp('short'));
        $this->assertEquals('int', $this->mapper->mapCTypeToPhp('char'));
        $this->assertEquals('float', $this->mapper->mapCTypeToPhp('float'));
        $this->assertEquals('float', $this->mapper->mapCTypeToPhp('double'));
        $this->assertEquals('bool', $this->mapper->mapCTypeToPhp('bool'));
        $this->assertEquals('bool', $this->mapper->mapCTypeToPhp('_Bool'));
        
        // Unsigned types
        $this->assertEquals('int', $this->mapper->mapCTypeToPhp('unsigned int'));
        $this->assertEquals('int', $this->mapper->mapCTypeToPhp('unsigned long'));
        $this->assertEquals('int', $this->mapper->mapCTypeToPhp('size_t'));
        
        // Pointer types
        $this->assertEquals('string', $this->mapper->mapCTypeToPhp('char*'));
        $this->assertEquals('string', $this->mapper->mapCTypeToPhp('const char*'));
        $this->assertEquals('mixed', $this->mapper->mapCTypeToPhp('void*'));
        $this->assertEquals('mixed', $this->mapper->mapCTypeToPhp('int*'));
        
        // Array types
        $this->assertEquals('array', $this->mapper->mapCTypeToPhp('int[10]'));
        $this->assertEquals('array', $this->mapper->mapCTypeToPhp('char[256]'));
        
        // Struct/union types
        $this->assertEquals('mixed', $this->mapper->mapCTypeToPhp('struct user_data'));
        $this->assertEquals('mixed', $this->mapper->mapCTypeToPhp('union value_type'));
        
        // Unknown types
        $this->assertEquals('mixed', $this->mapper->mapCTypeToPhp('unknown_type'));
    }

    public function testMapPhpTypeToC(): void
    {
        $this->assertEquals('int', $this->mapper->mapPhpTypeToC('int'));
        $this->assertEquals('double', $this->mapper->mapPhpTypeToC('float'));
        $this->assertEquals('char*', $this->mapper->mapPhpTypeToC('string'));
        $this->assertEquals('bool', $this->mapper->mapPhpTypeToC('bool'));
        $this->assertEquals('void*', $this->mapper->mapPhpTypeToC('array'));
        $this->assertEquals('void*', $this->mapper->mapPhpTypeToC('mixed'));
        $this->assertEquals('void*', $this->mapper->mapPhpTypeToC('unknown'));
    }

    public function testGenerateValidation(): void
    {
        // Integer validation
        $validation = $this->mapper->generateValidation('testParam', 'int');
        $this->assertStringContainsString('if (!is_int($testParam))', $validation);
        $this->assertStringContainsString('InvalidArgumentException', $validation);
        $this->assertStringContainsString('Parameter testParam must be an integer', $validation);
        
        // Float validation
        $validation = $this->mapper->generateValidation('floatParam', 'float');
        $this->assertStringContainsString('if (!is_numeric($floatParam))', $validation);
        $this->assertStringContainsString('Parameter floatParam must be numeric', $validation);
        
        // String validation
        $validation = $this->mapper->generateValidation('stringParam', 'char*');
        $this->assertStringContainsString('if (!is_string($stringParam))', $validation);
        $this->assertStringContainsString('Parameter stringParam must be a string', $validation);
        
        // Boolean validation
        $validation = $this->mapper->generateValidation('boolParam', 'bool');
        $this->assertStringContainsString('if (!is_bool($boolParam))', $validation);
        $this->assertStringContainsString('Parameter boolParam must be a boolean', $validation);
        
        // Array validation
        $validation = $this->mapper->generateValidation('arrayParam', 'int[10]');
        $this->assertStringContainsString('if (!is_array($arrayParam))', $validation);
        $this->assertStringContainsString('Parameter arrayParam must be an array', $validation);
        
        // No validation for mixed types
        $validation = $this->mapper->generateValidation('mixedParam', 'void*');
        $this->assertEmpty($validation);
    }

    public function testIsPointerType(): void
    {
        $this->assertTrue($this->mapper->isPointerType('char*'));
        $this->assertTrue($this->mapper->isPointerType('void*'));
        $this->assertTrue($this->mapper->isPointerType('int*'));
        $this->assertTrue($this->mapper->isPointerType('const char*'));
        
        $this->assertFalse($this->mapper->isPointerType('int'));
        $this->assertFalse($this->mapper->isPointerType('float'));
        $this->assertFalse($this->mapper->isPointerType('char'));
    }

    public function testIsStructType(): void
    {
        $this->assertTrue($this->mapper->isStructType('struct user_data'));
        $this->assertTrue($this->mapper->isStructType('union value_type'));
        
        $this->assertFalse($this->mapper->isStructType('int'));
        $this->assertFalse($this->mapper->isStructType('char*'));
        $this->assertFalse($this->mapper->isStructType('user_data'));
    }

    public function testTypeMapWithWhitespace(): void
    {
        $this->assertEquals('string', $this->mapper->mapCTypeToPhp(' char* '));
        $this->assertEquals('int', $this->mapper->mapCTypeToPhp(' unsigned int '));
        $this->assertEquals('mixed', $this->mapper->mapCTypeToPhp(' struct test '));
    }

    public function testComplexPointerTypes(): void
    {
        $this->assertEquals('mixed', $this->mapper->mapCTypeToPhp('struct user_data*'));
        $this->assertEquals('mixed', $this->mapper->mapCTypeToPhp('float*'));
        $this->assertEquals('mixed', $this->mapper->mapCTypeToPhp('double*'));
    }

    public function testValidationWithDifferentParameterNames(): void
    {
        $validation1 = $this->mapper->generateValidation('param1', 'int');
        $validation2 = $this->mapper->generateValidation('anotherParam', 'int');
        
        $this->assertStringContainsString('$param1', $validation1);
        $this->assertStringContainsString('$anotherParam', $validation2);
        $this->assertStringNotContainsString('$param1', $validation2);
        $this->assertStringNotContainsString('$anotherParam', $validation1);
    }
}