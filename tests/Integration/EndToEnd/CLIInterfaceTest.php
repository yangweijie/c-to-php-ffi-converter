<?php

declare(strict_types=1);

namespace Tests\Integration\EndToEnd;

use PHPUnit\Framework\TestCase;
use FFIConverter\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Tests CLI interface with real-world scenarios
 */
class CLIInterfaceTest extends TestCase
{
    private string $testOutputDir;
    private string $fixturesDir;
    private Filesystem $filesystem;
    private string $binPath;

    protected function setUp(): void
    {
        $this->testOutputDir = __DIR__ . '/../../output/cli_test';
        $this->fixturesDir = __DIR__ . '/../../Fixtures/Integration';
        $this->binPath = __DIR__ . '/../../../bin/c-to-php-ffi';
        $this->filesystem = new Filesystem();

        // Clean and create output directory
        if ($this->filesystem->exists($this->testOutputDir)) {
            $this->filesystem->remove($this->testOutputDir);
        }
        $this->filesystem->mkdir($this->testOutputDir);

        // Build test libraries
        $this->buildTestLibraries();
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testOutputDir)) {
            $this->filesystem->remove($this->testOutputDir);
        }
    }

    /**
     * Test basic CLI command execution
     * @group cli
     */
    public function testBasicCLICommandExecution(): void
    {
        $process = new Process([
            'php', $this->binPath, 'generate',
            $this->fixturesDir . '/math_library.h',
            '--output', $this->testOutputDir . '/basic',
            '--namespace', 'CLI\\Basic\\Test'
        ]);

        $process->run();

        $this->assertEquals(0, $process->getExitCode(), 'CLI command should succeed: ' . $process->getErrorOutput());
        $this->assertFileExists($this->testOutputDir . '/basic/MathLibrary.php');
        
        $output = $process->getOutput();
        $this->assertStringContainsString('Generation completed', $output);
    }

    /**
     * Test CLI with configuration file
     * @group cli
     */
    public function testCLIWithConfigurationFile(): void
    {
        $configPath = $this->createTestConfigFile();

        $process = new Process([
            'php', $this->binPath, 'generate',
            '--config', $configPath,
            '--verbose'
        ]);

        $process->run();

        $this->assertEquals(0, $process->getExitCode(), 'CLI with config should succeed: ' . $process->getErrorOutput());
        
        $output = $process->getOutput();
        $this->assertStringContainsString('Loading configuration', $output);
        $this->assertStringContainsString('Analyzing header files', $output);
        $this->assertStringContainsString('Generating wrapper classes', $output);
    }

    /**
     * Test CLI with library file specification
     * @group cli
     */
    public function testCLIWithLibraryFile(): void
    {
        $libraryPath = $this->fixturesDir . '/libmath_library.so';
        
        $process = new Process([
            'php', $this->binPath, 'generate',
            $this->fixturesDir . '/math_library.h',
            '--library', $libraryPath,
            '--output', $this->testOutputDir . '/with_library',
            '--namespace', 'CLI\\WithLib\\Test'
        ]);

        $process->run();

        $this->assertEquals(0, $process->getExitCode(), 'CLI with library should succeed: ' . $process->getErrorOutput());
        
        // Verify generated wrapper includes library loading
        $wrapperContent = file_get_contents($this->testOutputDir . '/with_library/MathLibrary.php');
        $this->assertStringContainsString($libraryPath, $wrapperContent);
    }

    /**
     * Test CLI validation options
     * @group cli
     */
    public function testCLIValidationOptions(): void
    {
        $process = new Process([
            'php', $this->binPath, 'generate',
            $this->fixturesDir . '/string_utils.h',
            '--output', $this->testOutputDir . '/validation',
            '--namespace', 'CLI\\Validation\\Test',
            '--validation',
            '--strict-types'
        ]);

        $process->run();

        $this->assertEquals(0, $process->getExitCode(), 'CLI with validation should succeed: ' . $process->getErrorOutput());
        
        // Verify generated wrapper includes validation
        $wrapperContent = file_get_contents($this->testOutputDir . '/validation/StringUtils.php');
        $this->assertStringContainsString('declare(strict_types=1)', $wrapperContent);
        $this->assertStringContainsString('ValidationException', $wrapperContent);
    }

    /**
     * Test CLI documentation generation options
     * @group cli
     */
    public function testCLIDocumentationOptions(): void
    {
        $process = new Process([
            'php', $this->binPath, 'generate',
            $this->fixturesDir . '/math_library.h',
            '--output', $this->testOutputDir . '/docs',
            '--namespace', 'CLI\\Docs\\Test',
            '--documentation',
            '--examples'
        ]);

        $process->run();

        $this->assertEquals(0, $process->getExitCode(), 'CLI with docs should succeed: ' . $process->getErrorOutput());
        
        // Verify documentation files are generated
        $this->assertFileExists($this->testOutputDir . '/docs/README.md');
        $this->assertFileExists($this->testOutputDir . '/docs/examples/');
        
        $readme = file_get_contents($this->testOutputDir . '/docs/README.md');
        $this->assertStringContainsString('# Math Library FFI Wrapper', $readme);
        $this->assertStringContainsString('## Usage Examples', $readme);
    }

    /**
     * Test CLI error handling for invalid inputs
     * @group cli
     */
    public function testCLIErrorHandlingInvalidInputs(): void
    {
        // Test with nonexistent header file
        $process = new Process([
            'php', $this->binPath, 'generate',
            '/nonexistent/file.h',
            '--output', $this->testOutputDir . '/error'
        ]);

        $process->run();

        $this->assertNotEquals(0, $process->getExitCode(), 'Should fail for nonexistent file');
        $this->assertStringContainsString('Error', $process->getErrorOutput());
    }

    /**
     * Test CLI error handling for invalid configuration
     * @group cli
     */
    public function testCLIErrorHandlingInvalidConfiguration(): void
    {
        $invalidConfigPath = $this->testOutputDir . '/invalid_config.yaml';
        file_put_contents($invalidConfigPath, "invalid: yaml: content: [");

        $process = new Process([
            'php', $this->binPath, 'generate',
            '--config', $invalidConfigPath
        ]);

        $process->run();

        $this->assertNotEquals(0, $process->getExitCode(), 'Should fail for invalid config');
        $this->assertStringContainsString('Configuration', $process->getErrorOutput());
    }

    /**
     * Test CLI help and version commands
     * @group cli
     */
    public function testCLIHelpAndVersionCommands(): void
    {
        // Test help command
        $process = new Process(['php', $this->binPath, '--help']);
        $process->run();

        $this->assertEquals(0, $process->getExitCode());
        $output = $process->getOutput();
        $this->assertStringContainsString('Usage:', $output);
        $this->assertStringContainsString('generate', $output);

        // Test version command
        $process = new Process(['php', $this->binPath, '--version']);
        $process->run();

        $this->assertEquals(0, $process->getExitCode());
        $output = $process->getOutput();
        $this->assertStringContainsString('C-to-PHP FFI Converter', $output);
    }

    /**
     * Test CLI with multiple header files
     * @group cli
     */
    public function testCLIWithMultipleHeaderFiles(): void
    {
        $process = new Process([
            'php', $this->binPath, 'generate',
            $this->fixturesDir . '/math_library.h',
            $this->fixturesDir . '/string_utils.h',
            '--output', $this->testOutputDir . '/multiple',
            '--namespace', 'CLI\\Multiple\\Test'
        ]);

        $process->run();

        $this->assertEquals(0, $process->getExitCode(), 'CLI with multiple headers should succeed: ' . $process->getErrorOutput());
        
        // Verify both wrappers are generated
        $this->assertFileExists($this->testOutputDir . '/multiple/MathLibrary.php');
        $this->assertFileExists($this->testOutputDir . '/multiple/StringUtils.php');
    }

    /**
     * Test CLI output formatting options
     * @group cli
     */
    public function testCLIOutputFormattingOptions(): void
    {
        // Test verbose output
        $process = new Process([
            'php', $this->binPath, 'generate',
            $this->fixturesDir . '/math_library.h',
            '--output', $this->testOutputDir . '/verbose',
            '--namespace', 'CLI\\Verbose\\Test',
            '--verbose'
        ]);

        $process->run();

        $this->assertEquals(0, $process->getExitCode());
        $output = $process->getOutput();
        $this->assertStringContainsString('Analyzing', $output);
        $this->assertStringContainsString('Generating', $output);
        $this->assertStringContainsString('Writing', $output);

        // Test quiet output
        $process = new Process([
            'php', $this->binPath, 'generate',
            $this->fixturesDir . '/string_utils.h',
            '--output', $this->testOutputDir . '/quiet',
            '--namespace', 'CLI\\Quiet\\Test',
            '--quiet'
        ]);

        $process->run();

        $this->assertEquals(0, $process->getExitCode());
        $output = $process->getOutput();
        $this->assertEmpty(trim($output), 'Quiet mode should produce minimal output');
    }

    /**
     * Test CLI with custom template directory
     * @group cli
     */
    public function testCLIWithCustomTemplateDirectory(): void
    {
        $templateDir = $this->testOutputDir . '/custom_templates';
        $this->filesystem->mkdir($templateDir);
        
        // Create a custom template
        file_put_contents($templateDir . '/class.php.twig', '<?php
// Custom template
namespace {{ namespace }};

class {{ className }} {
    // Custom implementation
}');

        $process = new Process([
            'php', $this->binPath, 'generate',
            $this->fixturesDir . '/math_library.h',
            '--output', $this->testOutputDir . '/custom_template',
            '--namespace', 'CLI\\Custom\\Test',
            '--template-dir', $templateDir
        ]);

        $process->run();

        $this->assertEquals(0, $process->getExitCode(), 'CLI with custom template should succeed: ' . $process->getErrorOutput());
        
        // Verify custom template was used
        $wrapperContent = file_get_contents($this->testOutputDir . '/custom_template/MathLibrary.php');
        $this->assertStringContainsString('// Custom template', $wrapperContent);
        $this->assertStringContainsString('// Custom implementation', $wrapperContent);
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
     * Create a test configuration file
     */
    private function createTestConfigFile(): string
    {
        $config = [
            'headerFiles' => [
                $this->fixturesDir . '/math_library.h'
            ],
            'libraryFile' => $this->fixturesDir . '/libmath_library.so',
            'outputPath' => $this->testOutputDir . '/config_test',
            'namespace' => 'CLI\\Config\\Test',
            'excludePatterns' => [],
            'validation' => [
                'enableParameterValidation' => true,
                'enableTypeConversion' => true,
                'customValidationRules' => []
            ]
        ];

        $configPath = $this->testOutputDir . '/test_config.yaml';
        file_put_contents($configPath, yaml_emit($config));

        return $configPath;
    }
}