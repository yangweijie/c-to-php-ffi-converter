<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;
use Yangweijie\CWrapper\Config\ConfigLoader;
use Yangweijie\CWrapper\Config\ConfigValidator;
use Yangweijie\CWrapper\Config\ProjectConfig;
use Yangweijie\CWrapper\Exception\ConfigurationException;
use Mockery;

class ConfigLoaderTest extends TestCase
{
    private ConfigLoader $loader;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/config_loader_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        
        // Create a mock validator that doesn't validate file existence
        $mockValidator = Mockery::mock(ConfigValidator::class);
        $mockValidator->shouldReceive('validateSchema')->andReturn();
        $mockValidator->shouldReceive('validate')->andReturn();
        
        $this->loader = new ConfigLoader($mockValidator);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        Mockery::close();
    }

    public function testLoadFromFileValidYaml(): void
    {
        $configFile = $this->tempDir . '/config.yaml';
        $configData = [
            'headerFiles' => ['test.h', 'another.h'],
            'libraryFile' => 'test.so',
            'outputPath' => './output',
            'namespace' => 'Test\\MyNamespace',
            'excludePatterns' => ['*.tmp'],
            'validation' => [
                'enableParameterValidation' => false,
                'enableTypeConversion' => true,
                'customValidationRules' => ['rule' => 'value'],
            ],
        ];
        
        file_put_contents($configFile, Yaml::dump($configData));
        
        $config = $this->loader->loadFromFile($configFile);
        
        $this->assertEquals(['test.h', 'another.h'], $config->getHeaderFiles());
        $this->assertEquals('test.so', $config->getLibraryFile());
        $this->assertEquals('./output', $config->getOutputPath());
        $this->assertEquals('Test\\MyNamespace', $config->getNamespace());
        $this->assertEquals(['*.tmp'], $config->getExcludePatterns());
        
        $validationConfig = $config->getValidationConfig();
        $this->assertFalse($validationConfig->isParameterValidationEnabled());
        $this->assertTrue($validationConfig->isTypeConversionEnabled());
        $this->assertEquals(['rule' => 'value'], $validationConfig->getCustomValidationRules());
    }

    public function testLoadFromFileNonExistent(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Configuration file not found');
        
        $this->loader->loadFromFile('/non/existent/config.yaml');
    }

    public function testLoadFromFileInvalidYaml(): void
    {
        $configFile = $this->tempDir . '/invalid.yaml';
        file_put_contents($configFile, "invalid: yaml: content: [\n");
        
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Failed to parse configuration file');
        
        $this->loader->loadFromFile($configFile);
    }

    public function testLoadFromFileNonArrayYaml(): void
    {
        $configFile = $this->tempDir . '/non_array.yaml';
        file_put_contents($configFile, "just a string");
        
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid YAML format in configuration file');
        
        $this->loader->loadFromFile($configFile);
    }

    public function testLoadFromInputBasic(): void
    {
        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getArguments')->andReturn(['headers' => ['test.h']]);
        $input->shouldReceive('getOption')->with('headers')->andReturn(null);
        $input->shouldReceive('getOption')->with('library')->andReturn('test.so');
        $input->shouldReceive('getOption')->with('output')->andReturn('./custom-output');
        $input->shouldReceive('getOption')->with('namespace')->andReturn('Custom\\MyNamespace');
        $input->shouldReceive('getOption')->with('exclude')->andReturn(null);
        $input->shouldReceive('getOption')->with('no-validation')->andReturn(false);
        $input->shouldReceive('getOption')->with('no-type-conversion')->andReturn(false);
        
        $config = $this->loader->loadFromInput($input);
        
        $this->assertEquals(['test.h'], $config->getHeaderFiles());
        $this->assertEquals('test.so', $config->getLibraryFile());
        $this->assertEquals('./custom-output', $config->getOutputPath());
        $this->assertEquals('Custom\\MyNamespace', $config->getNamespace());
    }

    public function testLoadFromInputWithDefaults(): void
    {
        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getArguments')->andReturn(['headers' => ['test.h']]);
        $input->shouldReceive('getOption')->with('headers')->andReturn(null);
        $input->shouldReceive('getOption')->with('library')->andReturn(null);
        $input->shouldReceive('getOption')->with('output')->andReturn(null);
        $input->shouldReceive('getOption')->with('namespace')->andReturn(null);
        $input->shouldReceive('getOption')->with('exclude')->andReturn(null);
        $input->shouldReceive('getOption')->with('no-validation')->andReturn(false);
        $input->shouldReceive('getOption')->with('no-type-conversion')->andReturn(false);
        
        $config = $this->loader->loadFromInput($input);
        
        $this->assertEquals(['test.h'], $config->getHeaderFiles());
        $this->assertEquals('', $config->getLibraryFile());
        $this->assertEquals('./generated', $config->getOutputPath());
        $this->assertEquals('Generated\\FFI', $config->getNamespace());
        $this->assertEmpty($config->getExcludePatterns());
    }

    public function testLoadFromInputMultipleHeaders(): void
    {
        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getArguments')->andReturn(['headers' => ['test1.h', 'test2.h']]);
        $input->shouldReceive('getOption')->with('headers')->andReturn('test3.h,test4.h');
        $input->shouldReceive('getOption')->with('library')->andReturn(null);
        $input->shouldReceive('getOption')->with('output')->andReturn(null);
        $input->shouldReceive('getOption')->with('namespace')->andReturn(null);
        $input->shouldReceive('getOption')->with('exclude')->andReturn(null);
        $input->shouldReceive('getOption')->with('no-validation')->andReturn(false);
        $input->shouldReceive('getOption')->with('no-type-conversion')->andReturn(false);
        
        $config = $this->loader->loadFromInput($input);
        
        $this->assertEquals(['test1.h', 'test2.h', 'test3.h', 'test4.h'], $config->getHeaderFiles());
    }

    public function testLoadFromInputExcludePatterns(): void
    {
        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getArguments')->andReturn(['headers' => ['test.h']]);
        $input->shouldReceive('getOption')->with('headers')->andReturn(null);
        $input->shouldReceive('getOption')->with('library')->andReturn(null);
        $input->shouldReceive('getOption')->with('output')->andReturn(null);
        $input->shouldReceive('getOption')->with('namespace')->andReturn(null);
        $input->shouldReceive('getOption')->with('exclude')->andReturn('*.tmp,*.bak');
        $input->shouldReceive('getOption')->with('no-validation')->andReturn(false);
        $input->shouldReceive('getOption')->with('no-type-conversion')->andReturn(false);
        
        $config = $this->loader->loadFromInput($input);
        
        $this->assertEquals(['*.tmp', '*.bak'], $config->getExcludePatterns());
    }

    public function testLoadFromInputValidationOptions(): void
    {
        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getArguments')->andReturn(['headers' => ['test.h']]);
        $input->shouldReceive('getOption')->with('headers')->andReturn(null);
        $input->shouldReceive('getOption')->with('library')->andReturn(null);
        $input->shouldReceive('getOption')->with('output')->andReturn(null);
        $input->shouldReceive('getOption')->with('namespace')->andReturn(null);
        $input->shouldReceive('getOption')->with('exclude')->andReturn(null);
        $input->shouldReceive('getOption')->with('no-validation')->andReturn(true);
        $input->shouldReceive('getOption')->with('no-type-conversion')->andReturn(true);
        
        $config = $this->loader->loadFromInput($input);
        
        $validationConfig = $config->getValidationConfig();
        $this->assertFalse($validationConfig->isParameterValidationEnabled());
        $this->assertFalse($validationConfig->isTypeConversionEnabled());
    }

    public function testLoadWithOverrides(): void
    {
        // Create config file
        $configFile = $this->tempDir . '/config.yaml';
        $configData = [
            'headerFiles' => ['file.h'],
            'libraryFile' => 'file.so',
            'outputPath' => './file-output',
            'namespace' => 'File\\MyNamespace',
            'validation' => [
                'enableParameterValidation' => true,
                'enableTypeConversion' => true,
            ],
        ];
        file_put_contents($configFile, Yaml::dump($configData));
        
        // Create input with overrides
        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getArguments')->andReturn(['headers' => ['override.h']]);
        $input->shouldReceive('getOption')->with('headers')->andReturn(null);
        $input->shouldReceive('getOption')->with('library')->andReturn('override.so');
        $input->shouldReceive('getOption')->with('output')->andReturn('./override-output');
        $input->shouldReceive('getOption')->with('namespace')->andReturn(null);
        $input->shouldReceive('getOption')->with('exclude')->andReturn(null);
        $input->shouldReceive('getOption')->with('no-validation')->andReturn(true);
        $input->shouldReceive('getOption')->with('no-type-conversion')->andReturn(false);
        
        $config = $this->loader->loadWithOverrides($configFile, $input);
        
        // Check overrides took effect
        $this->assertEquals(['override.h'], $config->getHeaderFiles());
        $this->assertEquals('override.so', $config->getLibraryFile());
        $this->assertEquals('./override-output', $config->getOutputPath());
        $this->assertEquals('File\\MyNamespace', $config->getNamespace()); // Not overridden
        
        $validationConfig = $config->getValidationConfig();
        $this->assertFalse($validationConfig->isParameterValidationEnabled()); // Overridden
        $this->assertTrue($validationConfig->isTypeConversionEnabled()); // Not overridden
    }

    public function testLoadWithOverridesPartial(): void
    {
        // Create config file
        $configFile = $this->tempDir . '/config.yaml';
        $configData = [
            'headerFiles' => ['file.h'],
            'libraryFile' => 'file.so',
            'outputPath' => './file-output',
            'namespace' => 'File\\MyNamespace',
        ];
        file_put_contents($configFile, Yaml::dump($configData));
        
        // Create input with only some overrides
        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getArguments')->andReturn([]);
        $input->shouldReceive('getOption')->with('headers')->andReturn(null);
        $input->shouldReceive('getOption')->with('library')->andReturn(null);
        $input->shouldReceive('getOption')->with('output')->andReturn(null);
        $input->shouldReceive('getOption')->with('namespace')->andReturn('Override\\MyNamespace');
        $input->shouldReceive('getOption')->with('exclude')->andReturn(null);
        $input->shouldReceive('getOption')->with('no-validation')->andReturn(false);
        $input->shouldReceive('getOption')->with('no-type-conversion')->andReturn(false);
        
        $config = $this->loader->loadWithOverrides($configFile, $input);
        
        // Check file values are preserved where not overridden
        $this->assertEquals(['file.h'], $config->getHeaderFiles());
        $this->assertEquals('file.so', $config->getLibraryFile());
        $this->assertEquals('./file-output', $config->getOutputPath());
        $this->assertEquals('Override\\MyNamespace', $config->getNamespace()); // Overridden
    }

    public function testCreateDefault(): void
    {
        $config = $this->loader->createDefault();
        
        $this->assertInstanceOf(ProjectConfig::class, $config);
        $this->assertEmpty($config->getHeaderFiles());
        $this->assertEquals('', $config->getLibraryFile());
        $this->assertEquals('./generated', $config->getOutputPath());
        $this->assertEquals('Generated\\FFI', $config->getNamespace());
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