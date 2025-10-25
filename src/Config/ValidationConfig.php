<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Config;

/**
 * Configuration for validation settings
 */
class ValidationConfig
{
    public function __construct(
        private bool $enableParameterValidation = true,
        private bool $enableTypeConversion = true,
        private array $customValidationRules = []
    ) {
    }

    public function isParameterValidationEnabled(): bool
    {
        return $this->enableParameterValidation;
    }

    public function isTypeConversionEnabled(): bool
    {
        return $this->enableTypeConversion;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomValidationRules(): array
    {
        return $this->customValidationRules;
    }

    public function setParameterValidation(bool $enabled): self
    {
        $this->enableParameterValidation = $enabled;
        return $this;
    }

    public function setTypeConversion(bool $enabled): self
    {
        $this->enableTypeConversion = $enabled;
        return $this;
    }

    /**
     * @param array<string, mixed> $rules
     */
    public function setCustomValidationRules(array $rules): self
    {
        $this->customValidationRules = $rules;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'enableParameterValidation' => $this->enableParameterValidation,
            'enableTypeConversion' => $this->enableTypeConversion,
            'customValidationRules' => $this->customValidationRules,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['enableParameterValidation'] ?? true,
            $data['enableTypeConversion'] ?? true,
            $data['customValidationRules'] ?? []
        );
    }
}