<?php

declare(strict_types=1);

namespace Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Exception\ConfigurationException;

class ConfigurationExceptionTest extends TestCase
{
    public function testMissingRequiredField(): void
    {
        $exception = ConfigurationException::missingRequiredField('output_path');
        
        $this->assertStringContainsString('Missing required configuration field: output_path', $exception->getMessage());
        $this->assertTrue($exception->isRecoverable());
        $this->assertStringContainsString("Add the 'output_path' field", $exception->getSuggestion());
        
        $context = $exception->getContext();
        $this->assertEquals('output_path', $context['field']);
    }

    public function testMissingRequiredFieldWithConfigFile(): void
    {
        $exception = ConfigurationException::missingRequiredField('namespace', 'config.yaml');
        
        $this->assertStringContainsString('Missing required configuration field: namespace in config.yaml', $exception->getMessage());
        $this->assertTrue($exception->isRecoverable());
        $this->assertStringContainsString('config.yaml', $exception->getSuggestion());
        
        $context = $exception->getContext();
        $this->assertEquals('namespace', $context['field']);
        $this->assertEquals('config.yaml', $context['config_file']);
    }

    public function testInvalidConfigurationFile(): void
    {
        $exception = ConfigurationException::invalidConfigurationFile('invalid.yaml');
        
        $this->assertStringContainsString('Invalid configuration file: invalid.yaml', $exception->getMessage());
        $this->assertTrue($exception->isRecoverable());
        $this->assertStringContainsString('Check the configuration file format', $exception->getSuggestion());
        
        $context = $exception->getContext();
        $this->assertEquals('invalid.yaml', $context['file']);
        $this->assertNull($context['reason']);
    }

    public function testInvalidConfigurationFileWithReason(): void
    {
        $exception = ConfigurationException::invalidConfigurationFile('invalid.yaml', 'YAML syntax error');
        
        $this->assertStringContainsString('Invalid configuration file: invalid.yaml - YAML syntax error', $exception->getMessage());
        
        $context = $exception->getContext();
        $this->assertEquals('invalid.yaml', $context['file']);
        $this->assertEquals('YAML syntax error', $context['reason']);
    }

    public function testInvalidPath(): void
    {
        $exception = ConfigurationException::invalidPath('/nonexistent/path');
        
        $this->assertStringContainsString('Invalid path: /nonexistent/path', $exception->getMessage());
        $this->assertTrue($exception->isRecoverable());
        $this->assertStringContainsString('Ensure the path exists', $exception->getSuggestion());
        
        $context = $exception->getContext();
        $this->assertEquals('/nonexistent/path', $context['path']);
        $this->assertEquals('path', $context['type']);
    }

    public function testInvalidPathWithType(): void
    {
        $exception = ConfigurationException::invalidPath('/bad/header.h', 'header file');
        
        $this->assertStringContainsString('Invalid header file: /bad/header.h', $exception->getMessage());
        $this->assertStringContainsString('Ensure the header file exists', $exception->getSuggestion());
        
        $context = $exception->getContext();
        $this->assertEquals('/bad/header.h', $context['path']);
        $this->assertEquals('header file', $context['type']);
    }

    public function testUnsupportedConfigurationVersion(): void
    {
        $supportedVersions = ['1.0', '1.1', '2.0'];
        $exception = ConfigurationException::unsupportedConfigurationVersion('0.9', $supportedVersions);
        
        $this->assertStringContainsString('Unsupported configuration version: 0.9', $exception->getMessage());
        $this->assertTrue($exception->isRecoverable());
        $this->assertStringContainsString('Use one of the supported versions: 1.0, 1.1, 2.0', $exception->getSuggestion());
        
        $context = $exception->getContext();
        $this->assertEquals('0.9', $context['version']);
        $this->assertEquals($supportedVersions, $context['supported_versions']);
    }

    public function testAllConfigurationExceptionsAreRecoverable(): void
    {
        $exceptions = [
            ConfigurationException::missingRequiredField('test'),
            ConfigurationException::invalidConfigurationFile('test.yaml'),
            ConfigurationException::invalidPath('/test/path'),
            ConfigurationException::unsupportedConfigurationVersion('1.0', ['2.0'])
        ];

        foreach ($exceptions as $exception) {
            $this->assertTrue($exception->isRecoverable(), 
                'Configuration exception should be recoverable: ' . get_class($exception));
        }
    }

    public function testAllConfigurationExceptionsHaveSuggestions(): void
    {
        $exceptions = [
            ConfigurationException::missingRequiredField('test'),
            ConfigurationException::invalidConfigurationFile('test.yaml'),
            ConfigurationException::invalidPath('/test/path'),
            ConfigurationException::unsupportedConfigurationVersion('1.0', ['2.0'])
        ];

        foreach ($exceptions as $exception) {
            $this->assertNotNull($exception->getSuggestion(), 
                'Configuration exception should have a suggestion: ' . get_class($exception));
            $this->assertNotEmpty($exception->getSuggestion());
        }
    }
}