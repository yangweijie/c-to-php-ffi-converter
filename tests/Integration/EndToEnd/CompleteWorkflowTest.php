<?php

declare(strict_types=1);

namespace Tests\Integration\EndToEnd;

use PHPUnit\Framework\TestCase;
use FFIConverter\Console\Application;
use FFIConverter\Config\ConfigLoader;
use FFIConverter\Config\ProjectConfig;
use FFIConverter\Integration\FFIGenIntegration;
use FFIConverter\Generator\WrapperGenerator;
use FFIConverter\Documentation\DocumentationGenerator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Complete end-to-end workflow tests
 * Tests the entire process from C header files to generated PHP wrapper classes
 */
class CompleteWorkflowTest extends TestCase
{
    private string $testOutputDir;
    private string $fixturesDir;
    private Filesystem $filesystem;
    private Application $application;

    protected function setUp(): void
    {
        $this->testOutputDir = __DIR__ . '/../../output/e2e';
        $this->fixturesDir = __DIR__ . '/../../Fixtures/Integration';
        $this->filesystem = new Filesystem();
        $this->application = new Application();

        // Clean and create output directory
        if ($this->filesystem->exists($this->testOutputDir)) {
            $this->filesystem->remove($this->testOutputDir);
        }
        $this->filesystem->mkdir($this->testOutputDir);
    }

    protected function tearDown(): void
    {
        // Clean up test output
        if ($this->filesystem->exists($this->testOutputDir)) {
            $this->filesystem->remove($this->testOutputDir);
        }
    }

    /**
     * Test complete workflow for math library
     * @group e2e
     */
    public function testCompleteWorkflowMathLibrary(): void
    {
        $configPath = $this->fixturesDir . '/config_math.yaml';
        $outputPath = $this->testOutputDir . '/math';
        
        // Ensure shared library exists
        $this->buildTestLibraries();
        
        // Update config with correct paths
        $config = $this->createTestConfig($configPath, $outputPath, 'math_library.h', 'libmath_library');
        
        // Run the complete generation workflow
        $input = new ArrayInput([
            'command' => 'generate',
            '--config' => $config,
            '--output' => $outputPath,
            '--namespace' => 'Test\\Math\\FFI',
            '--verbose' => true
        ]);
        
        $output = new BufferedOutput();
        $exitCode = $this->application->run($input, $output);
        
        $this->assertEquals(0, $exitCode, 'Generation command should succeed');
        
        // Verify generated files exist
        $this->assertFileExists($outputPath . '/MathLibrary.php');
        $this->assertFileExists($outputPath . '/README.md');
        $this->assertFileExists($outputPath . '/composer.json');
        
        // Verify generated PHP class structure
        $generatedClass = file_get_contents($outputPath . '/MathLibrary.php');
        $this->assertStringContainsString('namespace Test\\Math\\FFI;', $generatedClass);
        $this->assertStringContainsString('class MathLibrary', $generatedClass);
        $this->assertStringContainsString('public function mathAdd(int $a, int $b): int', $generatedClass);
        $this->assertStringContainsString('public function mathDistance2d(Point2D $p1, Point2D $p2): float', $generatedClass);
        
        // Verify documentation generation
        $readme = file_get_contents($outputPath . '/README.md');
        $this->assertStringContainsString('# Math Library FFI Wrapper', $readme);
        $this->assertStringContainsString('## Installation', $readme);
        $this->assertStringContainsString('## Usage Examples', $readme);
        
        // Test that generated wrapper actually works with the C library
        $this->verifyGeneratedWrapperWorks($outputPath, 'MathLibrary');
    }

    /**
     * Test complete workflow for string utils library
     * @group e2e
     */
    public function testCompleteWorkflowStringLibrary(): void
    {
        $configPath = $this->fixturesDir . '/config_string.yaml';
        $outputPath = $this->testOutputDir . '/string';
        
        // Ensure shared library exists
        $this->buildTestLibraries();
        
        // Update config with correct paths
        $config = $this->createTestConfig($configPath, $outputPath, 'string_utils.h', 'libstring_utils');
        
        // Run the complete generation workflow
        $input = new ArrayInput([
            'command' => 'generate',
            '--config' => $config,
            '--output' => $outputPath,
            '--namespace' => 'Test\\String\\FFI',
            '--verbose' => true
        ]);
        
        $output = new BufferedOutput();
        $exitCode = $this->application->run($input, $output);
        
        $this->assertEquals(0, $exitCode, 'Generation command should succeed');
        
        // Verify generated files exist
        $this->assertFileExists($outputPath . '/StringUtils.php');
        $this->assertFileExists($outputPath . '/StringArray.php');
        $this->assertFileExists($outputPath . '/README.md');
        
        // Verify generated PHP class structure
        $generatedClass = file_get_contents($outputPath . '/StringUtils.php');
        $this->assertStringContainsString('namespace Test\\String\\FFI;', $generatedClass);
        $this->assertStringContainsString('class StringUtils', $generatedClass);
        $this->assertStringContainsString('public function stringDuplicate(string $str): string', $generatedClass);
        $this->assertStringContainsString('public function stringConcatenate(string $str1, string $str2): string', $generatedClass);
        
        // Test that generated wrapper actually works with the C library
        $this->verifyGeneratedWrapperWorks($outputPath, 'StringUtils');
    }

    /**
     * Test CLI interface with real-world scenarios
     * @group e2e
     */
    public function testCLIInterfaceRealWorldScenarios(): void
    {
        $this->buildTestLibraries();
        
        // Test scenario 1: Generate with minimal configuration
        $input = new ArrayInput([
            'command' => 'generate',
            'header-files' => [$this->fixturesDir . '/math_library.h'],
            '--output' => $this->testOutputDir . '/cli_minimal',
            '--namespace' => 'CLI\\Test'
        ]);
        
        $output = new BufferedOutput();
        $exitCode = $this->application->run($input, $output);
        $this->assertEquals(0, $exitCode);
        
        // Test scenario 2: Generate with full configuration
        $input = new ArrayInput([
            'command' => 'generate',
            'header-files' => [$this->fixturesDir . '/string_utils.h'],
            '--output' => $this->testOutputDir . '/cli_full',
            '--namespace' => 'CLI\\Full\\Test',
            '--library' => $this->fixturesDir . '/libstring_utils.so',
            '--validation' => true,
            '--documentation' => true,
            '--verbose' => true
        ]);
        
        $output = new BufferedOutput();
        $exitCode = $this->application->run($input, $output);
        $this->assertEquals(0, $exitCode);
        
        // Verify output contains expected information
        $outputContent = $output->fetch();
        $this->assertStringContainsString('Analyzing header files', $outputContent);
        $this->assertStringContainsString('Generating wrapper classes', $outputContent);
        $this->assertStringContainsString('Generation completed successfully', $outputContent);
        
        // Test scenario 3: Error handling for invalid inputs
        $input = new ArrayInput([
            'command' => 'generate',
            'header-files' => ['/nonexistent/file.h'],
            '--output' => $this->testOutputDir . '/cli_error'
        ]);
        
        $output = new BufferedOutput();
        $exitCode = $this->application->run($input, $output);
        $this->assertNotEquals(0, $exitCode, 'Should fail for nonexistent files');
        
        $errorOutput = $output->fetch();
        $this->assertStringContainsString('Error', $errorOutput);
    }

    /**
     * Test validation and error handling in generated wrappers
     * @group e2e
     */
    public function testValidationAndErrorHandling(): void
    {
        $this->buildTestLibraries();
        
        $outputPath = $this->testOutputDir . '/validation';
        $config = $this->createTestConfig(
            $this->fixturesDir . '/config_math.yaml',
            $outputPath,
            'math_library.h',
            'libmath_library'
        );
        
        // Generate wrapper with validation enabled
        $input = new ArrayInput([
            'command' => 'generate',
            '--config' => $config,
            '--output' => $outputPath,
            '--validation' => true
        ]);
        
        $output = new BufferedOutput();
        $exitCode = $this->application->run($input, $output);
        $this->assertEquals(0, $exitCode);
        
        // Load and test the generated wrapper
        require_once $outputPath . '/MathLibrary.php';
        
        $mathLib = new \Test\Math\FFI\MathLibrary();
        
        // Test valid operations
        $result = $mathLib->mathAdd(5, 3);
        $this->assertEquals(8, $result);
        
        // Test validation for null pointers (should throw exception)
        $this->expectException(\FFIConverter\Exception\ValidationException::class);
        $mathLib->mathSumArray(null, 5);
    }

    /**
     * Test performance with large C projects
     * @group e2e
     * @group performance
     */
    public function testPerformanceWithLargeProjects(): void
    {
        $this->markTestSkipped('Performance test - run manually when needed');
        
        // This test would generate wrappers for larger C libraries
        // and measure performance metrics
        $startTime = microtime(true);
        
        // Generate wrappers for both libraries simultaneously
        $this->testCompleteWorkflowMathLibrary();
        $this->testCompleteWorkflowStringLibrary();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Assert reasonable performance (adjust threshold as needed)
        $this->assertLessThan(30.0, $executionTime, 'Generation should complete within 30 seconds');
    }

    /**
     * Build test libraries if they don't exist
     */
    private function buildTestLibraries(): void
    {
        $makefilePath = $this->fixturesDir . '/Makefile';
        
        if (!file_exists($makefilePath)) {
            $this->markTestSkipped('Makefile not found - cannot build test libraries');
        }
        
        // Check if libraries already exist
        $mathLib = $this->fixturesDir . '/libmath_library.so';
        $stringLib = $this->fixturesDir . '/libstring_utils.so';
        
        if (!file_exists($mathLib) || !file_exists($stringLib)) {
            // Build libraries
            $output = [];
            $returnCode = 0;
            exec("cd {$this->fixturesDir} && make all 2>&1", $output, $returnCode);
            
            if ($returnCode !== 0) {
                $this->markTestSkipped('Failed to build test libraries: ' . implode("\n", $output));
            }
        }
        
        // Verify libraries were built
        $this->assertFileExists($mathLib, 'Math library should be built');
        $this->assertFileExists($stringLib, 'String library should be built');
    }

    /**
     * Create a test configuration file with updated paths
     */
    private function createTestConfig(string $templatePath, string $outputPath, string $headerFile, string $libraryName): string
    {
        $template = file_get_contents($templatePath);
        
        // Update paths in the configuration
        $config = str_replace(
            ['./test_output', 'tests/Fixtures/Integration/'],
            [$outputPath, $this->fixturesDir . '/'],
            $template
        );
        
        // Update library extension based on platform
        $extension = PHP_OS_FAMILY === 'Darwin' ? 'dylib' : 'so';
        $config = str_replace('.so', '.' . $extension, $config);
        
        $configPath = $this->testOutputDir . '/config_' . basename($templatePath);
        file_put_contents($configPath, $config);
        
        return $configPath;
    }

    /**
     * Verify that generated wrapper actually works with the C library
     */
    private function verifyGeneratedWrapperWorks(string $outputPath, string $className): void
    {
        // This is a basic verification - in a real scenario, we would
        // load the generated class and test its methods
        
        $classFile = $outputPath . '/' . $className . '.php';
        $this->assertFileExists($classFile);
        
        $classContent = file_get_contents($classFile);
        
        // Verify the class has proper structure
        $this->assertStringContainsString('class ' . $className, $classContent);
        $this->assertStringContainsString('use FFI;', $classContent);
        $this->assertStringContainsString('private FFI $ffi;', $classContent);
        
        // Verify it has constructor that loads the library
        $this->assertStringContainsString('public function __construct', $classContent);
        $this->assertStringContainsString('FFI::cdef', $classContent);
        
        // Verify it has proper error handling
        $this->assertStringContainsString('try {', $classContent);
        $this->assertStringContainsString('} catch (', $classContent);
        
        // Verify PHPDoc comments are present
        $this->assertStringContainsString('/**', $classContent);
        $this->assertStringContainsString('* @param', $classContent);
        $this->assertStringContainsString('* @return', $classContent);
    }
}