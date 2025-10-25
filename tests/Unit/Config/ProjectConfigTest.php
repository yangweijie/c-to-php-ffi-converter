<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Config\ProjectConfig;
use Yangweijie\CWrapper\Config\ValidationConfig;

class ProjectConfigTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $config = new ProjectConfig();
        
        $this->assertEmpty($config->getHeaderFiles());
        $this->assertEquals('', $config->getLibraryFile());
        $this->assertEquals('./generated', $config->getOutputPath());
        $this->assertEquals('Generated\\FFI', $config->getNamespace());
        $this->assertEmpty($config->getExcludePatterns());
        $this->assertInstanceOf(ValidationConfig::class, $config->getValidationConfig());
    }

    public function testConstructorWithParameters(): void
    {
        $headerFiles = ['test.h', 'another.h'];
        $libraryFile = 'test.so';
        $outputPath = './output';
        $namespace = 'Test\\Namespace';
        $excludePatterns = ['*.tmp'];
        $validationConfig = new ValidationConfig(false, false, []);
        
        $config = new ProjectConfig(
            $headerFiles,
            $libraryFile,
            $outputPath,
            $namespace,
            $excludePatterns,
            $validationConfig
        );
        
        $this->assertEquals($headerFiles, $config->getHeaderFiles());
        $this->assertEquals($libraryFile, $config->getLibraryFile());
        $this->assertEquals($outputPath, $config->getOutputPath());
        $this->assertEquals($namespace, $config->getNamespace());
        $this->assertEquals($excludePatterns, $config->getExcludePatterns());
        $this->assertSame($validationConfig, $config->getValidationConfig());
    }

    public function testSetters(): void
    {
        $config = new ProjectConfig();
        
        $headerFiles = ['new.h'];
        $libraryFile = 'new.so';
        $outputPath = './new-output';
        $namespace = 'New\\Namespace';
        $excludePatterns = ['*.bak'];
        $validationConfig = new ValidationConfig(false, true, ['rule' => 'value']);
        
        $result1 = $config->setHeaderFiles($headerFiles);
        $result2 = $config->setLibraryFile($libraryFile);
        $result3 = $config->setOutputPath($outputPath);
        $result4 = $config->setNamespace($namespace);
        $result5 = $config->setExcludePatterns($excludePatterns);
        $result6 = $config->setValidationConfig($validationConfig);
        
        // Test fluent interface
        $this->assertSame($config, $result1);
        $this->assertSame($config, $result2);
        $this->assertSame($config, $result3);
        $this->assertSame($config, $result4);
        $this->assertSame($config, $result5);
        $this->assertSame($config, $result6);
        
        // Test values
        $this->assertEquals($headerFiles, $config->getHeaderFiles());
        $this->assertEquals($libraryFile, $config->getLibraryFile());
        $this->assertEquals($outputPath, $config->getOutputPath());
        $this->assertEquals($namespace, $config->getNamespace());
        $this->assertEquals($excludePatterns, $config->getExcludePatterns());
        $this->assertSame($validationConfig, $config->getValidationConfig());
    }

    public function testGetValidationRules(): void
    {
        $validationConfig = new ValidationConfig(false, true, ['custom' => 'rule']);
        $config = new ProjectConfig(validation: $validationConfig);
        
        $expected = [
            'enableParameterValidation' => false,
            'enableTypeConversion' => true,
            'customValidationRules' => ['custom' => 'rule'],
        ];
        
        $this->assertEquals($expected, $config->getValidationRules());
    }

    public function testToArray(): void
    {
        $headerFiles = ['test.h'];
        $libraryFile = 'test.so';
        $outputPath = './output';
        $namespace = 'Test\\Namespace';
        $excludePatterns = ['*.tmp'];
        $validationConfig = new ValidationConfig(false, true, ['rule' => 'value']);
        
        $config = new ProjectConfig(
            $headerFiles,
            $libraryFile,
            $outputPath,
            $namespace,
            $excludePatterns,
            $validationConfig
        );
        
        $expected = [
            'headerFiles' => $headerFiles,
            'libraryFile' => $libraryFile,
            'outputPath' => $outputPath,
            'namespace' => $namespace,
            'excludePatterns' => $excludePatterns,
            'validation' => [
                'enableParameterValidation' => false,
                'enableTypeConversion' => true,
                'customValidationRules' => ['rule' => 'value'],
            ],
        ];
        
        $this->assertEquals($expected, $config->toArray());
    }

    public function testFromArray(): void
    {
        $data = [
            'headerFiles' => ['test.h'],
            'libraryFile' => 'test.so',
            'outputPath' => './output',
            'namespace' => 'Test\\Namespace',
            'excludePatterns' => ['*.tmp'],
            'validation' => [
                'enableParameterValidation' => false,
                'enableTypeConversion' => true,
                'customValidationRules' => ['rule' => 'value'],
            ],
        ];
        
        $config = ProjectConfig::fromArray($data);
        
        $this->assertEquals(['test.h'], $config->getHeaderFiles());
        $this->assertEquals('test.so', $config->getLibraryFile());
        $this->assertEquals('./output', $config->getOutputPath());
        $this->assertEquals('Test\\Namespace', $config->getNamespace());
        $this->assertEquals(['*.tmp'], $config->getExcludePatterns());
        
        $validationConfig = $config->getValidationConfig();
        $this->assertFalse($validationConfig->isParameterValidationEnabled());
        $this->assertTrue($validationConfig->isTypeConversionEnabled());
        $this->assertEquals(['rule' => 'value'], $validationConfig->getCustomValidationRules());
    }

    public function testFromArrayWithDefaults(): void
    {
        $config = ProjectConfig::fromArray([]);
        
        $this->assertEmpty($config->getHeaderFiles());
        $this->assertEquals('', $config->getLibraryFile());
        $this->assertEquals('./generated', $config->getOutputPath());
        $this->assertEquals('Generated\\FFI', $config->getNamespace());
        $this->assertEmpty($config->getExcludePatterns());
        
        $validationConfig = $config->getValidationConfig();
        $this->assertTrue($validationConfig->isParameterValidationEnabled());
        $this->assertTrue($validationConfig->isTypeConversionEnabled());
        $this->assertEmpty($validationConfig->getCustomValidationRules());
    }

    public function testFromArrayPartial(): void
    {
        $data = [
            'headerFiles' => ['partial.h'],
            'namespace' => 'Partial\\Namespace',
        ];
        
        $config = ProjectConfig::fromArray($data);
        
        $this->assertEquals(['partial.h'], $config->getHeaderFiles());
        $this->assertEquals('', $config->getLibraryFile());
        $this->assertEquals('./generated', $config->getOutputPath());
        $this->assertEquals('Partial\\Namespace', $config->getNamespace());
        $this->assertEmpty($config->getExcludePatterns());
    }
}