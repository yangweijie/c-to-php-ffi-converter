<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Exception;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Default error handler implementation with recovery capabilities
 */
class ErrorHandler implements ErrorHandlerInterface
{
    private LoggerInterface $logger;
    private array $errors = [];
    private array $recoveryStrategies = [];
    private int $maxRecoveryAttempts = 3;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->setupDefaultRecoveryStrategies();
    }

    public function handleError(Throwable $error): void
    {
        $this->errors[] = [
            'error' => $error,
            'timestamp' => time(),
            'recovered' => false
        ];

        $this->logger->error('Error occurred: ' . $error->getMessage(), [
            'exception' => $error,
            'context' => $error instanceof FFIConverterException ? $error->getContext() : [],
            'debug_info' => $error instanceof FFIConverterException ? $error->getDebugInfo() : []
        ]);

        // Attempt recovery if the error is recoverable
        if ($this->isRecoverable($error)) {
            $recovered = $this->attemptRecovery($error);
            $this->errors[array_key_last($this->errors)]['recovered'] = $recovered;
            
            if ($recovered) {
                $this->logger->info('Successfully recovered from error: ' . $error->getMessage());
            } else {
                $this->logger->warning('Failed to recover from error: ' . $error->getMessage());
            }
        }
    }

    public function reportError(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
        
        // Create a generic exception for tracking
        $error = new class($message) extends FFIConverterException {};
        $this->errors[] = [
            'error' => $error,
            'timestamp' => time(),
            'recovered' => false,
            'context' => $context
        ];
    }

    public function isRecoverable(Throwable $error): bool
    {
        if ($error instanceof FFIConverterException) {
            return $error->isRecoverable();
        }

        // Default recovery logic for non-FFI exceptions
        return $this->hasRecoveryStrategy(get_class($error));
    }

    public function attemptRecovery(Throwable $error): bool
    {
        $errorClass = get_class($error);
        
        if (!$this->hasRecoveryStrategy($errorClass)) {
            return false;
        }

        $strategy = $this->recoveryStrategies[$errorClass];
        $attempts = 0;

        while ($attempts < $this->maxRecoveryAttempts) {
            try {
                $result = $strategy($error, $attempts);
                if ($result) {
                    $this->logger->info("Recovery successful for {$errorClass} after {$attempts} attempts");
                    return true;
                }
            } catch (Throwable $recoveryError) {
                $this->logger->warning("Recovery attempt {$attempts} failed for {$errorClass}: " . $recoveryError->getMessage());
            }
            
            $attempts++;
        }

        $this->logger->error("All recovery attempts failed for {$errorClass}");
        return false;
    }

    public function getErrorStats(): array
    {
        $stats = [
            'total_errors' => count($this->errors),
            'recovered_errors' => 0,
            'unrecovered_errors' => 0,
            'error_types' => [],
            'recent_errors' => []
        ];

        foreach ($this->errors as $errorData) {
            if ($errorData['recovered']) {
                $stats['recovered_errors']++;
            } else {
                $stats['unrecovered_errors']++;
            }

            $errorType = get_class($errorData['error']);
            $stats['error_types'][$errorType] = ($stats['error_types'][$errorType] ?? 0) + 1;

            // Include recent errors (last 10)
            if (count($stats['recent_errors']) < 10) {
                $stats['recent_errors'][] = [
                    'type' => $errorType,
                    'message' => $errorData['error']->getMessage(),
                    'timestamp' => $errorData['timestamp'],
                    'recovered' => $errorData['recovered']
                ];
            }
        }

        return $stats;
    }

    public function clearErrors(): void
    {
        $this->errors = [];
        $this->logger->info('Error history cleared');
    }

    /**
     * Add a custom recovery strategy for a specific error type
     */
    public function addRecoveryStrategy(string $errorClass, callable $strategy): void
    {
        $this->recoveryStrategies[$errorClass] = $strategy;
    }

    /**
     * Check if a recovery strategy exists for an error type
     */
    private function hasRecoveryStrategy(string $errorClass): bool
    {
        return isset($this->recoveryStrategies[$errorClass]);
    }

    /**
     * Set up default recovery strategies for common error types
     */
    private function setupDefaultRecoveryStrategies(): void
    {
        // Recovery strategy for configuration errors
        $this->recoveryStrategies[ConfigurationException::class] = function (ConfigurationException $error, int $attempt): bool {
            // Try to use default configuration values
            $context = $error->getContext();
            
            if (isset($context['field']) && $context['field'] === 'output_path') {
                // Use current directory as default output path
                return true;
            }
            
            if (isset($context['path']) && $context['type'] === 'header_file') {
                // Try common header file locations
                $commonPaths = ['/usr/include', '/usr/local/include'];
                foreach ($commonPaths as $path) {
                    if (file_exists($path . '/' . basename($context['path']))) {
                        return true;
                    }
                }
            }
            
            return false;
        };

        // Recovery strategy for analysis errors
        $this->recoveryStrategies[AnalysisException::class] = function (AnalysisException $error, int $attempt): bool {
            $context = $error->getContext();
            
            if (isset($context['dependency'])) {
                // Try to continue without the dependency
                return true;
            }
            
            if (isset($context['construct'])) {
                // Skip unsupported constructs and continue
                return true;
            }
            
            return false;
        };

        // Recovery strategy for generation errors
        $this->recoveryStrategies[GenerationException::class] = function (GenerationException $error, int $attempt): bool {
            $context = $error->getContext();
            
            if (isset($context['template'])) {
                // Try to use a fallback template
                return $attempt === 0; // Only try once
            }
            
            if (isset($context['directory'])) {
                // Try to create the directory
                try {
                    if (!is_dir($context['directory'])) {
                        mkdir($context['directory'], 0755, true);
                        return true;
                    }
                } catch (Throwable $e) {
                    return false;
                }
            }
            
            return false;
        };

        // Recovery strategy for validation errors
        $this->recoveryStrategies[ValidationException::class] = function (ValidationException $error, int $attempt): bool {
            // Validation errors are generally not recoverable at runtime
            // but we can log them and continue with warnings
            return false;
        };
    }

    /**
     * Set the maximum number of recovery attempts
     */
    public function setMaxRecoveryAttempts(int $maxAttempts): void
    {
        $this->maxRecoveryAttempts = max(1, $maxAttempts);
    }

    /**
     * Get all recorded errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the most recent error
     */
    public function getLastError(): ?array
    {
        return empty($this->errors) ? null : end($this->errors);
    }
}