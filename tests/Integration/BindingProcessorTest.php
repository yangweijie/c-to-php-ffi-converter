<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Integration\BindingProcessor;
use Yangweijie\CWrapper\Integration\BindingResult;
use Yangweijie\CWrapper\Exception\AnalysisException;

class BindingProcessorTest extends TestCase
{
    private BindingProcessor $processor;
    private string $testOutputDir;

    protected function setUp(): void
    {
        $this->processor = new BindingProcessor();
        $this->testOutputDir = sys_get_temp_dir() . '/binding_test_' . uniqid();
        mkdir($this->testOutputDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testOutputDir)) {
            $this->removeDirectory($this->testOutputDir);
        }
    }

    public function testProcessSuccessfulBindingResult(): void
    {
        // Create sample constants.php file
        $constantsFile = $this->testOutputDir . '/constants.php';
        $constantsContent = '<?php
const MAX_SIZE = 1024;
const PI = 3.14159;
const ENABLED = true;
const MESSAGE = "Hello World";
';
        file_put_contents($constantsFile, $constantsContent);

        // Create sample Methods.php file
        $methodsFile = $this->testOutputDir . '/Methods.php';
        $methodsContent = '<?php
trait Methods {
    /**
     * Add two integers
     * @param int $a First number
     * @param int $b Second number
     * @return int Sum of the numbers
     */
    public function add(int $a, int $b): int
    {
        return $this->ffi->add($a, $b);
    }

    /**
     * Process an array
     */
    public function process_array(?CData $arr, int $length): void
    {
        $this->ffi->process_array($arr, $length);
    }
}';
        file_put_contents($methodsFile, $methodsContent);

        $bindingResult = new BindingResult($constantsFile, $methodsFile, true);
        $processed = $this->processor->process($bindingResult);

        // Test constants
        $this->assertArrayHasKey('MAX_SIZE', $processed->constants);
        $this->assertEquals(1024, $processed->constants['MAX_SIZE']);
        $this->assertArrayHasKey('PI', $processed->constants);
        $this->assertEquals(3.14159, $processed->constants['PI']);
        $this->assertArrayHasKey('ENABLED', $processed->constants);
        $this->assertTrue($processed->constants['ENABLED']);
        $this->assertArrayHasKey('MESSAGE', $processed->constants);
        $this->assertEquals('Hello World', $processed->constants['MESSAGE']);

        // Test functions
        $this->assertCount(2, $processed->functions);
        
        $addFunction = $processed->functions[0];
        $this->assertEquals('add', $addFunction->name);
        $this->assertEquals('int', $addFunction->returnType);
        $this->assertCount(2, $addFunction->parameters);
        $this->assertEquals('a', $addFunction->parameters[0]['name']);
        $this->assertEquals('int', $addFunction->parameters[0]['type']);
        $this->assertEquals('b', $addFunction->parameters[1]['name']);
        $this->assertEquals('int', $addFunction->parameters[1]['type']);

        $processFunction = $processed->functions[1];
        $this->assertEquals('process_array', $processFunction->name);
        $this->assertEquals('void', $processFunction->returnType);
        $this->assertCount(2, $processFunction->parameters);
    }

    public function testProcessFailedBindingResult(): void
    {
        $bindingResult = new BindingResult('', '', false, ['FFIGen execution failed']);

        $this->expectException(AnalysisException::class);
        $this->expectExceptionMessage('Cannot process failed binding result');

        $this->processor->process($bindingResult);
    }

    public function testProcessMissingConstantsFile(): void
    {
        $methodsFile = $this->testOutputDir . '/Methods.php';
        file_put_contents($methodsFile, '<?php trait Methods {}');

        $bindingResult = new BindingResult(
            $this->testOutputDir . '/nonexistent.php',
            $methodsFile,
            true
        );

        $this->expectException(AnalysisException::class);
        $this->expectExceptionMessage('Constants file not found');

        $this->processor->process($bindingResult);
    }

    public function testProcessMissingMethodsFile(): void
    {
        $constantsFile = $this->testOutputDir . '/constants.php';
        file_put_contents($constantsFile, '<?php');

        $bindingResult = new BindingResult(
            $constantsFile,
            $this->testOutputDir . '/nonexistent.php',
            true
        );

        $this->expectException(AnalysisException::class);
        $this->expectExceptionMessage('Methods file not found');

        $this->processor->process($bindingResult);
    }

    public function testProcessEmptyFiles(): void
    {
        $constantsFile = $this->testOutputDir . '/constants.php';
        file_put_contents($constantsFile, '<?php');

        $methodsFile = $this->testOutputDir . '/Methods.php';
        file_put_contents($methodsFile, '<?php trait Methods {}');

        $bindingResult = new BindingResult($constantsFile, $methodsFile, true);
        $processed = $this->processor->process($bindingResult);

        $this->assertEmpty($processed->constants);
        $this->assertEmpty($processed->functions);
        $this->assertEmpty($processed->structures);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}