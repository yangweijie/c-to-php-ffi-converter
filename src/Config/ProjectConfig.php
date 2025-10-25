<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Config;

use Yangweijie\CWrapper\Exception\ConfigurationException;

/**
 * Project configuration data model
 */
class ProjectConfig implements ConfigInterface
{
    /**
     * @param array<string> $headerFiles
     * @param array<string> $excludePatterns
     */
    public function __construct(
        private array $headerFiles = [],
        private string $libraryFile = '',
        private string $outputPath = './generated',
        private string $namespace = 'Generated\\FFI',
        private array $excludePatterns = [],
        private ValidationConfig $validation = new ValidationConfig(),
        private string $generationType = 'object'
    ) {
    }

    /**
     * @return array<string>
     */
    public function getHeaderFiles(): array
    {
        return $this->headerFiles;
    }

    public function getLibraryFile(): string
    {
        return $this->libraryFile;
    }

    public function getOutputPath(): string
    {
        return $this->outputPath;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return array<string, mixed>
     */
    public function getValidationRules(): array
    {
        return $this->validation->toArray();
    }

    /**
     * @return array<string>
     */
    public function getExcludePatterns(): array
    {
        return $this->excludePatterns;
    }

    public function getValidationConfig(): ValidationConfig
    {
        return $this->validation;
    }

    public function getGenerationType(): string
    {
        return $this->generationType;
    }

    /**
     * @param array<string> $headerFiles
     */
    public function setHeaderFiles(array $headerFiles): self
    {
        $this->headerFiles = $headerFiles;
        return $this;
    }

    public function setLibraryFile(string $libraryFile): self
    {
        $this->libraryFile = $libraryFile;
        return $this;
    }

    public function setOutputPath(string $outputPath): self
    {
        $this->outputPath = $outputPath;
        return $this;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @param array<string> $excludePatterns
     */
    public function setExcludePatterns(array $excludePatterns): self
    {
        $this->excludePatterns = $excludePatterns;
        return $this;
    }

    public function setValidationConfig(ValidationConfig $validation): self
    {
        $this->validation = $validation;
        return $this;
    }

    public function setGenerationType(string $generationType): self
    {
        if (!in_array($generationType, ['object', 'functional'])) {
            throw new ConfigurationException("Invalid generation type: {$generationType}. Must be 'object' or 'functional'.");
        }
        $this->generationType = $generationType;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'headerFiles' => $this->headerFiles,
            'libraryFile' => $this->libraryFile,
            'outputPath' => $this->outputPath,
            'namespace' => $this->namespace,
            'excludePatterns' => $this->excludePatterns,
            'validation' => $this->validation->toArray(),
            'generationType' => $this->generationType,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $validation = isset($data['validation']) && is_array($data['validation'])
            ? ValidationConfig::fromArray($data['validation'])
            : new ValidationConfig();

        return new self(
            $data['headerFiles'] ?? [],
            $data['libraryFile'] ?? '',
            $data['outputPath'] ?? './generated',
            $data['namespace'] ?? 'Generated\\FFI',
            $data['excludePatterns'] ?? [],
            $validation,
            $data['generationType'] ?? 'object'
        );
    }


}