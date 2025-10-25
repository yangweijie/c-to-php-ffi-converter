<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Exception;

use Throwable;

/**
 * Interface for handling errors and exceptions in the FFI converter
 */
interface ErrorHandlerInterface
{
    /**
     * Handle an error or exception
     */
    public function handleError(Throwable $error): void;

    /**
     * Report an error with context
     */
    public function reportError(string $message, array $context = []): void;

    /**
     * Check if an error is recoverable
     */
    public function isRecoverable(Throwable $error): bool;

    /**
     * Attempt to recover from an error
     */
    public function attemptRecovery(Throwable $error): bool;

    /**
     * Get error statistics
     */
    public function getErrorStats(): array;

    /**
     * Clear error history
     */
    public function clearErrors(): void;
}