<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Config\ValidationConfig;

class ValidationConfigTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $config = new ValidationConfig();
        
        $this->assertTrue($config->isParameterValidationEnabled());
        $this->assertTrue($config->isTypeConversionEnabled());
        $this->assertEmpty($config->getCustomValidationRules());
    }

    public function testConstructorWithParameters(): void
    {
        $customRules = ['rule1' => 'value1', 'rule2' => 'value2'];
        $config = new ValidationConfig(false, false, $customRules);
        
        $this->assertFalse($config->isParameterValidationEnabled());
        $this->assertFalse($config->isTypeConversionEnabled());
        $this->assertEquals($customRules, $config->getCustomValidationRules());
    }

    public function testSetParameterValidation(): void
    {
        $config = new ValidationConfig();
        
        $result = $config->setParameterValidation(false);
        
        $this->assertSame($config, $result);
        $this->assertFalse($config->isParameterValidationEnabled());
    }

    public function testSetTypeConversion(): void
    {
        $config = new ValidationConfig();
        
        $result = $config->setTypeConversion(false);
        
        $this->assertSame($config, $result);
        $this->assertFalse($config->isTypeConversionEnabled());
    }

    public function testSetCustomValidationRules(): void
    {
        $config = new ValidationConfig();
        $rules = ['custom_rule' => 'custom_value'];
        
        $result = $config->setCustomValidationRules($rules);
        
        $this->assertSame($config, $result);
        $this->assertEquals($rules, $config->getCustomValidationRules());
    }

    public function testToArray(): void
    {
        $customRules = ['rule1' => 'value1'];
        $config = new ValidationConfig(false, true, $customRules);
        
        $expected = [
            'enableParameterValidation' => false,
            'enableTypeConversion' => true,
            'customValidationRules' => $customRules,
        ];
        
        $this->assertEquals($expected, $config->toArray());
    }

    public function testFromArray(): void
    {
        $data = [
            'enableParameterValidation' => false,
            'enableTypeConversion' => true,
            'customValidationRules' => ['rule1' => 'value1'],
        ];
        
        $config = ValidationConfig::fromArray($data);
        
        $this->assertFalse($config->isParameterValidationEnabled());
        $this->assertTrue($config->isTypeConversionEnabled());
        $this->assertEquals(['rule1' => 'value1'], $config->getCustomValidationRules());
    }

    public function testFromArrayWithDefaults(): void
    {
        $config = ValidationConfig::fromArray([]);
        
        $this->assertTrue($config->isParameterValidationEnabled());
        $this->assertTrue($config->isTypeConversionEnabled());
        $this->assertEmpty($config->getCustomValidationRules());
    }

    public function testFromArrayPartial(): void
    {
        $data = ['enableParameterValidation' => false];
        $config = ValidationConfig::fromArray($data);
        
        $this->assertFalse($config->isParameterValidationEnabled());
        $this->assertTrue($config->isTypeConversionEnabled());
        $this->assertEmpty($config->getCustomValidationRules());
    }
}