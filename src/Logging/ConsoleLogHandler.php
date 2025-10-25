<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Logging;

use DateTime;

/**
 * Console log handler with colored output
 */
class ConsoleLogHandler implements LogHandlerInterface
{
    private bool $colorEnabled = true;
    private array $colors = [
        'emergency' => "\033[1;37;41m", // White on red background
        'alert' => "\033[1;33;41m",     // Yellow on red background
        'critical' => "\033[1;31m",     // Bold red
        'error' => "\033[0;31m",        // Red
        'warning' => "\033[0;33m",      // Yellow
        'notice' => "\033[0;36m",       // Cyan
        'info' => "\033[0;32m",         // Green
        'debug' => "\033[0;37m",        // Light gray
    ];
    private string $resetColor = "\033[0m";

    public function __construct(bool $colorEnabled = null)
    {
        if ($colorEnabled === null) {
            // Auto-detect color support
            $this->colorEnabled = $this->supportsColor();
        } else {
            $this->colorEnabled = $colorEnabled;
        }
    }

    public function handle(array $record): void
    {
        $output = $this->formatRecord($record);
        
        if ($record['level'] === 'error' || $record['level'] === 'critical' || $record['level'] === 'emergency') {
            fwrite(STDERR, $output . PHP_EOL);
        } else {
            fwrite(STDOUT, $output . PHP_EOL);
        }
    }

    public function canHandle(string $level): bool
    {
        return true; // Console handler can handle all levels
    }

    /**
     * Format a log record for console output
     */
    private function formatRecord(array $record): string
    {
        $timestamp = $record['timestamp']->format('Y-m-d H:i:s');
        $level = strtoupper($record['level']);
        $message = $record['message'];
        
        // Apply color if enabled
        if ($this->colorEnabled && isset($this->colors[$record['level']])) {
            $level = $this->colors[$record['level']] . $level . $this->resetColor;
        }
        
        $output = "[{$timestamp}] {$level}: {$message}";
        
        // Add context information for certain log types
        if (isset($record['context']['progress_type'])) {
            $output .= $this->formatProgressContext($record['context']);
        } elseif (!empty($record['context'])) {
            $output .= $this->formatContext($record['context']);
        }
        
        return $output;
    }

    /**
     * Format progress-specific context
     */
    private function formatProgressContext(array $context): string
    {
        switch ($context['progress_type']) {
            case 'progress':
                if (isset($context['percentage'])) {
                    return " ({$context['current']}/{$context['total']} - {$context['percentage']}%)";
                }
                break;
                
            case 'performance':
                if (isset($context['duration_ms'])) {
                    return " (took {$context['duration_ms']}ms)";
                }
                break;
                
            case 'start':
            case 'complete':
                // No additional formatting needed
                return '';
        }
        
        return '';
    }

    /**
     * Format general context information
     */
    private function formatContext(array $context): string
    {
        // Filter out internal context keys
        $filtered = array_filter($context, function($key) {
            return !in_array($key, ['progress_type', 'operation', 'current', 'total', 'percentage', 'duration_ms']);
        }, ARRAY_FILTER_USE_KEY);
        
        if (empty($filtered)) {
            return '';
        }
        
        $contextStr = [];
        foreach ($filtered as $key => $value) {
            if (is_scalar($value)) {
                $contextStr[] = "{$key}={$value}";
            } elseif (is_array($value)) {
                $contextStr[] = "{$key}=" . json_encode($value);
            } else {
                $contextStr[] = "{$key}=" . gettype($value);
            }
        }
        
        return ' [' . implode(', ', $contextStr) . ']';
    }

    /**
     * Check if the terminal supports color output
     */
    private function supportsColor(): bool
    {
        // Check if we're in a terminal
        if (!function_exists('posix_isatty') || !posix_isatty(STDOUT)) {
            return false;
        }
        
        // Check TERM environment variable
        $term = getenv('TERM');
        if ($term === false || $term === 'dumb') {
            return false;
        }
        
        // Check for color support indicators
        return strpos($term, 'color') !== false || 
               strpos($term, '256') !== false || 
               getenv('COLORTERM') !== false;
    }

    /**
     * Enable or disable color output
     */
    public function setColorEnabled(bool $enabled): void
    {
        $this->colorEnabled = $enabled;
    }
}