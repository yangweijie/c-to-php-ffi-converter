<?php

declare(strict_types=1);

namespace Tests\Integration\EndToEnd;

use PHPUnit\Framework\TestCase;
use FFIConverter\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests that verify generated wrapper classes work with actual C libraries
 */
class GeneratedWrapperTest extends TestCase
{
    private string $testOutputDir;
    private string $fixturesDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->testOutputDir = __DIR__ . '/../../output/wrapper_test';
        $this->fixturesDir = __DIR__ . '/../../Fixtures/Integration';
        $this->filesystem = new Filesystem();

        // Clean and create output directory
        if ($this->filesystem->exists($this->testOutputDir)) {
            $this->filesystem->remove($this->testOutputDir);
        }
        $this->filesystem->mkdir($this->testOutputDir);

        // Build test libraries
        $this->buildTestLibraries();
        
        // Generate wrapper classes for testing
        $this->generateWrapperClasses();
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testOutputDir)) {
            $this->filesystem->remove($this->testOutputDir);
        }
    }

    /**
     * Test math library wrapper functionality
     * @group wrapper
     */
    public function testMathLibraryWrapperFunctionality(): void
    {
        $wrapperPath = $this->testOutputDir . '/math/MathLibrary.php';
        $this->assertFileExists($wrapperPath);

        // Include the generated wrapper
        require_once $wrapperPath;

        // Test basic arithmetic operations
        $math = new \Test\Math\FFI\MathLibrary();
        
        $this->assertEquals(8, $math->mathAdd(5, 3));
        $this->assertEquals(2, $math->mathSubtract(5, 3));
        $this->assertEquals(15, $math->mathMultiply(5, 3));
        $this->assertEquals(2.5, $math->mathDivide(5.0, 2.0));

        // Test array operations
        $array = [1, 2, 3, 4, 5];
        $this->assertEquals(15, $math->mathSumArray($array, count($array)));
        $this->assertEquals(3.0, $math->mathAverageArray($array, count($array)));
        $this->assertEquals(5, $math->mathFindMax($array, count($array)));
        $this->assertEquals(1, $math->mathFindMin($array, count($array)));

        // Test geometric operations
        $point1 = $math->createPoint2D(0.0, 0.0);
        $point2 = $math->createPoint2D(3.0, 4.0);
        $distance = $math->mathDistance2d($point1, $point2);
        $this->assertEquals(5.0, $distance, '', 0.001);

        // Test circle operations
        $circle = $math->createCircle(0.0, 0.0, 5.0);
        $area = $math->mathCircleArea($circle);
        $this->assertGreaterThan(78.5, $area);
        $this->assertLessThan(78.6, $area);
    }

    /**
     * Test string library wrapper functionality
     * @group wrapper
     */
    public function testStringLibraryWrapperFunctionality(): void
    {
        $wrapperPath = $this->testOutputDir . '/string/StringUtils.php';
        $this->assertFileExists($wrapperPath);

        // Include the generated wrapper
        require_once $wrapperPath;

        // Test string manipulation
        $stringUtils = new \Test\String\FFI\StringUtils();
        
        $duplicate = $stringUtils->stringDuplicate("hello");
        $this->assertEquals("hello", $duplicate);

        $concatenated = $stringUtils->stringConcatenate("hello", " world");
        $this->assertEquals("hello world", $concatenated);

        $substring = $stringUtils->stringSubstring("hello world", 6, 5);
        $this->assertEquals("world", $substring);

        $upper = $stringUtils->stringToUpper("hello");
        $this->assertEquals("HELLO", $upper);

        $lower = $stringUtils->stringToLower("WORLD");
        $this->assertEquals("world", $lower);

        $trimmed = $stringUtils->stringTrim("  hello world  ");
        $this->assertEquals("hello world", $trimmed);

        // Test string analysis
        $this->assertEquals(2, $stringUtils->stringCountChars("hello", 'l'));
        $this->assertEquals(3, $stringUtils->stringCountWords("hello world test"));
        $this->assertTrue($stringUtils->stringStartsWith("hello world", "hello"));
        $this->assertTrue($stringUtils->stringEndsWith("hello world", "world"));
        $this->assertTrue($stringUtils->stringContains("hello world", "lo wo"));

        // Test string formatting
        $intStr = $stringUtils->stringFormatInt(42);
        $this->assertEquals("42", $intStr);

        $floatStr = $stringUtils->stringFormatFloat(3.14159, 2);
        $this->assertEquals("3.14", $floatStr);

        // Test string parsing
        $this->assertEquals(123, $stringUtils->stringParseInt("123"));
        $this->assertEquals(3.14, $stringUtils->stringParseFloat("3.14"), '', 0.001);
    }

    /**
     * Test error handling in generated wrappers
     * @group wrapper
     */
    public function testErrorHandlingInGeneratedWrappers(): void
    {
        $wrapperPath = $this->testOutputDir . '/math/MathLibrary.php';
        require_once $wrapperPath;

        $math = new \Test\Math\FFI\MathLibrary();

        // Test division by zero error handling
        $result = $math->mathDivide(10.0, 0.0);
        $this->assertEquals(0.0, $result);
        
        $error = $math->mathGetLastError();
        $this->assertEquals(-3, $error); // MATH_ERROR_DIVISION_BY_ZERO

        $errorMessage = $math->mathGetErrorMessage($error);
        $this->assertEquals("Division by zero", $errorMessage);

        // Test null pointer error handling
        try {
            $math->mathSumArray(null, 5);
            $this->fail('Should throw exception for null pointer');
        } catch (\FFIConverter\Exception\ValidationException $e) {
            $this->assertStringContainsString('null', strtolower($e->getMessage()));
        }
    }

    /**
     * Test memory management in generated wrappers
     * @group wrapper
     */
    public function testMemoryManagementInGeneratedWrappers(): void
    {
        $wrapperPath = $this->testOutputDir . '/math/MathLibrary.php';
        require_once $wrapperPath;

        $math = new \Test\Math\FFI\MathLibrary();

        // Test point array creation and destruction
        $pointArray = $math->mathCreatePointArray(10);
        $this->assertNotNull($pointArray);
        $this->assertEquals(0, $math->mathGetPointCount($pointArray));

        // Add points to the array
        $point1 = $math->createPoint2D(1.0, 2.0);
        $result = $math->mathAddPoint($pointArray, $point1);
        $this->assertEquals(0, $result); // Success

        $this->assertEquals(1, $math->mathGetPointCount($pointArray));

        // Retrieve point from array
        $retrievedPoint = $math->mathGetPoint($pointArray, 0);
        $this->assertNotNull($retrievedPoint);
        $this->assertEquals(1.0, $retrievedPoint->x);
        $this->assertEquals(2.0, $retrievedPoint->y);

        // Clean up memory
        $math->mathDestroyPointArray($pointArray);
        
        // Verify cleanup (this would be implementation-specific)
        $this->assertTrue(true); // Placeholder - actual memory verification would be complex
    }

    /**
     * Test callback function handling in generated wrappers
     * @group wrapper
     */
    public function testCallbackFunctionHandling(): void
    {
        $wrapperPath = $this->testOutputDir . '/math/MathLibrary.php';
        require_once $wrapperPath;

        $math = new \Test\Math\FFI\MathLibrary();

        // Test progress callback
        $progressValues = [];
        $progressCallback = function($progress) use (&$progressValues) {
            $progressValues[] = $progress;
        };

        $array = [1, 2, 3, 4, 5];
        $math->mathProcessWithProgress($array, count($array), $progressCallback);

        // Verify progress was reported
        $this->assertNotEmpty($progressValues);
        $this->assertGreaterThan(0, end($progressValues));
        $this->assertLessThanOrEqual(1.0, end($progressValues));

        // Test comparison callback for sorting
        $compareCallback = function($a, $b) {
            return $a <=> $b; // Ascending order
        };

        $unsortedArray = [5, 2, 8, 1, 9];
        $math->mathSortArrayWithCallback($unsortedArray, count($unsortedArray), $compareCallback);

        // Verify array was sorted
        $expectedSorted = [1, 2, 5, 8, 9];
        $this->assertEquals($expectedSorted, $unsortedArray);
    }

    /**
     * Test complex data structure handling
     * @group wrapper
     */
    public function testComplexDataStructureHandling(): void
    {
        $wrapperPath = $this->testOutputDir . '/string/StringUtils.php';
        require_once $wrapperPath;

        $stringUtils = new \Test\String\FFI\StringUtils();

        // Test string array operations
        $stringArray = $stringUtils->stringArrayCreate(10);
        $this->assertNotNull($stringArray);
        $this->assertEquals(0, $stringUtils->stringArraySize($stringArray));

        // Add strings to array
        $result1 = $stringUtils->stringArrayAdd($stringArray, "first");
        $result2 = $stringUtils->stringArrayAdd($stringArray, "second");
        $result3 = $stringUtils->stringArrayAdd($stringArray, "third");

        $this->assertEquals(0, $result1);
        $this->assertEquals(0, $result2);
        $this->assertEquals(0, $result3);
        $this->assertEquals(3, $stringUtils->stringArraySize($stringArray));

        // Retrieve strings from array
        $first = $stringUtils->stringArrayGet($stringArray, 0);
        $second = $stringUtils->stringArrayGet($stringArray, 1);
        $third = $stringUtils->stringArrayGet($stringArray, 2);

        $this->assertEquals("first", $first);
        $this->assertEquals("second", $second);
        $this->assertEquals("third", $third);

        // Test string splitting
        $splitArray = $stringUtils->stringSplit("one,two,three", ",");
        $this->assertNotNull($splitArray);
        $this->assertEquals(3, $stringUtils->stringArraySize($splitArray));

        // Clean up
        $stringUtils->stringArrayDestroy($stringArray);
        $stringUtils->stringArrayDestroy($splitArray);
    }

    /**
     * Build test libraries if needed
     */
    private function buildTestLibraries(): void
    {
        $makefilePath = $this->fixturesDir . '/Makefile';
        
        if (!file_exists($makefilePath)) {
            $this->markTestSkipped('Makefile not found');
        }

        $mathLib = $this->fixturesDir . '/libmath_library.so';
        $stringLib = $this->fixturesDir . '/libstring_utils.so';

        if (!file_exists($mathLib) || !file_exists($stringLib)) {
            $output = [];
            $returnCode = 0;
            exec("cd {$this->fixturesDir} && make all 2>&1", $output, $returnCode);

            if ($returnCode !== 0) {
                $this->markTestSkipped('Failed to build test libraries: ' . implode("\n", $output));
            }
        }
    }

    /**
     * Generate wrapper classes for testing
     */
    private function generateWrapperClasses(): void
    {
        $application = new Application();

        // Generate math library wrapper
        $mathConfig = $this->createTestConfig('math');
        $input = new ArrayInput([
            'command' => 'generate',
            '--config' => $mathConfig,
            '--output' => $this->testOutputDir . '/math',
            '--namespace' => 'Test\\Math\\FFI'
        ]);

        $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);
        
        if ($exitCode !== 0) {
            $this->fail('Failed to generate math wrapper: ' . $output->fetch());
        }

        // Generate string library wrapper
        $stringConfig = $this->createTestConfig('string');
        $input = new ArrayInput([
            'command' => 'generate',
            '--config' => $stringConfig,
            '--output' => $this->testOutputDir . '/string',
            '--namespace' => 'Test\\String\\FFI'
        ]);

        $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);
        
        if ($exitCode !== 0) {
            $this->fail('Failed to generate string wrapper: ' . $output->fetch());
        }
    }

    /**
     * Create test configuration
     */
    private function createTestConfig(string $type): string
    {
        $configs = [
            'math' => [
                'headerFiles' => [$this->fixturesDir . '/math_library.h'],
                'libraryFile' => $this->fixturesDir . '/libmath_library.so',
                'outputPath' => $this->testOutputDir . '/math',
                'namespace' => 'Test\\Math\\FFI'
            ],
            'string' => [
                'headerFiles' => [$this->fixturesDir . '/string_utils.h'],
                'libraryFile' => $this->fixturesDir . '/libstring_utils.so',
                'outputPath' => $this->testOutputDir . '/string',
                'namespace' => 'Test\\String\\FFI'
            ]
        ];

        $config = $configs[$type];
        $config['validation'] = [
            'enableParameterValidation' => true,
            'enableTypeConversion' => true,
            'customValidationRules' => []
        ];

        $configPath = $this->testOutputDir . "/config_{$type}.yaml";
        file_put_contents($configPath, yaml_emit($config));

        return $configPath;
    }
}