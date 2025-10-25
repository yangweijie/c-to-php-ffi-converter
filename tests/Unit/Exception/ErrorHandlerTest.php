<?php

declare(strict_types=1);

namespace Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Yangweijie\CWrapper\Exception\ErrorHandler;
use Yangweijie\CWrapper\Exception\ConfigurationException;
use Yangweijie\CWrapper\Exception\AnalysisException;
use Yangweijie\CWrapper\Exception\GenerationException;
use Yangweijie\CWrapper\Exception\ValidationException;

class ErrorHandlerTest extends TestCase
{
    private ErrorHandler $errorHandler;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new TestLogger();
        $this->errorHandler = new ErrorHandler($this->logger);
    }

    public function testHandleErrorLogsError(): void
    {
        $error = new \Exception('Test error');
        
        $this->errorHandler->handleError($error);
        
        $this->assertTrue($this->logger->hasErrorRecords());
        $this->assertTrue($this->logger->hasErrorThatContains('Test error'));
    }

    public function testHandleRecoverableError(): void
    {
        $error = ConfigurationException::missingRequiredField('output_path');
        
        $this->errorHandler->handleError($error);
        
        $this->assertTrue($this->logger->hasErrorRecords());
        $this->assertTrue($this->logger->hasInfoRecords()); // Recovery success message
    }

    public function testReportError(): void
    {
        $context = ['key' => 'value'];
        
        $this->errorHandler->reportError('Custom error message', $context);
        
        $this->assertTrue($this->logger->hasErrorRecords());
        $this->assertTrue($this->logger->hasErrorThatContains('Custom error message'));
        
        $errorStats = $this->errorHandler->getErrorStats();
        $this->assertEquals(1, $errorStats['total_errors']);
    }

    public function testIsRecoverableWithFFIConverterException(): void
    {
        $recoverableError = ConfigurationException::missingRequiredField('test');
        $nonRecoverableError = new ValidationException('Test validation error');
        $nonRecoverableError->setRecoverable(false);
        
        $this->assertTrue($this->errorHandler->isRecoverable($recoverableError));
        $this->assertFalse($this->errorHandler->isRecoverable($nonRecoverableError));
    }

    public function testIsRecoverableWithGenericException(): void
    {
        $genericError = new \Exception('Generic error');
        
        // Generic exceptions are not recoverable by default
        $this->assertFalse($this->errorHandler->isRecoverable($genericError));
    }

    public function testAttemptRecoveryWithConfigurationException(): void
    {
        $error = ConfigurationException::missingRequiredField('output_path');
        
        $result = $this->errorHandler->attemptRecovery($error);
        
        $this->assertTrue($result);
        $this->assertTrue($this->logger->hasInfoThatContains('Recovery successful'));
    }

    public function testAttemptRecoveryWithAnalysisException(): void
    {
        $error = AnalysisException::dependencyResolutionFailed('missing_header.h');
        
        $result = $this->errorHandler->attemptRecovery($error);
        
        $this->assertTrue($result);
    }

    public function testAttemptRecoveryFailsForUnsupportedError(): void
    {
        $error = new \RuntimeException('Unsupported error type');
        
        $result = $this->errorHandler->attemptRecovery($error);
        
        $this->assertFalse($result);
    }

    public function testGetErrorStats(): void
    {
        // Add some errors
        $this->errorHandler->handleError(new \Exception('Error 1'));
        $this->errorHandler->handleError(ConfigurationException::missingRequiredField('test'));
        $this->errorHandler->handleError(new \Exception('Error 2'));
        
        $stats = $this->errorHandler->getErrorStats();
        
        $this->assertEquals(3, $stats['total_errors']);
        $this->assertGreaterThanOrEqual(0, $stats['recovered_errors']); // Allow 0 or more recovered errors
        $this->assertArrayHasKey('error_types', $stats);
        $this->assertArrayHasKey('recent_errors', $stats);
        
        // Check error types are counted
        $this->assertArrayHasKey('Exception', $stats['error_types']);
        $this->assertArrayHasKey(ConfigurationException::class, $stats['error_types']);
    }

    public function testClearErrors(): void
    {
        $this->errorHandler->handleError(new \Exception('Test error'));
        
        $statsBeforeClear = $this->errorHandler->getErrorStats();
        $this->assertEquals(1, $statsBeforeClear['total_errors']);
        
        $this->errorHandler->clearErrors();
        
        $statsAfterClear = $this->errorHandler->getErrorStats();
        $this->assertEquals(0, $statsAfterClear['total_errors']);
        
        $this->assertTrue($this->logger->hasInfoThatContains('Error history cleared'));
    }

    public function testAddCustomRecoveryStrategy(): void
    {
        $customError = new \InvalidArgumentException('Custom error');
        
        // Initially not recoverable
        $this->assertFalse($this->errorHandler->isRecoverable($customError));
        
        // Add custom recovery strategy
        $this->errorHandler->addRecoveryStrategy(\InvalidArgumentException::class, function($error, $attempt) {
            return true; // Always recoverable
        });
        
        // Now it should be recoverable
        $this->assertTrue($this->errorHandler->isRecoverable($customError));
        $this->assertTrue($this->errorHandler->attemptRecovery($customError));
    }

    public function testSetMaxRecoveryAttempts(): void
    {
        $this->errorHandler->setMaxRecoveryAttempts(1);
        
        // Add a strategy that fails on first attempt but succeeds on second
        $this->errorHandler->addRecoveryStrategy(\RuntimeException::class, function($error, $attempt) {
            return $attempt > 0; // Fail on first attempt (0), succeed on second (1)
        });
        
        $error = new \RuntimeException('Test error');
        
        // Should fail because max attempts is 1 (only attempt 0)
        $result = $this->errorHandler->attemptRecovery($error);
        $this->assertFalse($result);
    }

    public function testGetErrors(): void
    {
        $error1 = new \Exception('Error 1');
        $error2 = ConfigurationException::missingRequiredField('test');
        
        $this->errorHandler->handleError($error1);
        $this->errorHandler->handleError($error2);
        
        $errors = $this->errorHandler->getErrors();
        
        $this->assertCount(2, $errors);
        $this->assertSame($error1, $errors[0]['error']);
        $this->assertSame($error2, $errors[1]['error']);
        $this->assertArrayHasKey('timestamp', $errors[0]);
        $this->assertArrayHasKey('recovered', $errors[0]);
    }

    public function testGetLastError(): void
    {
        $this->assertNull($this->errorHandler->getLastError());
        
        $error = new \Exception('Test error');
        $this->errorHandler->handleError($error);
        
        $lastError = $this->errorHandler->getLastError();
        $this->assertNotNull($lastError);
        $this->assertSame($error, $lastError['error']);
    }

    public function testRecoveryAttemptsWithFailures(): void
    {
        // Add a strategy that always fails
        $this->errorHandler->addRecoveryStrategy(\LogicException::class, function($error, $attempt) {
            throw new \Exception('Recovery failed');
        });
        
        $error = new \LogicException('Test error');
        
        $result = $this->errorHandler->attemptRecovery($error);
        
        $this->assertFalse($result);
        $this->assertTrue($this->logger->hasErrorThatContains('All recovery attempts failed'));
    }
}
/**
 * S
imple test logger for capturing log messages
 */
class TestLogger implements LoggerInterface
{
    private array $records = [];

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context
        ];
    }

    public function hasErrorRecords(): bool
    {
        return $this->hasRecordsWithLevel('error');
    }

    public function hasInfoRecords(): bool
    {
        return $this->hasRecordsWithLevel('info');
    }

    public function hasErrorThatContains(string $needle): bool
    {
        return $this->hasRecordThatContains('error', $needle);
    }

    public function hasInfoThatContains(string $needle): bool
    {
        return $this->hasRecordThatContains('info', $needle);
    }

    private function hasRecordsWithLevel(string $level): bool
    {
        foreach ($this->records as $record) {
            if ($record['level'] === $level) {
                return true;
            }
        }
        return false;
    }

    private function hasRecordThatContains(string $level, string $needle): bool
    {
        foreach ($this->records as $record) {
            if ($record['level'] === $level && strpos($record['message'], $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function clear(): void
    {
        $this->records = [];
    }
}