<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Config\ProjectConfig;
use Yangweijie\CWrapper\Config\ValidationConfig;
use Yangweijie\CWrapper\Integration\FFIGenConfigurationBuilder;
use Yangweijie\CWrapper\Exception\ConfigurationException;

class FFIGenConfigurationBuilderTest extends TestCase
{
    private FFIGenConfigurationBuilder $builder;
    private string $testOutputDir;

    protected function setUp(): void
    {
        $this->builder = new FFIGenConfigurationBuilder();
        $this->testOutputDir = sys_get_temp_dir() . '/ffigen_test_' . uniqid();
        mkdir($this->testOutputDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testOutputDir)) {
            $this->removeDirectory($this->testOutputDir);
        }
    }

    public function testBuildConfigurationWithValidConfig(): void
    {
        $config = new ProjectConfig(
            headerFiles: [__DIR__ . '/../Fixtures/sample.h'],
            libraryFile: '/path/to/library.so',
            outputPath: $this->testOutputDir,
            namespace: 'Test\\FFI'
        );

        $result = $this->builder->buildConfiguration($config);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('headerFiles', $result);
        $this->assertArrayHasKey('libraryFile', $result);
        $this->assertArrayHasKey('outputPath', $result);
        $this->assertArrayHasKey('namespace', $result);

        // Check that the header file path is included (may be resolved to realpath)
        $expectedPath = __DIR__ . '/../Fixtures/sample.h';
        $this->assertTrue(
            in_array($expectedPath, $result['headerFiles']) || 
            in_array(realpath($expectedPath), $result['headerFiles']),
            'Header file path not found in configuration'
        );
        $this->assertEquals('/path/to/library.so', $result['libraryFile']);
        $this->assertEquals($this->testOutputDir, $result['outputPath']);
        $this->assertEquals('Test\\FFI', $result['namespace']);
    }

    public function testBuildConfigurationWithExcludePatterns(): void
    {
        $config = new ProjectConfig(
            headerFiles: [__DIR__ . '/../Fixtures/sample.h'],
            libraryFile: '/path/to/library.so',
            outputPath: $this->testOutputDir,
            namespace: 'Test\\FFI',
            excludePatterns: ['*_internal.h', 'test_*.h']
        );

        $result = $this->builder->buildConfiguration($config);

        $this->assertArrayHasKey('excludeConstants', $result);
        $this->assertArrayHasKey('excludeMethods', $result);
        $this->assertContains('*_internal.h', $result['excludeConstants']);
        $this->assertContains('test_*.h', $result['excludeConstants']);
        $this->assertContains('*_internal.h', $result['excludeMethods']);
        $this->assertContains('test_*.h', $result['excludeMethods']);
    }

    public function testBuildYamlConfiguration(): void
    {
        $config = new ProjectConfig(
            headerFiles: [__DIR__ . '/../Fixtures/sample.h'],
            libraryFile: '/path/to/library.so',
            outputPath: $this->testOutputDir,
            namespace: 'Test\\FFI'
        );

        $yaml = $this->builder->buildYamlConfiguration($config);

        $this->assertIsString($yaml);
        $this->assertStringContainsString('headerFiles:', $yaml);
        $this->assertStringContainsString('libraryFile:', $yaml);
        $this->assertStringContainsString('outputPath:', $yaml);
        $this->assertStringContainsString('namespace:', $yaml);
        $this->assertStringContainsString('sample.h', $yaml);
        $this->assertStringContainsString('/path/to/library.so', $yaml);
    }

    public function testValidateConfigurationThrowsExceptionForEmptyHeaders(): void
    {
        $config = new ProjectConfig(
            headerFiles: [],
            libraryFile: '/path/to/library.so',
            outputPath: $this->testOutputDir,
            namespace: 'Test\\FFI'
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('At least one header file must be specified');

        $this->builder->buildConfiguration($config);
    }

    public function testValidateConfigurationThrowsExceptionForEmptyLibraryFile(): void
    {
        $config = new ProjectConfig(
            headerFiles: [__DIR__ . '/../Fixtures/sample.h'],
            libraryFile: '',
            outputPath: $this->testOutputDir,
            namespace: 'Test\\FFI'
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Library file must be specified');

        $this->builder->buildConfiguration($config);
    }

    public function testValidateConfigurationThrowsExceptionForEmptyOutputPath(): void
    {
        $config = new ProjectConfig(
            headerFiles: [__DIR__ . '/../Fixtures/sample.h'],
            libraryFile: '/path/to/library.so',
            outputPath: '',
            namespace: 'Test\\FFI'
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Output path must be specified');

        $this->builder->buildConfiguration($config);
    }

    public function testValidateConfigurationThrowsExceptionForEmptyNamespace(): void
    {
        $config = new ProjectConfig(
            headerFiles: [__DIR__ . '/../Fixtures/sample.h'],
            libraryFile: '/path/to/library.so',
            outputPath: $this->testOutputDir,
            namespace: ''
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Namespace must be specified');

        $this->builder->buildConfiguration($config);
    }

    public function testWriteTemporaryConfigFile(): void
    {
        $config = new ProjectConfig(
            headerFiles: [__DIR__ . '/../Fixtures/sample.h'],
            libraryFile: '/path/to/library.so',
            outputPath: $this->testOutputDir,
            namespace: 'Test\\FFI'
        );

        $tempFile = $this->builder->writeTemporaryConfigFile($config);

        $this->assertFileExists($tempFile);
        $this->assertStringEndsWith('.yml', $tempFile);
        
        $content = file_get_contents($tempFile);
        $this->assertStringContainsString('headerFiles:', $content);
        $this->assertStringContainsString('libraryFile:', $content);
        $this->assertStringContainsString('outputPath:', $content);
        $this->assertStringContainsString('namespace:', $content);
        
        // Clean up
        unlink($tempFile);
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