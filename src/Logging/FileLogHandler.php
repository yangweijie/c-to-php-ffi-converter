<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Logging;

use DateTime;

/**
 * File log handler for persistent logging
 */
class FileLogHandler implements LogHandlerInterface
{
    private string $logFile;
    private string $minLevel;
    private int $maxFileSize;
    private int $maxFiles;
    
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

    public function __construct(
        string $logFile,
        string $minLevel = 'info',
        int $maxFileSize = 10 * 1024 * 1024, // 10MB
        int $maxFiles = 5
    ) {
        $this->logFile = $logFile;
        $this->minLevel = $minLevel;
        $this->maxFileSize = $maxFileSize;
        $this->maxFiles = $maxFiles;
        
        // Ensure log directory exists
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public function handle(array $record): void
    {
        if (!$this->canHandle($record['level'])) {
            return;
        }
        
        // Check if log rotation is needed
        $this->rotateIfNeeded();
        
        $logLine = $this->formatRecord($record);
        file_put_contents($this->logFile, $logLine . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public function canHandle(string $level): bool
    {
        return self::LEVELS[$level] <= self::LEVELS[$this->minLevel];
    }

    /**
     * Format a log record for file output
     */
    private function formatRecord(array $record): string
    {
        $timestamp = $record['timestamp']->format('Y-m-d H:i:s.u');
        $level = strtoupper($record['level']);
        $message = $record['message'];
        
        $logData = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'memory_mb' => round($record['memory_usage'] / 1024 / 1024, 2),
            'peak_memory_mb' => round($record['peak_memory'] / 1024 / 1024, 2),
        ];
        
        // Add context if present
        if (!empty($record['context'])) {
            $logData['context'] = $record['context'];
        }
        
        return json_encode($logData, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Rotate log files if the current file is too large
     */
    private function rotateIfNeeded(): void
    {
        if (!file_exists($this->logFile) || filesize($this->logFile) < $this->maxFileSize) {
            return;
        }
        
        // Rotate existing files
        for ($i = $this->maxFiles - 1; $i > 0; $i--) {
            $oldFile = $this->logFile . '.' . $i;
            $newFile = $this->logFile . '.' . ($i + 1);
            
            if (file_exists($oldFile)) {
                if ($i === $this->maxFiles - 1) {
                    unlink($oldFile); // Delete the oldest file
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        // Move current log to .1
        if (file_exists($this->logFile)) {
            rename($this->logFile, $this->logFile . '.1');
        }
    }

    /**
     * Get the current log file path
     */
    public function getLogFile(): string
    {
        return $this->logFile;
    }

    /**
     * Set minimum log level for this handler
     */
    public function setMinLevel(string $level): void
    {
        if (!isset(self::LEVELS[$level])) {
            throw new \InvalidArgumentException("Invalid log level: {$level}");
        }
        $this->minLevel = $level;
    }
}