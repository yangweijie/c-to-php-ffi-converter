<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Config;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;
use Yangweijie\CWrapper\Exception\ConfigurationException;

/**
 * Configuration loader for files and CLI arguments
 */
class ConfigLoader
{
    private ConfigValidator $validator;

    public function __construct(?ConfigValidator $validator = null)
    {
        $this->validator = $validator ?? new ConfigValidator();
    }
    /**
     * Load configuration from a YAML file
     *
     * @throws ConfigurationException
     */
    public function loadFromFile(string $configPath): ProjectConfig
    {
        if (!file_exists($configPath)) {
            throw new ConfigurationException("Configuration file not found: {$configPath}");
        }

        if (!is_readable($configPath)) {
            throw new ConfigurationException("Configuration file is not readable: {$configPath}");
        }

        try {
            $content = file_get_contents($configPath);
            if ($content === false) {
                throw new ConfigurationException("Failed to read configuration file: {$configPath}");
            }

            $data = Yaml::parse($content);
            if (!is_array($data)) {
                throw new ConfigurationException("Invalid YAML format in configuration file: {$configPath}");
            }

            // Validate schema before creating config
            $this->validator->validateSchema($data);
            
            $config = ProjectConfig::fromArray($data);
            $this->validator->validate($config);
            
            return $config;
        } catch (\Exception $e) {
            if ($e instanceof ConfigurationException) {
                throw $e;
            }
            throw new ConfigurationException("Failed to parse configuration file: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Load configuration from CLI input
     */
    public function loadFromInput(InputInterface $input): ProjectConfig
    {
        $headerFiles = $this->getHeaderFilesFromInput($input);
        $libraryFile = $input->getOption('library') ?? '';
        $outputPath = $input->getOption('output') ?? './generated';
        $namespace = $input->getOption('namespace') ?? 'Generated\\FFI';
        $excludePatterns = $this->getExcludePatternsFromInput($input);

        // Create validation config from input
        $validationConfig = new ValidationConfig(
            !$input->getOption('no-validation'),
            !$input->getOption('no-type-conversion'),
            []
        );

        $config = new ProjectConfig(
            $headerFiles,
            $libraryFile,
            $outputPath,
            $namespace,
            $excludePatterns,
            $validationConfig
        );
        
        $this->validator->validate($config);
        return $config;
    }

    /**
     * Merge configuration from file with CLI input overrides
     */
    public function loadWithOverrides(string $configPath, InputInterface $input): ProjectConfig
    {
        $fileConfig = $this->loadFromFile($configPath);
        $inputConfig = $this->loadFromInput($input);

        // Start with file config and override with CLI values where provided
        $mergedConfig = clone $fileConfig;

        // Override header files if provided via CLI
        $inputHeaderFiles = $this->getHeaderFilesFromInput($input);
        if (!empty($inputHeaderFiles)) {
            $mergedConfig->setHeaderFiles($inputHeaderFiles);
        }

        // Override other options if provided
        if ($input->getOption('library')) {
            $mergedConfig->setLibraryFile($input->getOption('library'));
        }

        if ($input->getOption('output')) {
            $mergedConfig->setOutputPath($input->getOption('output'));
        }

        if ($input->getOption('namespace')) {
            $mergedConfig->setNamespace($input->getOption('namespace'));
        }

        $inputExcludePatterns = $this->getExcludePatternsFromInput($input);
        if (!empty($inputExcludePatterns)) {
            $mergedConfig->setExcludePatterns($inputExcludePatterns);
        }

        // Override validation settings if provided
        $validationConfig = $fileConfig->getValidationConfig();
        if ($input->getOption('no-validation')) {
            $validationConfig->setParameterValidation(false);
        }
        if ($input->getOption('no-type-conversion')) {
            $validationConfig->setTypeConversion(false);
        }

        $this->validator->validate($mergedConfig);
        return $mergedConfig;
    }

    /**
     * Create a default configuration
     */
    public function createDefault(): ProjectConfig
    {
        return new ProjectConfig();
    }

    /**
     * @return array<string>
     */
    private function getHeaderFilesFromInput(InputInterface $input): array
    {
        $headerFiles = [];

        // Get from arguments
        $arguments = $input->getArguments();
        if (isset($arguments['headers'])) {
            if (is_array($arguments['headers'])) {
                $headerFiles = array_merge($headerFiles, $arguments['headers']);
            } else {
                $headerFiles[] = $arguments['headers'];
            }
        }

        // Get from option
        $headerOption = $input->getOption('headers');
        if ($headerOption) {
            if (is_array($headerOption)) {
                $headerFiles = array_merge($headerFiles, $headerOption);
            } else {
                // Split comma-separated values
                $headerFiles = array_merge($headerFiles, array_map('trim', explode(',', $headerOption)));
            }
        }

        return array_filter($headerFiles);
    }

    /**
     * @return array<string>
     */
    private function getExcludePatternsFromInput(InputInterface $input): array
    {
        $excludeOption = $input->getOption('exclude');
        if (!$excludeOption) {
            return [];
        }

        if (is_array($excludeOption)) {
            return $excludeOption;
        }

        // Split comma-separated values
        return array_map('trim', explode(',', $excludeOption));
    }
}