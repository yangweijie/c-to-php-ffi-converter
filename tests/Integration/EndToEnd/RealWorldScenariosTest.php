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
 * Tests real-world scenarios and edge cases
 */
class RealWorldScenariosTest extends TestCase
{
    private string $testOutputDir;
    private string $fixturesDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->testOutputDir = __DIR__ . '/../../output/real_world';
        $this->fixturesDir = __DIR__ . '/../../Fixtures';
        $this->filesystem = new Filesystem();

        // Clean and create output directory
        if ($this->filesystem->exists($this->testOutputDir)) {
            $this->filesystem->remove($this->testOutputDir);
        }
        $this->filesystem->mkdir($this->testOutputDir);
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testOutputDir)) {
            $this->filesystem->remove($this->testOutputDir);
        }
    }

    /**
     * Test handling of complex C header with dependencies
     * @group real-world
     */
    public function testComplexHeaderWithDependencies(): void
    {
        $application = new Application();

        $input = new ArrayInput([
            'command' => 'generate',
            'header-files' => [
                $this->fixturesDir . '/complex.h',
                $this->fixturesDir . '/dependency1.h',
                $this->fixturesDir . '/dependency2.h'
            ],
            '--output' => $this->testOutputDir . '/complex',
            '--namespace' => 'RealWorld\\Complex',
            '--resolve-dependencies' => true
        ]);

        $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);

        $this->assertEquals(0, $exitCode, 'Complex header processing should succeed');
        
        // Verify all dependencies are resolved
        $outputContent = $output->fetch();
        $this->assertStringContainsString('Resolving dependencies', $outputContent);
        $this->assertStringContainsString('dependency1.h', $outputContent);
        $this->assertStringContainsString('dependency2.h', $outputContent);

        // Verify generated files
        $this->assertFileExists($this->testOutputDir . '/complex/Complex.php');
        
        $generatedContent = file_get_contents($this->testOutputDir . '/complex/Complex.php');
        $this->assertStringContainsString('DataRecord', $generatedContent);
        $this->assertStringContainsString('DataCollection', $generatedContent);
        $this->assertStringContainsString('ValueUnion', $generatedContent);
    }

    /**
     * Test error recovery with malformed headers
     * @group real-world
     */
    public function testErrorRecoveryWithMalformedHeaders(): void
    {
        $application = new Application();

        $input = new ArrayInput([
            'command' => 'generate',
            'header-files' => [
                $this->fixturesDir . '/sample.h',      // Valid header
                $this->fixturesDir . '/malformed.h',   // Invalid header
                $this->fixturesDir . '/complex.h'      // Another valid header
            ],
            '--output' => $this->testOutputDir . '/error_recovery',
            '--namespace' => 'RealWorld\\ErrorRecovery',
            '--continue-on-error' => true
        ]);

        $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);

        // Should succeed partially (process valid headers despite malformed one)
        $this->assertEquals(0, $exitCode, 'Should continue processing valid headers');
        
        $outputContent = $output->fetch();
        $this->assertStringContainsString('Warning', $outputContent);
        $this->assertStringContainsString('malformed.h', $outputContent);
        
        // Verify valid headers were processed
        $this->assertFileExists($this->testOutputDir . '/error_recovery/Sample.php');
        $this->assertFileExists($this->testOutputDir . '/error_recovery/Complex.php');
    }

    /**
     * Test large project with many functions and structures
     * @group real-world
     * @group performance
     */
    public function testLargeProjectProcessing(): void
    {
        // Create a large synthetic header file
        $largeHeaderPath = $this->createLargeHeaderFile();
        
        $startTime = microtime(true);
        
        $application = new Application();
        $input = new ArrayInput([
            'command' => 'generate',
            'header-files' => [$largeHeaderPath],
            '--output' => $this->testOutputDir . '/large_project',
            '--namespace' => 'RealWorld\\LargeProject',
            '--optimize' => true
        ]);

        $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);
        
        $endTime = microtime(true);
        $processingTime = $endTime - $startTime;

        $this->assertEquals(0, $exitCode, 'Large project processing should succeed');
        $this->assertLessThan(60.0, $processingTime, 'Processing should complete within 60 seconds');
        
        // Verify generated wrapper contains all functions
        $generatedContent = file_get_contents($this->testOutputDir . '/large_project/LargeHeader.php');
        $this->assertStringContainsString('function func_0', $generatedContent);
        $this->assertStringContainsString('function func_99', $generatedContent);
        
        $outputContent = $output->fetch();
        $this->assertStringContainsString('100 functions processed', $outputContent);
    }

    /**
     * Test cross-platform compatibility
     * @group real-world
     */
    public function testCrossPlatformCompatibility(): void
    {
        $application = new Application();

        // Test with platform-specific library extensions
        $libraryExtensions = ['so', 'dylib', 'dll'];
        
        foreach ($libraryExtensions as $ext) {
            $input = new ArrayInput([
                'command' => 'generate',
                'header-files' => [$this->fixturesDir . '/sample.h'],
                '--output' => $this->testOutputDir . "/platform_{$ext}",
                '--namespace' => "RealWorld\\Platform\\{$ext}",
                '--library' => "/path/to/library.{$ext}",
                '--target-platform' => $this->getPlatformForExtension($ext)
            ]);

            $output = new BufferedOutput();
            $exitCode = $application->run($input, $output);

            $this->assertEquals(0, $exitCode, "Should handle {$ext} libraries");
            
            $generatedContent = file_get_contents($this->testOutputDir . "/platform_{$ext}/Sample.php");
            $this->assertStringContainsString("library.{$ext}", $generatedContent);
        }
    }

    /**
     * Test memory-intensive operations
     * @group real-world
     * @group memory
     */
    public function testMemoryIntensiveOperations(): void
    {
        $initialMemory = memory_get_usage(true);
        
        // Create multiple large header files
        $headerFiles = [];
        for ($i = 0; $i < 5; $i++) {
            $headerFiles[] = $this->createLargeHeaderFile("large_header_{$i}.h");
        }
        
        $application = new Application();
        $input = new ArrayInput([
            'command' => 'generate',
            'header-files' => $headerFiles,
            '--output' => $this->testOutputDir . '/memory_test',
            '--namespace' => 'RealWorld\\MemoryTest',
            '--memory-limit' => '256M'
        ]);

        $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;

        $this->assertEquals(0, $exitCode, 'Memory-intensive processing should succeed');
        $this->assertLessThan(200 * 1024 * 1024, $memoryIncrease, 'Memory usage should be reasonable'); // Less than 200MB increase
        
        // Verify all files were generated
        for ($i = 0; $i < 5; $i++) {
            $this->assertFileExists($this->testOutputDir . "/memory_test/LargeHeader{$i}.php");
        }
    }

    /**
     * Test concurrent processing simulation
     * @group real-world
     */
    public function testConcurrentProcessingSimulation(): void
    {
        // Simulate concurrent processing by running multiple generation processes
        $processes = [];
        
        for ($i = 0; $i < 3; $i++) {
            $process = new Process([
                'php', __DIR__ . '/../../../bin/c-to-php-ffi', 'generate',
                $this->fixturesDir . '/sample.h',
                '--output', $this->testOutputDir . "/concurrent_{$i}",
                '--namespace', "RealWorld\\Concurrent\\Test{$i}"
            ]);
            
            $processes[] = $process;
            $process->start();
        }
        
        // Wait for all processes to complete
        foreach ($processes as $i => $process) {
            $process->wait();
            $this->assertEquals(0, $process->getExitCode(), "Concurrent process {$i} should succeed");
            $this->assertFileExists($this->testOutputDir . "/concurrent_{$i}/Sample.php");
        }
    }

    /**
     * Test integration with existing PHP projects
     * @group real-world
     */
    public function testIntegrationWithExistingPHPProjects(): void
    {
        // Create a mock existing PHP project structure
        $projectDir = $this->testOutputDir . '/existing_project';
        $this->filesystem->mkdir($projectDir . '/src');
        $this->filesystem->mkdir($projectDir . '/vendor');
        
        // Create existing composer.json
        $composerJson = [
            'name' => 'test/existing-project',
            'autoload' => [
                'psr-4' => [
                    'ExistingProject\\' => 'src/'
                ]
            ],
            'require' => [
                'php' => '^8.0'
            ]
        ];
        file_put_contents($projectDir . '/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT));
        
        // Generate FFI wrapper into existing project
        $application = new Application();
        $input = new ArrayInput([
            'command' => 'generate',
            'header-files' => [$this->fixturesDir . '/sample.h'],
            '--output' => $projectDir . '/src/FFI',
            '--namespace' => 'ExistingProject\\FFI',
            '--update-composer' => true
        ]);

        $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);

        $this->assertEquals(0, $exitCode, 'Integration with existing project should succeed');
        
        // Verify files are in correct location
        $this->assertFileExists($projectDir . '/src/FFI/Sample.php');
        
        // Verify composer.json was updated
        $updatedComposer = json_decode(file_get_contents($projectDir . '/composer.json'), true);
        $this->assertArrayHasKey('ext-ffi', $updatedComposer['require']);
        
        $generatedContent = file_get_contents($projectDir . '/src/FFI/Sample.php');
        $this->assertStringContainsString('namespace ExistingProject\\FFI;', $generatedContent);
    }

    /**
     * Test handling of edge cases in C syntax
     * @group real-world
     */
    public function testEdgeCasesInCSyntax(): void
    {
        // Create header with edge cases
        $edgeCaseHeader = $this->testOutputDir . '/edge_cases.h';
        file_put_contents($edgeCaseHeader, '
#ifndef EDGE_CASES_H
#define EDGE_CASES_H

// Function pointers
typedef int (*callback_t)(int, char*);
typedef struct {
    callback_t callback;
} CallbackStruct;

// Variadic functions
int printf_like(const char* format, ...);

// Bit fields
typedef struct {
    unsigned int flag1 : 1;
    unsigned int flag2 : 1;
    unsigned int value : 30;
} BitFieldStruct;

// Anonymous unions and structs
typedef struct {
    union {
        int intValue;
        float floatValue;
    };
    struct {
        int x, y;
    } point;
} AnonymousStruct;

// Const and volatile qualifiers
const volatile int* get_volatile_ptr(void);

// Array parameters
void process_matrix(int matrix[10][10]);

#endif
');

        $application = new Application();
        $input = new ArrayInput([
            'command' => 'generate',
            'header-files' => [$edgeCaseHeader],
            '--output' => $this->testOutputDir . '/edge_cases',
            '--namespace' => 'RealWorld\\EdgeCases',
            '--handle-edge-cases' => true
        ]);

        $output = new BufferedOutput();
        $exitCode = $application->run($input, $output);

        $this->assertEquals(0, $exitCode, 'Edge case handling should succeed');
        
        $generatedContent = file_get_contents($this->testOutputDir . '/edge_cases/EdgeCases.php');
        
        // Verify edge cases are handled appropriately
        $this->assertStringContainsString('CallbackStruct', $generatedContent);
        $this->assertStringContainsString('BitFieldStruct', $generatedContent);
        $this->assertStringContainsString('AnonymousStruct', $generatedContent);
        
        $outputContent = $output->fetch();
        $this->assertStringContainsString('function pointers detected', $outputContent);
        $this->assertStringContainsString('bit fields detected', $outputContent);
    }

    /**
     * Create a large header file for testing
     */
    private function createLargeHeaderFile(string $filename = 'large_header.h'): string
    {
        $headerPath = $this->testOutputDir . '/' . $filename;
        $content = "#ifndef LARGE_HEADER_H\n#define LARGE_HEADER_H\n\n";
        
        // Generate many function declarations
        for ($i = 0; $i < 100; $i++) {
            $content .= "int func_{$i}(int param1, float param2, char* param3);\n";
        }
        
        // Generate many struct definitions
        for ($i = 0; $i < 50; $i++) {
            $content .= "\ntypedef struct {\n";
            $content .= "    int field1;\n";
            $content .= "    float field2;\n";
            $content .= "    char* field3;\n";
            $content .= "} Struct{$i};\n";
        }
        
        // Generate many constants
        for ($i = 0; $i < 200; $i++) {
            $content .= "#define CONSTANT_{$i} {$i}\n";
        }
        
        $content .= "\n#endif // LARGE_HEADER_H\n";
        
        file_put_contents($headerPath, $content);
        return $headerPath;
    }

    /**
     * Get platform name for library extension
     */
    private function getPlatformForExtension(string $ext): string
    {
        $platforms = [
            'so' => 'linux',
            'dylib' => 'darwin',
            'dll' => 'windows'
        ];
        
        return $platforms[$ext] ?? 'linux';
    }
}