<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Validation\TypeConverter;

class TypeConverterTest extends TestCase
{
    private TypeConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new TypeConverter();
    }

    public function testConvertIntegerTypes(): void
    {
        $result = $this->converter->convert(42, 'int');
        $this->assertTrue($result->isValid);
        $this->assertEquals(42, $result->convertedValue);

        $result = $this->converter->convert(255, 'unsigned char');
        $this->assertTrue($result->isValid);
        $this->assertEquals(255, $result->convertedValue);
    }

    public function testConvertIntegerOutOfRange(): void
    {
        $result = $this->converter->convert(300, 'unsigned char');
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('above maximum', $result->errors[0]);

        $result = $this->converter->convert(-1, 'unsigned char');
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('below minimum', $result->errors[0]);
    }

    public function testConvertFloatTypes(): void
    {
        $result = $this->converter->convert(3.14, 'float');
        $this->assertTrue($result->isValid);
        $this->assertEquals(3.14, $result->convertedValue);

        $result = $this->converter->convert(42, 'double');
        $this->assertTrue($result->isValid);
        $this->assertEquals(42.0, $result->convertedValue);
    }

    public function testConvertStringTypes(): void
    {
        $result = $this->converter->convert('hello', 'char*');
        $this->assertTrue($result->isValid);
        $this->assertEquals('hello', $result->convertedValue);

        $result = $this->converter->convert(null, 'const char*');
        $this->assertTrue($result->isValid);
        $this->assertNull($result->convertedValue);
    }

    public function testConvertBooleanTypes(): void
    {
        $result = $this->converter->convert(true, 'bool');
        $this->assertTrue($result->isValid);
        $this->assertTrue($result->convertedValue);

        $result = $this->converter->convert(1, '_Bool');
        $this->assertTrue($result->isValid);
        $this->assertTrue($result->convertedValue);

        $result = $this->converter->convert(0, 'bool');
        $this->assertTrue($result->isValid);
        $this->assertFalse($result->convertedValue);
    }

    public function testConvertPointerTypes(): void
    {
        $resource = fopen('php://memory', 'r');
        $result = $this->converter->convert($resource, 'void*');
        $this->assertTrue($result->isValid);
        fclose($resource);

        $result = $this->converter->convert(null, 'void*');
        $this->assertTrue($result->isValid);
        $this->assertNull($result->convertedValue);
    } 
   public function testConvertStringToNumber(): void
    {
        $result = $this->converter->convert('42', 'int');
        $this->assertTrue($result->isValid);
        $this->assertEquals(42, $result->convertedValue);

        $result = $this->converter->convert('3.14', 'float');
        $this->assertTrue($result->isValid);
        $this->assertEquals(3.14, $result->convertedValue);
    }

    public function testConvertInvalidStringToNumber(): void
    {
        $result = $this->converter->convert('not_a_number', 'int');
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Cannot convert', $result->errors[0]);
    }

    public function testConvertFloatToIntegerWithPrecisionLoss(): void
    {
        $result = $this->converter->convert(3.14, 'int');
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('cannot be safely converted', $result->errors[0]);
    }

    public function testConvertBooleanToInteger(): void
    {
        $result = $this->converter->convert(true, 'int');
        $this->assertTrue($result->isValid);
        $this->assertEquals(1, $result->convertedValue);

        $result = $this->converter->convert(false, 'int');
        $this->assertTrue($result->isValid);
        $this->assertEquals(0, $result->convertedValue);
    }

    public function testConvertStringToBoolean(): void
    {
        $result = $this->converter->convert('true', 'bool');
        $this->assertTrue($result->isValid);
        $this->assertTrue($result->convertedValue);

        $result = $this->converter->convert('false', 'bool');
        $this->assertTrue($result->isValid);
        $this->assertFalse($result->convertedValue);

        $result = $this->converter->convert('1', 'bool');
        $this->assertTrue($result->isValid);
        $this->assertTrue($result->convertedValue);

        $result = $this->converter->convert('0', 'bool');
        $this->assertTrue($result->isValid);
        $this->assertFalse($result->convertedValue);
    }

    public function testIsCompatible(): void
    {
        $this->assertTrue($this->converter->isCompatible(42, 'int'));
        $this->assertTrue($this->converter->isCompatible('hello', 'char*'));
        $this->assertFalse($this->converter->isCompatible('not_a_number', 'int'));
    }

    public function testGetExpectedPhpType(): void
    {
        $this->assertEquals('integer', $this->converter->getExpectedPhpType('int'));
        $this->assertEquals('double', $this->converter->getExpectedPhpType('float'));
        $this->assertEquals('string', $this->converter->getExpectedPhpType('char*'));
        $this->assertEquals('boolean', $this->converter->getExpectedPhpType('bool'));
        $this->assertEquals('mixed', $this->converter->getExpectedPhpType('unknown_type'));
    }

    public function testNormalizeCType(): void
    {
        // Test type aliases
        $result = $this->converter->convert(255, 'uint8_t');
        $this->assertTrue($result->isValid);

        $result = $this->converter->convert(65535, 'uint16_t');
        $this->assertTrue($result->isValid);
    }

    public function testHandleUnknownType(): void
    {
        $result = $this->converter->convert('anything', 'unknown_custom_type');
        $this->assertTrue($result->isValid); // Unknown types are permissive
        $this->assertEquals('anything', $result->convertedValue);
    }
}