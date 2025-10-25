<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Exception;

use Exception;
use Throwable;

/**
 * Base exception for all FFI converter errors
 * 
 * Provides enhanced error context, debugging information, and recovery capabilities
 */
abstract class FFIConverterException extends Exception
{
    protected array $context = [];
    protected array $debugInfo = [];
    protected bool $recoverable = false;
    protected ?string $suggestion = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = [],
        array $debugInfo = [],
        bool $recoverable = false,
        ?string $suggestion = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
        $this->debugInfo = $debugInfo;
        $this->recoverable = $recoverable;
        $this->suggestion = $suggestion;
    }

    /**
     * Get error context information
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Add context information to the exception
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Get debugging information
     */
    public function getDebugInfo(): array
    {
        return $this->debugInfo;
    }

    /**
     * Add debugging information
     */
    public function addDebugInfo(string $key, mixed $value): self
    {
        $this->debugInfo[$key] = $value;
        return $this;
    }

    /**
     * Check if the error is recoverable
     */
    public function isRecoverable(): bool
    {
        return $this->recoverable;
    }

    /**
     * Set whether the error is recoverable
     */
    public function setRecoverable(bool $recoverable): self
    {
        $this->recoverable = $recoverable;
        return $this;
    }

    /**
     * Get suggestion for fixing the error
     */
    public function getSuggestion(): ?string
    {
        return $this->suggestion;
    }

    /**
     * Set suggestion for fixing the error
     */
    public function setSuggestion(string $suggestion): self
    {
        $this->suggestion = $suggestion;
        return $this;
    }

    /**
     * Get formatted error information including context and debug info
     */
    public function getFormattedError(): array
    {
        return [
            'type' => static::class,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'debug_info' => $this->debugInfo,
            'recoverable' => $this->recoverable,
            'suggestion' => $this->suggestion,
            'trace' => $this->getTraceAsString(),
        ];
    }

    /**
     * Create a detailed error report
     */
    public function createErrorReport(): string
    {
        $report = "Error Type: " . static::class . "\n";
        $report .= "Message: " . $this->getMessage() . "\n";
        $report .= "File: " . $this->getFile() . ":" . $this->getLine() . "\n";
        
        if (!empty($this->context)) {
            $report .= "Context:\n";
            foreach ($this->context as $key => $value) {
                $report .= "  {$key}: " . $this->formatValue($value) . "\n";
            }
        }

        if (!empty($this->debugInfo)) {
            $report .= "Debug Information:\n";
            foreach ($this->debugInfo as $key => $value) {
                $report .= "  {$key}: " . $this->formatValue($value) . "\n";
            }
        }

        if ($this->suggestion) {
            $report .= "Suggestion: " . $this->suggestion . "\n";
        }

        $report .= "Recoverable: " . ($this->recoverable ? 'Yes' : 'No') . "\n";

        return $report;
    }

    /**
     * Format a value for display in error reports
     */
    private function formatValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT);
        }
        if (is_object($value)) {
            return get_class($value);
        }
        return (string) $value;
    }
}