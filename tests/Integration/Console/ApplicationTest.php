<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Integration\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;
use Yangweijie\CWrapper\Console\Application;

/**
 * Integration tests for the console application
 */
class ApplicationTest extends TestCase
{
    private Application $application;
    private ApplicationTester $applicationTester;
    private string $testOutputDir;

    protected function setUp(): void
    {
        $this->application = new Application();
        $this->application->setAutoExit(false);
        $this->applicationTester = new ApplicationTester($this->application);
        
        $this->testOutputDir = sys_get_temp_dir() . '/c-wrapper-test-' . uniqid();
    }

    protected function tearDown(): void
    {
        // Clean up test output directory
        if (is_dir($this->testOutputDir)) {
            $this->removeDirectory($this->testOutputDir);
        }
    }

    public function testApplicationDisplaysHelp(): void
    {
        $this->applicationTester->run(['--help']);
        
        $output = $this->applicationTester->getDisplay();
        $this->assertStringContainsString('Generate PHP FFI wrapper classes', $output);
        $this->assertStringContainsString('header-files', $output);
        $this->assertEquals(0, $this->applicationTester->getStatusCode());
    }

    public function testApplicationDisplaysVersion(): void
    {
        $this->applicationTester->run(['--version']);
        
        $output = $this->applicationTester->getDisplay();
        $this->assertStringContainsString('C-to-PHP FFI Converter', $output);
        $this->assertStringContainsString('1.0.0', $output);
        $this->assertEquals(0, $this->applicationTester->getStatusCode());
    }

    public function testGenerateCommandWithValidArguments(): void
    {
        $headerFile = __DIR__ . '/../../Fixtures/sample.h';
        
        $this->applicationTester->run([
            'header-files' => [$headerFile],
            '--output' => $this->testOutputDir,
            '--namespace' => 'Test\\Generated',
        ]);
        
        $output = $this->applicationTester->getDisplay();
        $this->assertStringContainsString('Configuration validated successfully!', $output);
        $this->assertEquals(0, $this->applicationTester->getStatusCode());
    }

    public function testGenerateCommandWithConfigFile(): void
    {
        $configFile = __DIR__ . '/../../Fixtures/config.yaml';
        
        $this->applicationTester->run([
            'header-files' => [],
            '--config' => $configFile,
            '--output' => $this->testOutputDir,
        ]);
        
        $output = $this->applicationTester->getDisplay();
        $this->assertStringContainsString('Configuration validated successfully!', $output);
        $this->assertEquals(0, $this->applicationTester->getStatusCode());
    }

    public function testApplicationHasGenerateCommand(): void
    {
        $commands = $this->application->all();
        $this->assertArrayHasKey('generate', $commands);
        $this->assertInstanceOf(\Yangweijie\CWrapper\Console\Command\GenerateCommand::class, $commands['generate']);
    }

    public function testGenerateCommandFailsWithNonexistentHeaderFile(): void
    {
        $this->applicationTester->run([
            'header-files' => ['/nonexistent/file.h'],
            '--output' => $this->testOutputDir,
        ]);
        
        $output = $this->applicationTester->getDisplay();
        $this->assertStringContainsString('Configuration Error', $output);
        $this->assertStringContainsString('Header file not found', $output);
        $this->assertEquals(1, $this->applicationTester->getStatusCode());
    }

    public function testGenerateCommandFailsWithInvalidNamespace(): void
    {
        $headerFile = __DIR__ . '/../../Fixtures/sample.h';
        
        $this->applicationTester->run([
            'header-files' => [$headerFile],
            '--output' => $this->testOutputDir,
            '--namespace' => '123InvalidNamespace',
        ]);
        
        $output = $this->applicationTester->getDisplay();
        $this->assertStringContainsString('Validation Error', $output);
        $this->assertStringContainsString('Invalid namespace format', $output);
        $this->assertEquals(1, $this->applicationTester->getStatusCode());
    }

    public function testGenerateCommandFailsWithNonexistentConfigFile(): void
    {
        $this->applicationTester->run([
            'header-files' => [],
            '--config' => '/nonexistent/config.yaml',
        ]);
        
        $output = $this->applicationTester->getDisplay();
        $this->assertStringContainsString('Configuration Error', $output);
        $this->assertEquals(1, $this->applicationTester->getStatusCode());
    }

    public function testGenerateCommandFailsWithReadonlyOutputDirectory(): void
    {
        $headerFile = __DIR__ . '/../../Fixtures/sample.h';
        $readonlyDir = '/root'; // Typically not writable by regular users
        
        $this->applicationTester->run([
            'header-files' => [$headerFile],
            '--output' => $readonlyDir,
        ]);
        
        $output = $this->applicationTester->getDisplay();
        $this->assertStringContainsString('Configuration Error', $output);
        $this->assertEquals(1, $this->applicationTester->getStatusCode());
    }

    public function testGenerateCommandWithVerboseOutput(): void
    {
        $headerFile = __DIR__ . '/../../Fixtures/sample.h';
        
        $this->applicationTester->run([
            'header-files' => [$headerFile],
            '--output' => $this->testOutputDir,
            '-v',
        ]);
        
        $output = $this->applicationTester->getDisplay();
        $this->assertStringContainsString('Configuration Summary', $output);
        $this->assertEquals(0, $this->applicationTester->getStatusCode());
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}