<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Config;

use Yangweijie\CWrapper\Exception\ConfigurationException;

/**
 * Configuration validator for validating project configuration
 */
class ConfigValidator
{
    /**
     * Validate a project configuration
     *
     * @throws ConfigurationException
     */
    public function validate(ProjectConfig $config): void
    {
        $this->validateRequiredFields($config);
        $this->validatePaths($config);
        $this->validateNamespace($config);
        $this->validateExcludePatterns($config);
    }

    /**
     * Validate required configuration fields
     *
     * @throws ConfigurationException
     */
    private function validateRequiredFields(ProjectConfig $config): void
    {
        if (empty($config->getHeaderFiles())) {
            throw new ConfigurationException('At least one header file must be specified');
        }

        if (empty($config->getNamespace())) {
            throw new ConfigurationException('Namespace cannot be empty');
        }

        if (empty($config->getOutputPath())) {
            throw new ConfigurationException('Output path cannot be empty');
        }
    }

    /**
     * Validate file and directory paths
     *
     * @throws ConfigurationException
     */
    private function validatePaths(ProjectConfig $config): void
    {
        // Validate header files
        foreach ($config->getHeaderFiles() as $headerFile) {
            $this->validateHeaderFile($headerFile);
        }

        // Validate library file if specified
        $libraryFile = $config->getLibraryFile();
        if (!empty($libraryFile)) {
            $this->validateLibraryFile($libraryFile);
        }

        // Validate output path
        $this->validateOutputPath($config->getOutputPath());
    }

    /**
     * Validate a header file path
     *
     * @throws ConfigurationException
     */
    private function validateHeaderFile(string $headerFile): void
    {
        if (empty($headerFile)) {
            throw new ConfigurationException('Header file path cannot be empty');
        }

        // Check if file exists
        if (!file_exists($headerFile)) {
            throw new ConfigurationException("Header file not found: {$headerFile}");
        }

        // Check if file is readable
        if (!is_readable($headerFile)) {
            throw new ConfigurationException("Header file is not readable: {$headerFile}");
        }

        // Check file extension
        $extension = strtolower(pathinfo($headerFile, PATHINFO_EXTENSION));
        if (!in_array($extension, ['h', 'hpp', 'hxx', 'hh'], true)) {
            throw new ConfigurationException("Invalid header file extension: {$headerFile}. Expected .h, .hpp, .hxx, or .hh");
        }
    }

    /**
     * Validate a library file path
     *
     * @throws ConfigurationException
     */
    private function validateLibraryFile(string $libraryFile): void
    {
        if (empty($libraryFile)) {
            return; // Library file is optional
        }

        // Check if file exists
        if (!file_exists($libraryFile)) {
            throw new ConfigurationException("Library file not found: {$libraryFile}");
        }

        // Check if file is readable
        if (!is_readable($libraryFile)) {
            throw new ConfigurationException("Library file is not readable: {$libraryFile}");
        }

        // Check file extension for common library formats
        $extension = strtolower(pathinfo($libraryFile, PATHINFO_EXTENSION));
        $validExtensions = ['so', 'dll', 'dylib', 'a'];
        
        if (!in_array($extension, $validExtensions, true)) {
            throw new ConfigurationException(
                "Invalid library file extension: {$libraryFile}. Expected one of: " . implode(', ', $validExtensions)
            );
        }
    }

    /**
     * Validate output path
     *
     * @throws ConfigurationException
     */
    private function validateOutputPath(string $outputPath): void
    {
        if (empty($outputPath)) {
            throw new ConfigurationException('Output path cannot be empty');
        }

        // Check if parent directory exists and is writable
        $parentDir = dirname($outputPath);
        if (!is_dir($parentDir)) {
            throw new ConfigurationException("Output parent directory does not exist: {$parentDir}");
        }

        if (!is_writable($parentDir)) {
            throw new ConfigurationException("Output parent directory is not writable: {$parentDir}");
        }

        // If output path exists, check if it's writable
        if (file_exists($outputPath)) {
            if (is_file($outputPath)) {
                throw new ConfigurationException("Output path is a file, expected directory: {$outputPath}");
            }

            if (!is_writable($outputPath)) {
                throw new ConfigurationException("Output directory is not writable: {$outputPath}");
            }
        }
    }

    /**
     * Validate namespace format
     *
     * @throws ConfigurationException
     */
    private function validateNamespace(ProjectConfig $config): void
    {
        $namespace = $config->getNamespace();
        
        if (empty($namespace)) {
            throw new ConfigurationException('Namespace cannot be empty');
        }

        // Validate PHP namespace format
        if (!preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff\\\\]*$/', $namespace)) {
            throw new ConfigurationException("Invalid namespace format: {$namespace}");
        }

        // Check for reserved keywords
        $parts = explode('\\', $namespace);
        $reservedKeywords = [
            'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone',
            'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty',
            'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit',
            'extends', 'final', 'finally', 'for', 'foreach', 'function', 'global', 'goto', 'if',
            'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset',
            'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require',
            'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use',
            'var', 'while', 'xor', 'yield'
        ];

        foreach ($parts as $part) {
            if (in_array(strtolower($part), $reservedKeywords, true)) {
                throw new ConfigurationException("Namespace contains reserved keyword: {$part}");
            }
        }
    }

    /**
     * Validate exclude patterns
     *
     * @throws ConfigurationException
     */
    private function validateExcludePatterns(ProjectConfig $config): void
    {
        foreach ($config->getExcludePatterns() as $pattern) {
            if (empty($pattern)) {
                throw new ConfigurationException('Exclude pattern cannot be empty');
            }

            // Test if pattern is a valid regex (if it starts and ends with delimiters)
            if (preg_match('/^\/.*\/$/', $pattern)) {
                if (@preg_match($pattern, '') === false) {
                    throw new ConfigurationException("Invalid regex pattern: {$pattern}");
                }
            }
        }
    }

    /**
     * Validate configuration schema from array data
     *
     * @param array<string, mixed> $data
     * @throws ConfigurationException
     */
    public function validateSchema(array $data): void
    {
        $this->validateSchemaStructure($data);
        $this->validateSchemaTypes($data);
    }

    /**
     * @param array<string, mixed> $data
     * @throws ConfigurationException
     */
    private function validateSchemaStructure(array $data): void
    {
        $allowedKeys = [
            'headerFiles', 'libraryFile', 'outputPath', 'namespace', 
            'excludePatterns', 'validation'
        ];

        foreach (array_keys($data) as $key) {
            if (!in_array($key, $allowedKeys, true)) {
                throw new ConfigurationException("Unknown configuration key: {$key}");
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @throws ConfigurationException
     */
    private function validateSchemaTypes(array $data): void
    {
        if (isset($data['headerFiles']) && !is_array($data['headerFiles'])) {
            throw new ConfigurationException('headerFiles must be an array');
        }

        if (isset($data['libraryFile']) && !is_string($data['libraryFile'])) {
            throw new ConfigurationException('libraryFile must be a string');
        }

        if (isset($data['outputPath']) && !is_string($data['outputPath'])) {
            throw new ConfigurationException('outputPath must be a string');
        }

        if (isset($data['namespace']) && !is_string($data['namespace'])) {
            throw new ConfigurationException('namespace must be a string');
        }

        if (isset($data['excludePatterns']) && !is_array($data['excludePatterns'])) {
            throw new ConfigurationException('excludePatterns must be an array');
        }

        if (isset($data['validation']) && !is_array($data['validation'])) {
            throw new ConfigurationException('validation must be an array');
        }

        // Validate validation sub-schema
        if (isset($data['validation']) && is_array($data['validation'])) {
            $this->validateValidationSchema($data['validation']);
        }
    }

    /**
     * @param array<string, mixed> $validationData
     * @throws ConfigurationException
     */
    private function validateValidationSchema(array $validationData): void
    {
        $allowedKeys = ['enableParameterValidation', 'enableTypeConversion', 'customValidationRules'];

        foreach (array_keys($validationData) as $key) {
            if (!in_array($key, $allowedKeys, true)) {
                throw new ConfigurationException("Unknown validation configuration key: {$key}");
            }
        }

        if (isset($validationData['enableParameterValidation']) && !is_bool($validationData['enableParameterValidation'])) {
            throw new ConfigurationException('enableParameterValidation must be a boolean');
        }

        if (isset($validationData['enableTypeConversion']) && !is_bool($validationData['enableTypeConversion'])) {
            throw new ConfigurationException('enableTypeConversion must be a boolean');
        }

        if (isset($validationData['customValidationRules']) && !is_array($validationData['customValidationRules'])) {
            throw new ConfigurationException('customValidationRules must be an array');
        }
    }
}