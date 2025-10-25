<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Logging;

use DateTime;

/**
 * Structured logger for the FFI converter with progress reporting
 */
class Logger implements LoggerInterface
{
    private array $handlers = [];
    private string $minLevel = 'debug';
    private array $context = [];
    
    private const LEVELS = [
        'emergency' => 0,
        'alert' => 1,
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7,
    ];

    public function __construct(array $handlers = [])
    {
        $this->handlers = $handlers;
        
        // Add console handler by default if no handlers provided
        if (empty($this->handlers)) {
            $this->handlers[] = new ConsoleLogHandler();
        }
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $record = $this->createLogRecord($level, $message, $context);
        
        foreach ($this->handlers as $handler) {
            $handler->handle($record);
        }
    }

    /**
     * Add a log handler
     */
    public function addHandler(LogHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    /**
     * Set minimum log level
     */
    public function setMinLevel(string $level): void
    {
        if (!isset(self::LEVELS[$level])) {
            throw new \InvalidArgumentException("Invalid log level: {$level}");
        }
        $this->minLevel = $level;
    }

    /**
     * Add global context that will be included in all log messages
     */
    public function addContext(array $context): void
    {
        $this->context = array_merge($this->context, $context);
    }

    /**
     * Clear global context
     */
    public function clearContext(): void
    {
        $this->context = [];
    }

    /**
     * Log progress information
     */
    public function progress(string $operation, int $current, int $total, array $context = []): void
    {
        $percentage = $total > 0 ? round(($current / $total) * 100, 1) : 0;
        
        $this->info("Progress: {$operation}", array_merge($context, [
            'operation' => $operation,
            'current' => $current,
            'total' => $total,
            'percentage' => $percentage,
            'progress_type' => 'progress'
        ]));
    }

    /**
     * Log the start of an operation
     */
    public function startOperation(string $operation, array $context = []): void
    {
        $this->info("Starting: {$operation}", array_merge($context, [
            'operation' => $operation,
            'progress_type' => 'start'
        ]));
    }

    /**
     * Log the completion of an operation
     */
    public function completeOperation(string $operation, array $context = []): void
    {
        $this->info("Completed: {$operation}", array_merge($context, [
            'operation' => $operation,
            'progress_type' => 'complete'
        ]));
    }

    /**
     * Log performance metrics
     */
    public function performance(string $operation, float $duration, array $metrics = []): void
    {
        $this->info("Performance: {$operation}", array_merge($metrics, [
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'progress_type' => 'performance'
        ]));
    }

    /**
     * Check if a log level should be logged
     */
    private function shouldLog(string $level): bool
    {
        return self::LEVELS[$level] <= self::LEVELS[$this->minLevel];
    }

    /**
     * Create a log record
     */
    private function createLogRecord(string $level, string $message, array $context): array
    {
        return [
            'timestamp' => new DateTime(),
            'level' => $level,
            'message' => $message,
            'context' => array_merge($this->context, $context),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }
}