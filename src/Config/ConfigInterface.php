<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Config;

/**
 * Interface for configuration management
 */
interface ConfigInterface
{
    /**
     * Get header files to process
     *
     * @return array<string>
     */
    public function getHeaderFiles(): array;

    /**
     * Get library file path
     *
     * @return string
     */
    public function getLibraryFile(): string;

    /**
     * Get output path for generated files
     *
     * @return string
     */
    public function getOutputPath(): string;

    /**
     * Get namespace for generated classes
     *
     * @return string
     */
    public function getNamespace(): string;

    /**
     * Get validation rules configuration
     *
     * @return array<string, mixed>
     */
    public function getValidationRules(): array;
}