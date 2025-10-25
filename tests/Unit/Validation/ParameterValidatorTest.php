<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Validation\ParameterValidator;
use Yangweijie\CWrapper\Validation\ValidationRule;
use Yangweijie\CWrapper\Validation\TypeConverter;
use Yangweijie\CWrapper\Validation\RangeValidator;

class ParameterValidatorTest extends TestCase
{
    private ParameterValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ParameterValidator();
    }

    public function testValidateTypeRule(): void
    {
        $rule = new ValidationRule('type', ['expected_type' => 'int']);
        $result = $this->validator->validate(42, $rule);

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
        $this->assertEquals(42, $result->convertedValue);
    }

    public function testValidateInvalidTypeRule(): void
    {
        $rule = new ValidationRule('type', ['expected_type' => 'int']);
        $result = $this->validator->validate('not_a_number', $rule);

        $this->assertFalse($result->isValid);
        $this->assertNotEmpty($result->errors);
    }

    public function testValidateRangeRule(): void
    {
        $rule = new ValidationRule('range', ['min' => 0, 'max' => 100]);
        $result = $this->validator->validate(50, $rule);

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    public function testValidateParameterCount(): void
    {
        $rule = new ValidationRule('count', ['expected_count' => 3]);
        $result = $this->validator->validate([1, 2, 3], $rule);

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
    }

    public function testValidateParameterCountMismatch(): void
    {
        $rule = new ValidationRule('count', ['expected_count' => 2]);
        $result = $this->validator->validate([1, 2, 3], $rule);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Parameter count mismatch', $result->errors[0]);
    }

    public function testValidateCustomRule(): void
    {
        $customValidator = fn($value) => $value > 0;
        $rule = new ValidationRule('custom', ['validator' => $customValidator]);
        $result = $this->validator->validate(5, $rule);

        $this->assertTrue($result->isValid);
    }

    public function testValidateCustomRuleFailure(): void
    {
        $customValidator = fn($value) => $value > 10;
        $rule = new ValidationRule('custom', ['validator' => $customValidator]);
        $result = $this->validator->validate(5, $rule);

        $this->assertFalse($result->isValid);
    } 
   public function testValidateParameters(): void
    {
        $parameters = [42, 'hello', 3.14];
        $expectedTypes = ['int', 'char*', 'float'];
        
        $result = $this->validator->validateParameters($parameters, $expectedTypes);

        $this->assertTrue($result->isValid);
        $this->assertEmpty($result->errors);
        $this->assertIsArray($result->convertedValue);
    }

    public function testValidateParametersCountMismatch(): void
    {
        $parameters = [42, 'hello'];
        $expectedTypes = ['int', 'char*', 'float'];
        
        $result = $this->validator->validateParameters($parameters, $expectedTypes);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Parameter count mismatch', $result->errors[0]);
    }

    public function testValidateParametersWithTypeError(): void
    {
        $parameters = ['not_a_number', 'hello'];
        $expectedTypes = ['int', 'char*'];
        
        $result = $this->validator->validateParameters($parameters, $expectedTypes);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Parameter 0', $result->errors[0]);
    }

    public function testValidateUnknownRuleType(): void
    {
        $rule = new ValidationRule('unknown_type', []);
        $result = $this->validator->validate(42, $rule);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Unknown validation rule type', $result->errors[0]);
    }

    public function testValidateCustomRuleWithStringError(): void
    {
        $customValidator = fn($value) => 'Custom error message';
        $rule = new ValidationRule('custom', ['validator' => $customValidator]);
        $result = $this->validator->validate(5, $rule);

        $this->assertFalse($result->isValid);
        $this->assertEquals(['Custom error message'], $result->errors);
    }

    public function testValidateCustomRuleWithArrayError(): void
    {
        $customValidator = fn($value) => ['Error 1', 'Error 2'];
        $rule = new ValidationRule('custom', ['validator' => $customValidator]);
        $result = $this->validator->validate(5, $rule);

        $this->assertFalse($result->isValid);
        $this->assertEquals(['Error 1', 'Error 2'], $result->errors);
    }

    public function testValidateCustomRuleException(): void
    {
        $customValidator = fn($value) => throw new \Exception('Validator exception');
        $rule = new ValidationRule('custom', ['validator' => $customValidator]);
        $result = $this->validator->validate(5, $rule);

        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Custom validation error', $result->errors[0]);
    }
}