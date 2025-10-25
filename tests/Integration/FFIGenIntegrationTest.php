<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Config\ProjectConfig;
use Yangweijie\CWrapper\Integration\FFIGenIntegration;
use Yangweijie\CWrapper\Integration\FFIGenRunner;
use Yangweijie\CWrapper\Integration\BindingProcessor;
use Yangweijie\CWrapper\Integration\BindingResult;
use Yangweijie\CWrapper\Integration\ProcessedBindings;

class FFIGenIntegrationTest extends TestCase
{
    private string $testOutputDir;

    protected function setUp(): void
    {
        $this->testOutputDir = sys_get_temp_dir() . '/ffigen_integration_test_' . uniqid();
        mkdir($this->testOutputDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testOutputDir)) {
            $this->removeDirectory($this->testOutputDir);
        }
    }

    public function testGenerateBindingsWithMockedRunner(): void
    {
        // Create mock runner that returns successful result
        $mockRunner = $this->createMock(FFIGenRunner::class);
        $expectedResult = new BindingResult(
            $this->testOutputDir . '/constants.php',
            $this->testOutputDir . '/Methods.php',
            true
        );
        $mockRunner->expects($this->once())
            ->method('run')
            ->willReturn($expectedResult);

        $integration = new FFIGenIntegration($mockRunner);

        $config = new ProjectConfig(
            headerFiles: [__DIR__ . '/../Fixtures/sample.h'],
            libraryFile: '/path/to/library.so',
            outputPath: $this->testOutputDir,
            namespace: 'Test\\FFI'
        );

        $result = $integration->generateBindings($config);

        $this->assertSame($expectedResult, $result);
        $this->assertTrue($result->success);
    }

    public function testProcessBindingsWithMockedProcessor(): void
    {
        // Create sample binding result
        $bindingResult = new BindingResult(
            $this->testOutputDir . '/constants.php',
            $this->testOutputDir . '/Methods.php',
            true
        );

        // Create mock processor
        $mockProcessor = $this->createMock(BindingProcessor::class);
        $expectedProcessed = new ProcessedBindings([], [], []);
        $mockProcessor->expects($this->once())
            ->method('process')
            ->with($bindingResult)
            ->willReturn($expectedProcessed);

        $integration = new FFIGenIntegration(null, $mockProcessor);

        $result = $integration->processBindings($bindingResult);

        $this->assertSame($expectedProcessed, $result);
    }

    public function testEndToEndIntegrationWithMocks(): void
    {
        // Create sample files for processing
        $constantsFile = $this->testOutputDir . '/constants.php';
        $methodsFile = $this->testOutputDir . '/Methods.php';
        
        file_put_contents($constantsFile, '<?php const TEST_CONST = 42;');
        file_put_contents($methodsFile, '<?php trait Methods { public function test(): int {} }');

        // Create mock runner
        $mockRunner = $this->createMock(FFIGenRunner::class);
        $bindingResult = new BindingResult($constantsFile, $methodsFile, true);
        $mockRunner->expects($this->once())
            ->method('run')
            ->willReturn($bindingResult);

        // Use real processor to test actual processing
        $processor = new BindingProcessor();
        $integration = new FFIGenIntegration($mockRunner, $processor);

        $config = new ProjectConfig(
            headerFiles: [__DIR__ . '/../Fixtures/sample.h'],
            libraryFile: '/path/to/library.so',
            outputPath: $this->testOutputDir,
            namespace: 'Test\\FFI'
        );

        // Test generation
        $generatedResult = $integration->generateBindings($config);
        $this->assertTrue($generatedResult->success);

        // Test processing
        $processedResult = $integration->processBindings($generatedResult);
        $this->assertArrayHasKey('TEST_CONST', $processedResult->constants);
        $this->assertEquals(42, $processedResult->constants['TEST_CONST']);
    }

    public function testIntegrationWithFailedGeneration(): void
    {
        // Create mock runner that returns failed result
        $mockRunner = $this->createMock(FFIGenRunner::class);
        $failedResult = new BindingResult('', '', false, ['Generation failed']);
        $mockRunner->expects($this->once())
            ->method('run')
            ->willReturn($failedResult);

        $integration = new FFIGenIntegration($mockRunner);

        $config = new ProjectConfig(
            headerFiles: [__DIR__ . '/../Fixtures/sample.h'],
            libraryFile: '/path/to/library.so',
            outputPath: $this->testOutputDir,
            namespace: 'Test\\FFI'
        );

        $result = $integration->generateBindings($config);

        $this->assertFalse($result->success);
        $this->assertContains('Generation failed', $result->errors);
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