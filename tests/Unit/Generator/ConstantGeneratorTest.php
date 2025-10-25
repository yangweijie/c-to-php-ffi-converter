<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Generator\ConstantGenerator;

class ConstantGeneratorTest extends TestCase
{
    private ConstantGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ConstantGenerator();
    }

    public function testGenerateConstantsClass(): void
    {
        $constants = [
            'MAX_SIZE' => 1024,
            'DEFAULT_NAME' => 'test',
            'PI_VALUE' => 3.14159,
            'ENABLED' => true,
            'DISABLED' => false
        ];

        $wrapperClass = $this->generator->generateConstantsClass(
            $constants,
            'Test\\Constants',
            'TestConstants'
        );

        $this->assertEquals('TestConstants', $wrapperClass->name);
        $this->assertEquals('Test\\Constants', $wrapperClass->namespace);
        $this->assertEquals(5, count($wrapperClass->constants));
        $this->assertGreaterThan(0, count($wrapperClass->methods));
    }

    public function testGenerateConstantsClassCode(): void
    {
        $constants = [
            'TEST_CONST' => 42,
            'TEST_STRING' => 'hello'
        ];

        $wrapperClass = $this->generator->generateConstantsClass(
            $constants,
            'Test\\Constants'
        );

        $code = $this->generator->generateConstantsClassCode($wrapperClass);

        $this->assertStringContainsString('<?php', $code);
        $this->assertStringContainsString('namespace Test\\Constants;', $code);
        $this->assertStringContainsString('class Constants', $code);
        $this->assertStringContainsString('public const TEST_CONST = 42;', $code);
        $this->assertStringContainsString('public const TEST_STRING = \'hello\';', $code);
    }

    public function testNormalizeConstantName(): void
    {
        $constants = [
            'normal_const' => 1,
            'ALREADY_UPPER' => 2,
            'mixed-Case_Name' => 3,
            '123_starts_with_number' => 4,
            'special@chars#here' => 5
        ];

        $wrapperClass = $this->generator->generateConstantsClass($constants, 'Test');
        $code = $this->generator->generateConstantsClassCode($wrapperClass);

        $this->assertStringContainsString('NORMAL_CONST', $code);
        $this->assertStringContainsString('ALREADY_UPPER', $code);
        $this->assertStringContainsString('MIXED_CASE_NAME', $code);
        $this->assertStringContainsString('_123_STARTS_WITH_NUMBER', $code);
        $this->assertStringContainsString('SPECIAL_CHARS_HERE', $code);
    }

    public function testFormatConstantValues(): void
    {
        $constants = [
            'INT_VAL' => 42,
            'FLOAT_VAL' => 3.14,
            'STRING_VAL' => 'test string',
            'BOOL_TRUE' => true,
            'BOOL_FALSE' => false,
            'NULL_VAL' => null,
            'ARRAY_VAL' => [1, 2, 3]
        ];

        $wrapperClass = $this->generator->generateConstantsClass($constants, 'Test');
        $code = $this->generator->generateConstantsClassCode($wrapperClass);

        $this->assertStringContainsString('INT_VAL = 42;', $code);
        $this->assertStringContainsString('FLOAT_VAL = 3.14;', $code);
        $this->assertStringContainsString('STRING_VAL = \'test string\';', $code);
        $this->assertStringContainsString('BOOL_TRUE = true;', $code);
        $this->assertStringContainsString('BOOL_FALSE = false;', $code);
        $this->assertStringContainsString('NULL_VAL = null;', $code);
        $this->assertStringContainsString('ARRAY_VAL = array', $code);
    }

    public function testGenerateGetAllConstantsMethod(): void
    {
        $constants = [
            'TEST1' => 1,
            'TEST2' => 'value'
        ];

        $wrapperClass = $this->generator->generateConstantsClass($constants, 'Test');
        
        $getAllConstantsFound = false;
        foreach ($wrapperClass->methods as $method) {
            if (str_contains($method, 'getAllConstants()')) {
                $getAllConstantsFound = true;
                $this->assertStringContainsString('return [', $method);
                $this->assertStringContainsString('\'TEST1\' => 1', $method);
                $this->assertStringContainsString('\'TEST2\' => \'value\'', $method);
                break;
            }
        }
        
        $this->assertTrue($getAllConstantsFound, 'getAllConstants method not found');
    }

    public function testGenerateGetConstantMethod(): void
    {
        $constants = ['TEST' => 1];
        $wrapperClass = $this->generator->generateConstantsClass($constants, 'Test');
        
        $getConstantFound = false;
        foreach ($wrapperClass->methods as $method) {
            if (str_contains($method, 'getConstant(string $name)')) {
                $getConstantFound = true;
                $this->assertStringContainsString('$constants = self::getAllConstants();', $method);
                $this->assertStringContainsString('InvalidArgumentException', $method);
                $this->assertStringContainsString('return $constants[$name];', $method);
                break;
            }
        }
        
        $this->assertTrue($getConstantFound, 'getConstant method not found');
    }

    public function testGenerateHasConstantMethod(): void
    {
        $constants = ['TEST' => 1];
        $wrapperClass = $this->generator->generateConstantsClass($constants, 'Test');
        
        $hasConstantFound = false;
        foreach ($wrapperClass->methods as $method) {
            if (str_contains($method, 'hasConstant(string $name)')) {
                $hasConstantFound = true;
                $this->assertStringContainsString('array_key_exists($name, $constants)', $method);
                break;
            }
        }
        
        $this->assertTrue($hasConstantFound, 'hasConstant method not found');
    }

    public function testGenerateConstantProperties(): void
    {
        $constants = [
            'TEST_CONST' => 42,
            'TEST_STRING' => 'hello'
        ];

        $properties = $this->generator->generateConstantProperties($constants);

        $this->assertCount(2, $properties);
        $this->assertStringContainsString('public static $TEST_CONST = 42;', $properties[0]);
        $this->assertStringContainsString('public static $TEST_STRING = \'hello\';', $properties[1]);
        $this->assertStringContainsString('Constant TEST_CONST', $properties[0]);
        $this->assertStringContainsString('Constant TEST_STRING', $properties[1]);
    }

    public function testEmptyConstants(): void
    {
        $wrapperClass = $this->generator->generateConstantsClass([], 'Test');
        
        $this->assertEquals('Constants', $wrapperClass->name);
        $this->assertEquals('Test', $wrapperClass->namespace);
        $this->assertEmpty($wrapperClass->constants);
        $this->assertGreaterThan(0, count($wrapperClass->methods)); // Should still have utility methods
    }
}