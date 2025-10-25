<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Logging;

/**
 * Logger interface for the FFI converter
 */
interface LoggerInterface
{
    /**
     * Log an emergency message
     */
    public function emergency(string $message, array $context = []): void;

    /**
     * Log an alert message
     */
    public function alert(string $message, array $context = []): void;

    /**
     * Log a critical message
     */
    public function critical(string $message, array $context = []): void;

    /**
     * Log an error message
     */
    public function error(string $message, array $context = []): void;

    /**
     * Log a warning message
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Log a notice message
     */
    public function notice(string $message, array $context = []): void;

    /**
     * Log an info message
     */
    public function info(string $message, array $context = []): void;

    /**
     * Log a debug message
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Log a message with arbitrary level
     */
    public function log(string $level, string $message, array $context = []): void;
}