<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Validation\RangeValidator;
use Yangweijie\CWrapper\Validation\ValidationRule;

class RangeValidatorTest extends TestCase
{
    private RangeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new RangeValidator();
    }

    public function testValidateNumericRange(): void
    {
        $rule = new ValidationRule('range', ['min' => 0, 'max' => 100]);
        
        $result = $this->validator->validate(50, $rule);
        $this->assertTrue($result->isValid);
        $this->assertEquals(50, $result->convertedValue);

        $result = $this->validator->validate(0, $rule);
        $this->assertTrue($result->isValid);

        $result = $this->validator->validate(100, $rule);
        $this->assertTrue($result->isValid);
    }

    public function testValidateNumericRangeOutOfBounds(): void
    {
        $rule = new ValidationRule('range', ['min' => 0, 'max' => 100]);
        
        $result = $this->validator->validate(-1, $rule);
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('below minimum', $result->errors[0]);

        $result = $this->validator->validate(101, $rule);
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('above maximum', $result->errors[0]);
    }

    public function testValidateNumericRangeMinOnly(): void
    {
        $rule = new ValidationRule('range', ['min' => 10]);
        
        $result = $this->validator->validate(15, $rule);
        $this->assertTrue($result->isValid);

        $result = $this->validator->validate(5, $rule);
        $this->assertFalse($result->isValid);
    }

    public function testValidateNumericRangeMaxOnly(): void
    {
        $rule = new ValidationRule('range', ['max' => 10]);
        
        $result = $this->validator->validate(5, $rule);
        $this->assertTrue($result->isValid);

        $result = $this->validator->validate(15, $rule);
        $this->assertFalse($result->isValid);
    }

    public function testValidateStringLength(): void
    {
        $rule = new ValidationRule('range', ['length_min' => 3, 'length_max' => 10]);
        
        $result = $this->validator->validate('hello', $rule);
        $this->assertTrue($result->isValid);

        $result = $this->validator->validate('hi', $rule);
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('below minimum', $result->errors[0]);

        $result = $this->validator->validate('this_is_too_long', $rule);
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('above maximum', $result->errors[0]);
    }  
  public function testValidateArrayLength(): void
    {
        $rule = new ValidationRule('range', ['length_min' => 2, 'length_max' => 5]);
        
        $result = $this->validator->validate([1, 2, 3], $rule);
        $this->assertTrue($result->isValid);

        $result = $this->validator->validate([1], $rule);
        $this->assertFalse($result->isValid);

        $result = $this->validator->validate([1, 2, 3, 4, 5, 6], $rule);
        $this->assertFalse($result->isValid);
    }

    public function testValidateAllowedValues(): void
    {
        $rule = new ValidationRule('range', ['allowed_values' => ['red', 'green', 'blue']]);
        
        $result = $this->validator->validate('red', $rule);
        $this->assertTrue($result->isValid);

        $result = $this->validator->validate('yellow', $rule);
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('must be one of', $result->errors[0]);
    }

    public function testValidatePattern(): void
    {
        $rule = new ValidationRule('range', ['pattern' => '/^[a-z]+$/']);
        
        $result = $this->validator->validate('hello', $rule);
        $this->assertTrue($result->isValid);

        $result = $this->validator->validate('Hello123', $rule);
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('does not match required pattern', $result->errors[0]);
    }

    public function testValidateNonNumericForRange(): void
    {
        $rule = new ValidationRule('range', ['min' => 0, 'max' => 100]);
        
        $result = $this->validator->validate('not_numeric', $rule);
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('must be numeric', $result->errors[0]);
    }

    public function testValidateNonStringForPattern(): void
    {
        $rule = new ValidationRule('range', ['pattern' => '/^[a-z]+$/']);
        
        $result = $this->validator->validate(123, $rule);
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('must be string', $result->errors[0]);
    }

    public function testValidateWrongRuleType(): void
    {
        $rule = new ValidationRule('type', ['expected_type' => 'int']);
        
        $result = $this->validator->validate(42, $rule);
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('can only handle \'range\' type rules', $result->errors[0]);
    }

    public function testValidateNoConstraints(): void
    {
        $rule = new ValidationRule('range', []);
        
        $result = $this->validator->validate(42, $rule);
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('No valid range constraints', $result->errors[0]);
    }

    public function testValidateInvalidAllowedValues(): void
    {
        $rule = new ValidationRule('range', ['allowed_values' => 'not_an_array']);
        
        $result = $this->validator->validate('test', $rule);
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('must be an array', $result->errors[0]);
    }

    public function testValidateInvalidPattern(): void
    {
        $rule = new ValidationRule('range', ['pattern' => 123]);
        
        $result = $this->validator->validate('test', $rule);
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('must be a string', $result->errors[0]);
    }
}