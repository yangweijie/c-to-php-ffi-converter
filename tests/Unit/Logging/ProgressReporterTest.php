<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Logging\ProgressReporter;
use Yangweijie\CWrapper\Logging\LoggerInterface;

class ProgressReporterTest extends TestCase
{
    private ProgressReporter $progressReporter;
    private MockLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new MockLogger();
        $this->progressReporter = new ProgressReporter($this->logger);
    }

    public function testStartOperation(): void
    {
        $this->progressReporter->startOperation('test-op', 'Test Operation', 100);
        
        $this->assertTrue($this->logger->hasStartOperationCall('Test Operation'));
        
        $progress = $this->progressReporter->getProgress('test-op');
        $this->assertNotNull($progress);
        $this->assertEquals('test-op', $progress['operation_id']);
        $this->assertEquals('Test Operation', $progress['description']);
        $this->assertEquals(100, $progress['total_steps']);
        $this->assertEquals(0, $progress['current_step']);
        $this->assertEquals(0, $progress['percentage']);
    }

    public function testUpdateProgress(): void
    {
        $this->progressReporter->startOperation('test-op', 'Test Operation', 100);
        $this->progressReporter->updateProgress('test-op', 25);
        
        $progress = $this->progressReporter->getProgress('test-op');
        $this->assertEquals(25, $progress['current_step']);
        $this->assertEquals(25.0, $progress['percentage']);
    }

    public function testUpdateProgressWithDescription(): void
    {
        $this->progressReporter->startOperation('test-op', 'Test Operation', 100);
        $this->progressReporter->updateProgress('test-op', 50, 'Processing file.h');
        
        $this->assertTrue($this->logger->hasProgressCall('Test Operation'));
    }

    public function testProgressReporting(): void
    {
        $this->progressReporter->setReportInterval(25); // Report every 25%
        $this->progressReporter->startOperation('test-op', 'Test Operation', 100);
        
        // Should not report at 10%
        $this->progressReporter->updateProgress('test-op', 10);
        $this->assertFalse($this->logger->hasProgressCall('Test Operation'));
        
        // Should report at 25%
        $this->progressReporter->updateProgress('test-op', 25);
        $this->assertTrue($this->logger->hasProgressCall('Test Operation'));
        
        // Reset logger to test next interval
        $this->logger->clear();
        
        // Should not report at 30%
        $this->progressReporter->updateProgress('test-op', 30);
        $this->assertFalse($this->logger->hasProgressCall('Test Operation'));
        
        // Should report at 50%
        $this->progressReporter->updateProgress('test-op', 50);
        $this->assertTrue($this->logger->hasProgressCall('Test Operation'));
    }

    public function testCompleteOperation(): void
    {
        $this->progressReporter->startOperation('test-op', 'Test Operation', 100);
        $this->progressReporter->updateProgress('test-op', 100);
        
        $summary = ['files_processed' => 50, 'errors' => 0];
        $this->progressReporter->completeOperation('test-op', $summary);
        
        $this->assertTrue($this->logger->hasCompleteOperationCall('Test Operation'));
        $this->assertTrue($this->logger->hasPerformanceCall('Test Operation'));
        
        // Operation should be removed after completion
        $this->assertNull($this->progressReporter->getProgress('test-op'));
    }

    public function testFailOperation(): void
    {
        $this->progressReporter->startOperation('test-op', 'Test Operation', 100);
        $this->progressReporter->updateProgress('test-op', 50);
        
        $this->progressReporter->failOperation('test-op', 'File not found', ['file' => 'missing.h']);
        
        $this->assertTrue($this->logger->hasErrorCall('Operation failed: Test Operation'));
        
        // Operation should be removed after failure
        $this->assertNull($this->progressReporter->getProgress('test-op'));
    }

    public function testAddSubstep(): void
    {
        $this->progressReporter->startOperation('test-op', 'Test Operation', 10);
        $this->progressReporter->addSubstep('test-op', 'parse-headers', 'Parsing headers', 50);
        
        $progress = $this->progressReporter->getProgress('test-op');
        $this->assertArrayHasKey('substeps', $progress);
        $this->assertArrayHasKey('parse-headers', $progress['substeps']);
        
        $substep = $progress['substeps']['parse-headers'];
        $this->assertEquals('Parsing headers', $substep['description']);
        $this->assertEquals(50, $substep['total_items']);
        $this->assertEquals(0, $substep['current_item']);
        $this->assertEquals(0, $substep['percentage']);
    }

    public function testUpdateSubstep(): void
    {
        $this->progressReporter->startOperation('test-op', 'Test Operation', 10);
        $this->progressReporter->addSubstep('test-op', 'parse-headers', 'Parsing headers', 50);
        $this->progressReporter->updateSubstep('test-op', 'parse-headers', 25, 'header1.h');
        
        $progress = $this->progressReporter->getProgress('test-op');
        $substep = $progress['substeps']['parse-headers'];
        
        $this->assertEquals(25, $substep['current_item']);
        $this->assertEquals(50.0, $substep['percentage']);
    }

    public function testUpdateSubstepWithManyItems(): void
    {
        $this->progressReporter->startOperation('test-op', 'Test Operation', 10);
        $this->progressReporter->addSubstep('test-op', 'parse-headers', 'Parsing headers', 200);
        
        // Should log debug message every 10 items for large substeps
        $this->progressReporter->updateSubstep('test-op', 'parse-headers', 10, 'header10.h');
        $this->assertTrue($this->logger->hasDebugCall('Substep progress: Parsing headers'));
    }

    public function testGetActiveOperations(): void
    {
        $this->assertEquals([], $this->progressReporter->getActiveOperations());
        
        $this->progressReporter->startOperation('op1', 'Operation 1', 100);
        $this->progressReporter->startOperation('op2', 'Operation 2', 50);
        
        $activeOps = $this->progressReporter->getActiveOperations();
        $this->assertCount(2, $activeOps);
        $this->assertContains('op1', $activeOps);
        $this->assertContains('op2', $activeOps);
        
        $this->progressReporter->completeOperation('op1');
        
        $activeOps = $this->progressReporter->getActiveOperations();
        $this->assertCount(1, $activeOps);
        $this->assertContains('op2', $activeOps);
    }

    public function testSetReportInterval(): void
    {
        $this->progressReporter->setReportInterval(50);
        $this->progressReporter->startOperation('test-op', 'Test Operation', 100);
        
        // Should not report at 25%
        $this->progressReporter->updateProgress('test-op', 25);
        $this->assertFalse($this->logger->hasProgressCall('Test Operation'));
        
        // Should report at 50%
        $this->progressReporter->updateProgress('test-op', 50);
        $this->assertTrue($this->logger->hasProgressCall('Test Operation'));
    }

    public function testSetReportIntervalBounds(): void
    {
        // Test minimum bound
        $this->progressReporter->setReportInterval(0);
        $this->progressReporter->startOperation('test-op', 'Test Operation', 100);
        $this->progressReporter->updateProgress('test-op', 1);
        $this->assertTrue($this->logger->hasProgressCall('Test Operation')); // Should report every 1%
        
        $this->logger->clear();
        
        // Test maximum bound
        $this->progressReporter->setReportInterval(150);
        $this->progressReporter->startOperation('test-op2', 'Test Operation 2', 100);
        $this->progressReporter->updateProgress('test-op2', 99);
        $this->assertFalse($this->logger->hasProgressCall('Test Operation 2')); // Should only report at 100%
    }

    public function testUpdateProgressForNonexistentOperation(): void
    {
        // Should not throw an exception
        $this->progressReporter->updateProgress('nonexistent', 50);
        $this->assertFalse($this->logger->hasProgressCall(''));
    }

    public function testAddSubstepForNonexistentOperation(): void
    {
        // Should not throw an exception
        $this->progressReporter->addSubstep('nonexistent', 'substep', 'Description', 10);
        $this->assertNull($this->progressReporter->getProgress('nonexistent'));
    }

    public function testProgressWithZeroSteps(): void
    {
        $this->progressReporter->startOperation('test-op', 'Empty Operation', 0);
        $this->progressReporter->updateProgress('test-op', 0);
        
        $progress = $this->progressReporter->getProgress('test-op');
        $this->assertEquals(0, $progress['percentage']);
    }
}

/**
 * Mock logger for testing
 */
class MockLogger implements LoggerInterface
{
    private array $calls = [];

    public function emergency(string $message, array $context = []): void
    {
        $this->calls[] = ['level' => 'emergency', 'message' => $message, 'context' => $context];
    }

    public function alert(string $message, array $context = []): void
    {
        $this->calls[] = ['level' => 'alert', 'message' => $message, 'context' => $context];
    }

    public function critical(string $message, array $context = []): void
    {
        $this->calls[] = ['level' => 'critical', 'message' => $message, 'context' => $context];
    }

    public function error(string $message, array $context = []): void
    {
        $this->calls[] = ['level' => 'error', 'message' => $message, 'context' => $context];
    }

    public function warning(string $message, array $context = []): void
    {
        $this->calls[] = ['level' => 'warning', 'message' => $message, 'context' => $context];
    }

    public function notice(string $message, array $context = []): void
    {
        $this->calls[] = ['level' => 'notice', 'message' => $message, 'context' => $context];
    }

    public function info(string $message, array $context = []): void
    {
        $this->calls[] = ['level' => 'info', 'message' => $message, 'context' => $context];
    }

    public function debug(string $message, array $context = []): void
    {
        $this->calls[] = ['level' => 'debug', 'message' => $message, 'context' => $context];
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $this->calls[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }

    public function startOperation(string $operation, array $context = []): void
    {
        $this->calls[] = ['method' => 'startOperation', 'operation' => $operation, 'context' => $context];
    }

    public function completeOperation(string $operation, array $context = []): void
    {
        $this->calls[] = ['method' => 'completeOperation', 'operation' => $operation, 'context' => $context];
    }

    public function progress(string $operation, int $current, int $total, array $context = []): void
    {
        $this->calls[] = ['method' => 'progress', 'operation' => $operation, 'current' => $current, 'total' => $total, 'context' => $context];
    }

    public function performance(string $operation, float $duration, array $metrics = []): void
    {
        $this->calls[] = ['method' => 'performance', 'operation' => $operation, 'duration' => $duration, 'metrics' => $metrics];
    }

    public function hasStartOperationCall(string $operation): bool
    {
        return $this->hasCall('startOperation', $operation);
    }

    public function hasCompleteOperationCall(string $operation): bool
    {
        return $this->hasCall('completeOperation', $operation);
    }

    public function hasProgressCall(string $operation): bool
    {
        return $this->hasCall('progress', $operation);
    }

    public function hasPerformanceCall(string $operation): bool
    {
        return $this->hasCall('performance', $operation);
    }

    public function hasErrorCall(string $message): bool
    {
        foreach ($this->calls as $call) {
            if ($call['level'] === 'error' && strpos($call['message'], $message) !== false) {
                return true;
            }
        }
        return false;
    }

    public function hasDebugCall(string $message): bool
    {
        foreach ($this->calls as $call) {
            if ($call['level'] === 'debug' && strpos($call['message'], $message) !== false) {
                return true;
            }
        }
        return false;
    }

    private function hasCall(string $method, string $operation): bool
    {
        foreach ($this->calls as $call) {
            if (isset($call['method']) && $call['method'] === $method && $call['operation'] === $operation) {
                return true;
            }
        }
        return false;
    }

    public function clear(): void
    {
        $this->calls = [];
    }

    public function getCalls(): array
    {
        return $this->calls;
    }
}