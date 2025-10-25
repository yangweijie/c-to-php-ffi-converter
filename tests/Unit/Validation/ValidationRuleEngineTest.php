<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Validation\ValidationRuleEngine;
use Yangweijie\CWrapper\Validation\ValidationRule;
use Yangweijie\CWrapper\Exception\ValidationException;

class ValidationRuleEngineTest extends TestCase
{
    private ValidationRuleEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new ValidationRuleEngine();
    }

    public function testAddAndGetTypeRule(): void
    {
        $rule = new ValidationRule('range', ['min' => 0, 'max' => 100]);
        $this->engine->addTypeRule('custom_int', $rule);

        $rules = $this->engine->getRulesForType('custom_int');
        $this->assertCount(1, $rules);
        $this->assertEquals($rule, $rules[0]);
    }

    public function testAddCustomValidator(): void
    {
        $validator = fn($value) => $value > 0;
        $this->engine->addCustomValidator('positive', $validator);

        $validators = $this->engine->getCustomValidators();
        $this->assertArrayHasKey('positive', $validators);
        $this->assertEquals($validator, $validators['positive']);
    }

    public function testValidateParameterWithDefaultRules(): void
    {
        $result = $this->engine->validateParameter(42, 'int');
        $this->assertTrue($result->isValid);

        $result = $this->engine->validateParameter(300, 'unsigned char');
        $this->assertFalse($result->isValid);
    }

    public function testValidateParameterWithCustomRules(): void
    {
        $rule = new ValidationRule('range', ['min' => 10, 'max' => 20]);
        $this->engine->addTypeRule('custom_range', $rule);

        $result = $this->engine->validateParameter(15, 'custom_range');
        $this->assertTrue($result->isValid);

        $result = $this->engine->validateParameter(25, 'custom_range');
        $this->assertFalse($result->isValid);
    }

    public function testValidateFunctionParameters(): void
    {
        $parameters = [42, 'hello', 3.14];
        $cTypes = ['int', 'char*', 'float'];

        $result = $this->engine->validateFunctionParameters($parameters, $cTypes);
        $this->assertTrue($result->isValid);
        $this->assertIsArray($result->convertedValue);
    }

    public function testValidateFunctionParametersCountMismatch(): void
    {
        $parameters = [42, 'hello'];
        $cTypes = ['int', 'char*', 'float'];

        $result = $this->engine->validateFunctionParameters($parameters, $cTypes);
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Parameter count mismatch', $result->errors[0]);
    }

    public function testValidateFunctionParametersWithErrors(): void
    {
        $parameters = [300, 'hello']; // 300 is out of range for unsigned char
        $cTypes = ['unsigned char', 'char*'];

        $result = $this->engine->validateFunctionParameters($parameters, $cTypes);
        $this->assertFalse($result->isValid);
        $this->assertStringContainsString('Parameter 0', $result->errors[0]);
    } 
   public function testCreateRuleFromConfig(): void
    {
        $config = [
            'type' => 'range',
            'parameters' => ['min' => 0, 'max' => 100]
        ];

        $rule = $this->engine->createRuleFromConfig($config);
        $this->assertEquals('range', $rule->type);
        $this->assertEquals(['min' => 0, 'max' => 100], $rule->parameters);
    }

    public function testCreateRuleFromConfigWithCustomValidator(): void
    {
        $validator = fn($value) => $value > 0;
        $this->engine->addCustomValidator('positive', $validator);

        $config = [
            'type' => 'custom',
            'parameters' => ['validator_name' => 'positive']
        ];

        $rule = $this->engine->createRuleFromConfig($config);
        $this->assertEquals('custom', $rule->type);
        $this->assertEquals($validator, $rule->parameters['validator']);
    }

    public function testCreateRuleFromConfigWithUnknownValidator(): void
    {
        $config = [
            'type' => 'custom',
            'parameters' => ['validator_name' => 'unknown']
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Custom validator 'unknown' not found");
        $this->engine->createRuleFromConfig($config);
    }

    public function testLoadRulesFromConfig(): void
    {
        $config = [
            'custom_int' => [
                [
                    'type' => 'range',
                    'parameters' => ['min' => 0, 'max' => 100]
                ],
                [
                    'type' => 'type',
                    'parameters' => ['expected_type' => 'int']
                ]
            ]
        ];

        $this->engine->loadRulesFromConfig($config);
        $rules = $this->engine->getRulesForType('custom_int');
        $this->assertCount(2, $rules);
    }

    public function testLoadRulesFromConfigWithInvalidRule(): void
    {
        $config = [
            'custom_int' => [
                'not_an_array'
            ]
        ];

        // Should not throw exception, just skip invalid rules
        $this->engine->loadRulesFromConfig($config);
        $rules = $this->engine->getRulesForType('custom_int');
        $this->assertEmpty($rules);
    }

    public function testRemoveTypeRules(): void
    {
        $rule = new ValidationRule('range', ['min' => 0, 'max' => 100]);
        $this->engine->addTypeRule('test_type', $rule);

        $this->assertNotEmpty($this->engine->getRulesForType('test_type'));
        
        $this->engine->removeTypeRules('test_type');
        $this->assertEmpty($this->engine->getRulesForType('test_type'));
    }

    public function testRemoveCustomValidator(): void
    {
        $validator = fn($value) => $value > 0;
        $this->engine->addCustomValidator('test_validator', $validator);

        $this->assertArrayHasKey('test_validator', $this->engine->getCustomValidators());
        
        $this->engine->removeCustomValidator('test_validator');
        $this->assertArrayNotHasKey('test_validator', $this->engine->getCustomValidators());
    }

    public function testGetAllTypeRules(): void
    {
        $rule1 = new ValidationRule('range', ['min' => 0, 'max' => 100]);
        $rule2 = new ValidationRule('type', ['expected_type' => 'int']);
        
        $this->engine->addTypeRule('type1', $rule1);
        $this->engine->addTypeRule('type2', $rule2);

        $allRules = $this->engine->getAllTypeRules();
        $this->assertArrayHasKey('type1', $allRules);
        $this->assertArrayHasKey('type2', $allRules);
        
        // Should also include default rules
        $this->assertArrayHasKey('int', $allRules);
        $this->assertArrayHasKey('char', $allRules);
    }

    public function testDefaultRulesAreLoaded(): void
    {
        // Test that default rules are automatically loaded
        $intRules = $this->engine->getRulesForType('int');
        $this->assertNotEmpty($intRules);

        $charRules = $this->engine->getRulesForType('char');
        $this->assertNotEmpty($charRules);
        $this->assertCount(2, $charRules); // type + range rule

        $floatRules = $this->engine->getRulesForType('float');
        $this->assertNotEmpty($floatRules);
    }
}