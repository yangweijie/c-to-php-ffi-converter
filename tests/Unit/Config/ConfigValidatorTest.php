<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Config\ConfigValidator;
use Yangweijie\CWrapper\Config\ProjectConfig;
use Yangweijie\CWrapper\Config\ValidationConfig;
use Yangweijie\CWrapper\Exception\ConfigurationException;

class ConfigValidatorTest extends TestCase
{
    private ConfigValidator $validator;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->validator = new ConfigValidator();
        $this->tempDir = sys_get_temp_dir() . '/config_validator_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testValidateValidConfiguration(): void
    {
        // Create a temporary header file
        $headerFile = $this->tempDir . '/test.h';
        file_put_contents($headerFile, '#ifndef TEST_H\n#define TEST_H\nvoid test_function();\n#endif');
        
        $config = new ProjectConfig(
            headerFiles: [$headerFile],
            libraryFile: '',
            outputPath: $this->tempDir . '/output',
            namespace: 'Test\\MyNamespace'
        );
        
        // Should not throw exception
        $this->validator->validate($config);
        $this->assertTrue(true); // If we get here, validation passed
    }

    public function testValidateEmptyHeaderFiles(): void
    {
        $config = new ProjectConfig(
            headerFiles: [],
            outputPath: $this->tempDir . '/output',
            namespace: 'Test\\Namespace'
        );
        
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('At least one header file must be specified');
        
        $this->validator->validate($config);
    }

    public function testValidateEmptyNamespace(): void
    {
        $headerFile = $this->tempDir . '/test.h';
        file_put_contents($headerFile, '#ifndef TEST_H\n#define TEST_H\nvoid test_function();\n#endif');
        
        $config = new ProjectConfig(
            headerFiles: [$headerFile],
            namespace: ''
        );
        
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Namespace cannot be empty');
        
        $this->validator->validate($config);
    }

    public function testValidateEmptyOutputPath(): void
    {
        $headerFile = $this->tempDir . '/test.h';
        file_put_contents($headerFile, '#ifndef TEST_H\n#define TEST_H\nvoid test_function();\n#endif');
        
        $config = new ProjectConfig(
            headerFiles: [$headerFile],
            outputPath: '',
            namespace: 'Test\\MyNamespace'
        );
        
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Output path cannot be empty');
        
        $this->validator->validate($config);
    }

    public function testValidateNonExistentHeaderFile(): void
    {
        $config = new ProjectConfig(
            headerFiles: ['/non/existent/file.h'],
            outputPath: $this->tempDir . '/output',
            namespace: 'Test\\MyNamespace'
        );
        
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Header file not found: /non/existent/file.h');
        
        $this->validator->validate($config);
    }

    public function testValidateInvalidHeaderFileExtension(): void
    {
        $invalidFile = $this->tempDir . '/test.txt';
        file_put_contents($invalidFile, 'not a header file');
        
        $config = new ProjectConfig(
            headerFiles: [$invalidFile],
            outputPath: $this->tempDir . '/output',
            namespace: 'Test\\MyNamespace'
        );
        
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid header file extension');
        
        $this->validator->validate($config);
    }

    public function testValidateValidHeaderFileExtensions(): void
    {
        $extensions = ['h', 'hpp', 'hxx', 'hh'];
        
        foreach ($extensions as $ext) {
            $headerFile = $this->tempDir . "/test.{$ext}";
            file_put_contents($headerFile, '#ifndef TEST_H\n#define TEST_H\nvoid test_function();\n#endif');
            
            $config = new ProjectConfig(
                headerFiles: [$headerFile],
                outputPath: $this->tempDir . '/output',
                namespace: 'Test\\MyNamespace'
            );
            
            // Should not throw exception
            $this->validator->validate($config);
        }
        
        $this->assertTrue(true); // If we get here, all validations passed
    }

    public function testValidateNonExistentLibraryFile(): void
    {
        $headerFile = $this->tempDir . '/test.h';
        file_put_contents($headerFile, '#ifndef TEST_H\n#define TEST_H\nvoid test_function();\n#endif');
        
        $config = new ProjectConfig(
            headerFiles: [$headerFile],
            libraryFile: '/non/existent/library.so',
            outputPath: $this->tempDir . '/output',
            namespace: 'Test\\MyNamespace'
        );
        
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Library file not found: /non/existent/library.so');
        
        $this->validator->validate($config);
    }

    public function testValidateInvalidNamespaceFormat(): void
    {
        $headerFile = $this->tempDir . '/test.h';
        file_put_contents($headerFile, '#ifndef TEST_H\n#define TEST_H\nvoid test_function();\n#endif');
        
        $config = new ProjectConfig(
            headerFiles: [$headerFile],
            outputPath: $this->tempDir . '/output',
            namespace: '123InvalidNamespace'
        );
        
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid namespace format');
        
        $this->validator->validate($config);
    }

    public function testValidateNamespaceWithReservedKeyword(): void
    {
        $headerFile = $this->tempDir . '/test.h';
        file_put_contents($headerFile, '#ifndef TEST_H\n#define TEST_H\nvoid test_function();\n#endif');
        
        $config = new ProjectConfig(
            headerFiles: [$headerFile],
            outputPath: $this->tempDir . '/output',
            namespace: 'Class\\Namespace'
        );
        
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Namespace contains reserved keyword: Class');
        
        $this->validator->validate($config);
    }

    public function testValidateSchemaValidData(): void
    {
        $data = [
            'headerFiles' => ['test.h'],
            'libraryFile' => 'test.so',
            'outputPath' => './output',
            'namespace' => 'Test\\Namespace',
            'excludePatterns' => ['*.tmp'],
            'validation' => [
                'enableParameterValidation' => true,
                'enableTypeConversion' => false,
                'customValidationRules' => ['rule' => 'value'],
            ],
        ];
        
        // Should not throw exception
        $this->validator->validateSchema($data);
        $this->assertTrue(true);
    }

    public function testValidateSchemaUnknownKey(): void
    {
        $data = [
            'headerFiles' => ['test.h'],
            'unknownKey' => 'value',
        ];
        
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Unknown configuration key: unknownKey');
        
        $this->validator->validateSchema($data);
    }

    public function testValidateSchemaInvalidType(): void
    {
        $data = [
            'headerFiles' => 'should_be_array',
        ];
        
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('headerFiles must be an array');
        
        $this->validator->validateSchema($data);
    }

    public function testValidateSchemaInvalidValidationType(): void
    {
        $data = [
            'validation' => [
                'enableParameterValidation' => 'should_be_boolean',
            ],
        ];
        
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('enableParameterValidation must be a boolean');
        
        $this->validator->validateSchema($data);
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