<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Integration\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Yangweijie\CWrapper\Console\Command\GenerateCommand;

/**
 * Integration tests for the GenerateCommand
 */
class GenerateCommandTest extends TestCase
{
    private GenerateCommand $command;
    private CommandTester $commandTester;
    private string $testOutputDir;

    protected function setUp(): void
    {
        $this->command = new GenerateCommand();
        $this->commandTester = new CommandTester($this->command);
        
        $this->testOutputDir = sys_get_temp_dir() . '/c-wrapper-test-' . uniqid();
    }

    protected function tearDown(): void
    {
        // Clean up test output directory
        if (is_dir($this->testOutputDir)) {
            $this->removeDirectory($this->testOutputDir);
        }
    }

    public function testCommandHelp(): void
    {
        // Test help without executing the command logic
        $help = $this->command->getHelp();
        $this->assertStringContainsString('This command generates PHP FFI wrapper classes', $help);
        
        // Test that the command has the expected options
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('output'));
        $this->assertTrue($definition->hasOption('namespace'));
        $this->assertTrue($definition->hasOption('library'));
        $this->assertTrue($definition->hasOption('config'));
        $this->assertTrue($definition->hasArgument('header-files'));
    }

    public function testExecuteWithMinimalArguments(): void
    {
        $headerFile = __DIR__ . '/../../Fixtures/sample.h';
        
        $this->commandTester->execute([
            'header-files' => [$headerFile],
            '--output' => $this->testOutputDir,
        ]);
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('C-to-PHP FFI Converter', $output);
        $this->assertStringContainsString('Configuration Summary', $output);
        $this->assertStringContainsString($headerFile, $output);
        $this->assertStringContainsString($this->testOutputDir, $output);
        $this->assertStringContainsString('Generated\\FFI', $output); // Default namespace
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithNamespaceAndValidation(): void
    {
        $headerFile = __DIR__ . '/../../Fixtures/sample.h';
        
        $this->commandTester->execute([
            'header-files' => [$headerFile],
            '--output' => $this->testOutputDir,
            '--namespace' => 'MyProject\\FFI',
            '--validation',
        ]);
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('MyProject\\FFI', $output);
        $this->assertStringContainsString('Parameter Validation', $output);
        $this->assertStringContainsString('Enabled', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithMultipleHeaderFiles(): void
    {
        $headerFile1 = __DIR__ . '/../../Fixtures/sample.h';
        
        // Create a second header file for testing
        $headerFile2 = $this->testOutputDir . '_header2.h';
        mkdir(dirname($headerFile2), 0755, true);
        file_put_contents($headerFile2, "#ifndef TEST_H\n#define TEST_H\nint test_func(void);\n#endif");
        
        $this->commandTester->execute([
            'header-files' => [$headerFile1, $headerFile2],
            '--output' => $this->testOutputDir,
        ]);
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString(basename($headerFile1), $output);
        $this->assertStringContainsString(basename($headerFile2), $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        
        // Clean up
        unlink($headerFile2);
    }

    public function testExecuteWithConfigFile(): void
    {
        $configFile = __DIR__ . '/../../Fixtures/config.yaml';
        
        $this->commandTester->execute([
            '--config' => $configFile,
            '--output' => $this->testOutputDir, // Override config file output
        ]);
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Test\\FFI', $output); // From config file
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteFailsWithMissingHeaderFile(): void
    {
        $this->commandTester->execute([
            'header-files' => ['/path/to/nonexistent.h'],
            '--output' => $this->testOutputDir,
        ]);
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Configuration Error', $output);
        $this->assertStringContainsString('Header file not found', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testExecuteFailsWithInvalidOutputDirectory(): void
    {
        $headerFile = __DIR__ . '/../../Fixtures/sample.h';
        
        $this->commandTester->execute([
            'header-files' => [$headerFile],
            '--output' => '/invalid/readonly/path',
        ]);
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Configuration Error', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testExecuteFailsWithInvalidNamespace(): void
    {
        $headerFile = __DIR__ . '/../../Fixtures/sample.h';
        
        $this->commandTester->execute([
            'header-files' => [$headerFile],
            '--output' => $this->testOutputDir,
            '--namespace' => '123Invalid',
        ]);
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Validation Error', $output);
        $this->assertStringContainsString('Invalid namespace format', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testExecuteFailsWithNoHeaderFiles(): void
    {
        $this->commandTester->execute([
            'header-files' => [],
            '--output' => $this->testOutputDir,
        ]);
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Configuration Error', $output);
        $this->assertStringContainsString('No header files specified', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithVerboseErrorOutput(): void
    {
        $this->commandTester->execute([
            'header-files' => ['/nonexistent.h'],
            '--output' => $this->testOutputDir,
            '-v',
        ]);
        
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Configuration Error', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
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