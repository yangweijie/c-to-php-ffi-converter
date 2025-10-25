<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Logging;

use Yangweijie\CWrapper\Exception\FFIConverterException;
use Throwable;

/**
 * Detailed error reporter with suggestions and recovery information
 */
class ErrorReporter
{
    private LoggerInterface $logger;
    private array $errorHistory = [];
    private array $suggestionRules = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->setupDefaultSuggestionRules();
    }

    /**
     * Report an error with detailed context and suggestions
     */
    public function reportError(Throwable $error, array $additionalContext = []): void
    {
        $errorReport = $this->createErrorReport($error, $additionalContext);
        
        // Log the error
        $this->logger->error($error->getMessage(), $errorReport);
        
        // Store in history
        $this->errorHistory[] = [
            'timestamp' => time(),
            'error' => $error,
            'report' => $errorReport
        ];
        
        // Generate and log suggestions
        $suggestions = $this->generateSuggestions($error, $errorReport);
        if (!empty($suggestions)) {
            $this->logger->info('Error resolution suggestions:', [
                'error_type' => get_class($error),
                'suggestions' => $suggestions
            ]);
        }
    }

    /**
     * Report a recoverable error that was handled
     */
    public function reportRecoveredError(Throwable $error, string $recoveryMethod, array $context = []): void
    {
        $this->logger->warning('Recovered from error: ' . $error->getMessage(), array_merge($context, [
            'error_type' => get_class($error),
            'recovery_method' => $recoveryMethod,
            'recovered' => true
        ]));
    }

    /**
     * Report multiple related errors
     */
    public function reportErrorBatch(array $errors, string $batchContext): void
    {
        $this->logger->error("Multiple errors occurred during: {$batchContext}", [
            'batch_context' => $batchContext,
            'error_count' => count($errors),
            'error_types' => array_count_values(array_map('get_class', $errors))
        ]);

        foreach ($errors as $index => $error) {
            $this->logger->error("Batch error #{$index}: " . $error->getMessage(), [
                'batch_index' => $index,
                'error_type' => get_class($error),
                'file' => $error->getFile(),
                'line' => $error->getLine()
            ]);
        }
    }

    /**
     * Generate a comprehensive error summary
     */
    public function generateErrorSummary(int $timeframeHours = 24): array
    {
        $cutoffTime = time() - ($timeframeHours * 3600);
        $recentErrors = array_filter($this->errorHistory, function($entry) use ($cutoffTime) {
            return $entry['timestamp'] >= $cutoffTime;
        });

        $summary = [
            'timeframe_hours' => $timeframeHours,
            'total_errors' => count($recentErrors),
            'error_types' => [],
            'most_common_errors' => [],
            'recovery_rate' => 0,
            'suggestions_generated' => 0
        ];

        $errorTypes = [];
        $recoveredCount = 0;
        $suggestionsCount = 0;

        foreach ($recentErrors as $entry) {
            $errorType = get_class($entry['error']);
            $errorTypes[$errorType] = ($errorTypes[$errorType] ?? 0) + 1;

            if (isset($entry['report']['recovered']) && $entry['report']['recovered']) {
                $recoveredCount++;
            }

            if (isset($entry['report']['suggestions']) && !empty($entry['report']['suggestions'])) {
                $suggestionsCount++;
            }
        }

        $summary['error_types'] = $errorTypes;
        $summary['most_common_errors'] = array_slice(
            array_keys(array_sort($errorTypes, function($a, $b) { return $b <=> $a; })), 
            0, 
            5
        );
        $summary['recovery_rate'] = count($recentErrors) > 0 ? round(($recoveredCount / count($recentErrors)) * 100, 1) : 0;
        $summary['suggestions_generated'] = $suggestionsCount;

        return $summary;
    }

    /**
     * Add a custom suggestion rule
     */
    public function addSuggestionRule(string $errorPattern, callable $suggestionGenerator): void
    {
        $this->suggestionRules[$errorPattern] = $suggestionGenerator;
    }

    /**
     * Clear error history
     */
    public function clearHistory(): void
    {
        $this->errorHistory = [];
        $this->logger->info('Error history cleared');
    }

    /**
     * Get error history
     */
    public function getErrorHistory(): array
    {
        return $this->errorHistory;
    }

    /**
     * Create a detailed error report
     */
    private function createErrorReport(Throwable $error, array $additionalContext): array
    {
        $report = [
            'error_type' => get_class($error),
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString()
        ];

        // Add FFI converter specific information
        if ($error instanceof FFIConverterException) {
            $report['context'] = $error->getContext();
            $report['debug_info'] = $error->getDebugInfo();
            $report['recoverable'] = $error->isRecoverable();
            $report['suggestion'] = $error->getSuggestion();
        }

        // Add additional context
        if (!empty($additionalContext)) {
            $report['additional_context'] = $additionalContext;
        }

        // Add system information
        $report['system_info'] = [
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'time' => date('c')
        ];

        return $report;
    }

    /**
     * Generate suggestions for error resolution
     */
    private function generateSuggestions(Throwable $error, array $errorReport): array
    {
        $suggestions = [];

        // Check custom suggestion rules
        foreach ($this->suggestionRules as $pattern => $generator) {
            if (preg_match($pattern, $error->getMessage()) || preg_match($pattern, get_class($error))) {
                $customSuggestions = $generator($error, $errorReport);
                if (is_array($customSuggestions)) {
                    $suggestions = array_merge($suggestions, $customSuggestions);
                } elseif (is_string($customSuggestions)) {
                    $suggestions[] = $customSuggestions;
                }
            }
        }

        // Add built-in suggestion if available
        if ($error instanceof FFIConverterException && $error->getSuggestion()) {
            $suggestions[] = $error->getSuggestion();
        }

        // Add generic suggestions based on error type
        $suggestions = array_merge($suggestions, $this->getGenericSuggestions($error));

        return array_unique($suggestions);
    }

    /**
     * Get generic suggestions based on error type and message
     */
    private function getGenericSuggestions(Throwable $error): array
    {
        $suggestions = [];
        $message = strtolower($error->getMessage());

        if (strpos($message, 'file not found') !== false || strpos($message, 'no such file') !== false) {
            $suggestions[] = 'Check that the file path is correct and the file exists';
            $suggestions[] = 'Verify file permissions and accessibility';
        }

        if (strpos($message, 'permission denied') !== false) {
            $suggestions[] = 'Check file and directory permissions';
            $suggestions[] = 'Ensure the process has read/write access to the required files';
        }

        if (strpos($message, 'memory') !== false) {
            $suggestions[] = 'Increase PHP memory limit if possible';
            $suggestions[] = 'Process files in smaller batches to reduce memory usage';
        }

        if (strpos($message, 'timeout') !== false) {
            $suggestions[] = 'Increase timeout limits for long-running operations';
            $suggestions[] = 'Break down large operations into smaller chunks';
        }

        return $suggestions;
    }

    /**
     * Set up default suggestion rules
     */
    private function setupDefaultSuggestionRules(): void
    {
        // Configuration error suggestions
        $this->suggestionRules['/ConfigurationException/'] = function($error, $report) {
            return [
                'Review the configuration file format and required fields',
                'Check the documentation for configuration examples',
                'Validate configuration file syntax (YAML/JSON)'
            ];
        };

        // Analysis error suggestions
        $this->suggestionRules['/AnalysisException/'] = function($error, $report) {
            return [
                'Ensure C header files are syntactically correct',
                'Check that all dependencies are available',
                'Verify include paths and library locations'
            ];
        };

        // Generation error suggestions
        $this->suggestionRules['/GenerationException/'] = function($error, $report) {
            return [
                'Check output directory permissions',
                'Ensure sufficient disk space is available',
                'Verify template files are accessible'
            ];
        };

        // Validation error suggestions
        $this->suggestionRules['/ValidationException/'] = function($error, $report) {
            return [
                'Check parameter types and values',
                'Review function signature requirements',
                'Ensure all required parameters are provided'
            ];
        };
    }
}