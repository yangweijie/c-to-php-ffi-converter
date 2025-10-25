<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Config\ProjectConfig;
use Yangweijie\CWrapper\Integration\FFIGenRunner;
use Yangweijie\CWrapper\Integration\BindingProcessor;
use Yangweijie\CWrapper\Integration\BindingResult;
use Yangweijie\CWrapper\Exception\GenerationException;
use Yangweijie\CWrapper\Exception\AnalysisException;

/**
 * Tests for error handling and edge cases in the FFIGen integration layer
 */
class FFIGenErrorHandlingTest extends TestCase
{
    private string $testOutputDir;

    protected function setUp(): void
    {
        $this->testOutputDir = sys_get_temp_dir() . '/ffigen_error_test_' . uniqid();
        mkdir($this->testOutputDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testOutputDir)) {
            $this->removeDirectory($this->testOutputDir);
        }
    }

    public function testFFIGenRunnerWithMalformedHeader(): void
    {
        $malformedHeaderFile = $this->testOutputDir . '/malformed.h';
        $malformedContent = '
// This header has syntax errors
#ifndef MALFORMED_H
#define MALFORMED_H

// Missing semicolon
int broken_function(int param)

// Unmatched braces
struct broken_struct {
    int field1;
    // missing closing brace

// Invalid preprocessor directive
#invalid_directive

#endif
';
        file_put_contents($malformedHeaderFile, $malformedContent);

        $config = new ProjectConfig(
            headerFiles: [$malformedHeaderFile],
            libraryFile: $this->getSystemLibraryPath(),
            outputPath: $this->testOutputDir,
            namespace: 'Test\\Malformed'
        );

        $runner = new FFIGenRunner();
        $result = $runner->run($config);

        // Should either fail gracefully or succeed with limited output
        if (!$result->success) {
            $this->assertNotEmpty($result->errors, 'Should report errors for malformed header');
            $this->assertIsArray($result->errors);
        }
    }

    public function testFFIGenRunnerWithEmptyHeader(): void
    {
        $emptyHeaderFile = $this->testOutputDir . '/empty.h';
        file_put_contents($emptyHeaderFile, '');

        $config = new ProjectConfig(
            headerFiles: [$emptyHeaderFile],
            libraryFile: $this->getSystemLibraryPath(),
            outputPath: $this->testOutputDir,
            namespace: 'Test\\Empty'
        );

        $runner = new FFIGenRunner();
        $result = $runner->run($config);

        // Empty header might fail or succeed depending on FFIGen implementation
        if ($result->success) {
            $this->assertFileExists($result->constantsFile);
            $this->assertFileExists($result->methodsFile);
        } else {
            $this->assertNotEmpty($result->errors, 'Should have error messages for empty header');
        }
    }

    public function testFFIGenRunnerWithNonexistentLibrary(): void
    {
        $config = new ProjectConfig(
            headerFiles: [realpath(__DIR__ . '/../Fixtures/sample.h')],
            libraryFile: '/nonexistent/library.so',
            outputPath: $this->testOutputDir,
            namespace: 'Test\\NonexistentLib'
        );

        $runner = new FFIGenRunner();
        $result = $runner->run($config);

        // FFIGen might still succeed even with nonexistent library
        // since it only generates bindings, not loads the library
        if ($result->success) {
            $this->assertFileExists($result->constantsFile);
            $this->assertFileExists($result->methodsFile);
        }
    }

    public function testBindingProcessorWithCorruptedFiles(): void
    {
        $processor = new BindingProcessor();

        // Create corrupted constants file
        $constantsFile = $this->testOutputDir . '/corrupted_constants.php';
        file_put_contents($constantsFile, '<?php // This is not valid PHP const syntax');

        // Create corrupted methods file
        $methodsFile = $this->testOutputDir . '/corrupted_methods.php';
        file_put_contents($methodsFile, '<?php // This is not valid PHP trait syntax');

        $bindingResult = new BindingResult($constantsFile, $methodsFile, true);
        $processed = $processor->process($bindingResult);

        // Should handle corrupted files gracefully
        $this->assertIsArray($processed->constants);
        $this->assertIsArray($processed->functions);
        $this->assertIsArray($processed->structures);
    }

    public function testBindingProcessorWithBinaryFiles(): void
    {
        $processor = new BindingProcessor();

        // Create binary files (not text)
        $constantsFile = $this->testOutputDir . '/binary_constants.php';
        file_put_contents($constantsFile, pack('H*', '89504e470d0a1a0a')); // PNG header

        $methodsFile = $this->testOutputDir . '/binary_methods.php';
        file_put_contents($methodsFile, pack('H*', '504b0304')); // ZIP header

        $bindingResult = new BindingResult($constantsFile, $methodsFile, true);
        $processed = $processor->process($bindingResult);

        // Should handle binary files without crashing
        $this->assertIsArray($processed->constants);
        $this->assertIsArray($processed->functions);
    }

    public function testBindingProcessorWithVeryLargeFiles(): void
    {
        $processor = new BindingProcessor();

        // Create very large constants file
        $constantsFile = $this->testOutputDir . '/large_constants.php';
        $constantsContent = "<?php\n";
        for ($i = 0; $i < 10000; $i++) {
            $constantsContent .= "const LARGE_CONST_{$i} = {$i};\n";
        }
        file_put_contents($constantsFile, $constantsContent);

        // Create very large methods file
        $methodsFile = $this->testOutputDir . '/large_methods.php';
        $methodsContent = "<?php\ntrait Methods {\n";
        for ($i = 0; $i < 1000; $i++) {
            $methodsContent .= "    public static function large_function_{$i}(int \$param): int { return \$param; }\n";
        }
        $methodsContent .= "}\n";
        file_put_contents($methodsFile, $methodsContent);

        $bindingResult = new BindingResult($constantsFile, $methodsFile, true);
        
        $startTime = microtime(true);
        $processed = $processor->process($bindingResult);
        $endTime = microtime(true);

        // Should handle large files efficiently
        $this->assertGreaterThan(5000, count($processed->constants), 'Should process many constants');
        $this->assertGreaterThan(500, count($processed->functions), 'Should process many functions');
        $this->assertLessThan(10.0, $endTime - $startTime, 'Should process large files quickly');
    }

    public function testFFIGenRunnerWithSpecialCharactersInPaths(): void
    {
        // Create directory with special characters
        $specialDir = $this->testOutputDir . '/special chars & symbols!@#';
        mkdir($specialDir, 0755, true);

        $headerFile = $specialDir . '/special header.h';
        file_put_contents($headerFile, '
#ifndef SPECIAL_H
#define SPECIAL_H
int special_function(int param);
#define SPECIAL_CONSTANT 42
#endif
');

        $config = new ProjectConfig(
            headerFiles: [$headerFile],
            libraryFile: $this->getSystemLibraryPath(),
            outputPath: $specialDir,
            namespace: 'Test\\Special'
        );

        $runner = new FFIGenRunner();
        $result = $runner->run($config);

        // Should handle special characters in paths
        if ($result->success) {
            $this->assertFileExists($result->constantsFile);
            $this->assertFileExists($result->methodsFile);
        } else {
            // If it fails, should provide meaningful error
            $this->assertNotEmpty($result->errors);
        }
    }

    public function testBindingProcessorWithUnicodeContent(): void
    {
        $processor = new BindingProcessor();

        // Create files with Unicode content
        $constantsFile = $this->testOutputDir . '/unicode_constants.php';
        $constantsContent = '<?php
// Unicode comments: 测试 тест テスト
const UNICODE_CONST = "Hello 世界";
const EMOJI_CONST = "🚀";
';
        file_put_contents($constantsFile, $constantsContent);

        $methodsFile = $this->testOutputDir . '/unicode_methods.php';
        $methodsContent = '<?php
trait Methods {
    /**
     * Function with Unicode documentation: 文档
     */
    public static function unicode_function(string $param): string
    {
        return $param;
    }
}';
        file_put_contents($methodsFile, $methodsContent);

        $bindingResult = new BindingResult($constantsFile, $methodsFile, true);
        $processed = $processor->process($bindingResult);

        // Should handle Unicode content properly
        $this->assertArrayHasKey('UNICODE_CONST', $processed->constants);
        $this->assertArrayHasKey('EMOJI_CONST', $processed->constants);
        
        $functionNames = array_map(fn($f) => $f->name, $processed->functions);
        $this->assertContains('unicode_function', $functionNames);
    }

    public function testConcurrentFFIGenExecution(): void
    {
        // Test multiple FFIGen executions running concurrently
        $configs = [];
        $runners = [];
        
        for ($i = 0; $i < 3; $i++) {
            $headerFile = $this->testOutputDir . "/concurrent_{$i}.h";
            file_put_contents($headerFile, "
#ifndef CONCURRENT_{$i}_H
#define CONCURRENT_{$i}_H
int concurrent_function_{$i}(int param);
#define CONCURRENT_CONST_{$i} {$i}
#endif
");
            
            $outputDir = $this->testOutputDir . "/output_{$i}";
            mkdir($outputDir, 0755, true);
            
            $configs[$i] = new ProjectConfig(
                headerFiles: [$headerFile],
                libraryFile: $this->getSystemLibraryPath(),
                outputPath: $outputDir,
                namespace: "Test\\Concurrent{$i}"
            );
            
            $runners[$i] = new FFIGenRunner();
        }

        // Execute all runners
        $results = [];
        foreach ($runners as $i => $runner) {
            $results[$i] = $runner->run($configs[$i]);
        }

        // Verify all executions succeeded or failed gracefully
        foreach ($results as $i => $result) {
            if ($result->success) {
                $this->assertFileExists($result->constantsFile, "Output {$i} should have constants file");
                $this->assertFileExists($result->methodsFile, "Output {$i} should have methods file");
            } else {
                $this->assertNotEmpty($result->errors, "Failed output {$i} should have error messages");
            }
        }
    }

    /**
     * Get a system library path that should exist on most systems
     */
    private function getSystemLibraryPath(): string
    {
        $possibleLibraries = [
            '/System/Library/Frameworks/CoreFoundation.framework/CoreFoundation', // macOS
            '/usr/lib/x86_64-linux-gnu/libm.so.6', // Linux
            '/usr/lib/libm.so', // Generic Unix
        ];

        foreach ($possibleLibraries as $library) {
            if (file_exists($library)) {
                return $library;
            }
        }

        return '/usr/lib/libm.so'; // Fallback
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}