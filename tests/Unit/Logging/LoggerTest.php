<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Logging\Logger;
use Yangweijie\CWrapper\Logging\LogHandlerInterface;

class LoggerTest extends TestCase
{
    private Logger $logger;
    private TestLogHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new TestLogHandler();
        $this->logger = new Logger([$this->handler]);
    }

    public function testLogLevels(): void
    {
        $this->logger->emergency('Emergency message');
        $this->logger->alert('Alert message');
        $this->logger->critical('Critical message');
        $this->logger->error('Error message');
        $this->logger->warning('Warning message');
        $this->logger->notice('Notice message');
        $this->logger->info('Info message');
        $this->logger->debug('Debug message');

        $records = $this->handler->getRecords();
        $this->assertCount(8, $records);

        $levels = array_column($records, 'level');
        $expectedLevels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
        $this->assertEquals($expectedLevels, $levels);
    }

    public function testLogWithContext(): void
    {
        $context = ['key1' => 'value1', 'key2' => 'value2'];
        
        $this->logger->info('Test message', $context);
        
        $records = $this->handler->getRecords();
        $this->assertCount(1, $records);
        $this->assertEquals($context, $records[0]['context']);
    }

    public function testSetMinLevel(): void
    {
        $this->logger->setMinLevel('warning');
        
        $this->logger->debug('Debug message');
        $this->logger->info('Info message');
        $this->logger->warning('Warning message');
        $this->logger->error('Error message');
        
        $records = $this->handler->getRecords();
        $this->assertCount(2, $records); // Only warning and error should be logged
        
        $levels = array_column($records, 'level');
        $this->assertEquals(['warning', 'error'], $levels);
    }

    public function testAddGlobalContext(): void
    {
        $globalContext = ['global_key' => 'global_value'];
        $this->logger->addContext($globalContext);
        
        $localContext = ['local_key' => 'local_value'];
        $this->logger->info('Test message', $localContext);
        
        $records = $this->handler->getRecords();
        $expectedContext = array_merge($globalContext, $localContext);
        $this->assertEquals($expectedContext, $records[0]['context']);
    }

    public function testClearContext(): void
    {
        $this->logger->addContext(['global_key' => 'global_value']);
        $this->logger->info('Message with global context');
        
        $this->logger->clearContext();
        $this->logger->info('Message without global context');
        
        $records = $this->handler->getRecords();
        $this->assertCount(2, $records);
        
        $this->assertArrayHasKey('global_key', $records[0]['context']);
        $this->assertArrayNotHasKey('global_key', $records[1]['context']);
    }

    public function testProgress(): void
    {
        $this->logger->progress('Processing files', 50, 100, ['file' => 'test.h']);
        
        $records = $this->handler->getRecords();
        $this->assertCount(1, $records);
        
        $context = $records[0]['context'];
        $this->assertEquals('Processing files', $context['operation']);
        $this->assertEquals(50, $context['current']);
        $this->assertEquals(100, $context['total']);
        $this->assertEquals(50.0, $context['percentage']);
        $this->assertEquals('progress', $context['progress_type']);
        $this->assertEquals('test.h', $context['file']);
    }

    public function testStartOperation(): void
    {
        $this->logger->startOperation('File analysis', ['file_count' => 5]);
        
        $records = $this->handler->getRecords();
        $this->assertCount(1, $records);
        
        $this->assertEquals('info', $records[0]['level']);
        $this->assertStringContainsString('Starting: File analysis', $records[0]['message']);
        
        $context = $records[0]['context'];
        $this->assertEquals('File analysis', $context['operation']);
        $this->assertEquals('start', $context['progress_type']);
        $this->assertEquals(5, $context['file_count']);
    }

    public function testCompleteOperation(): void
    {
        $this->logger->completeOperation('File analysis', ['files_processed' => 10]);
        
        $records = $this->handler->getRecords();
        $this->assertCount(1, $records);
        
        $this->assertEquals('info', $records[0]['level']);
        $this->assertStringContainsString('Completed: File analysis', $records[0]['message']);
        
        $context = $records[0]['context'];
        $this->assertEquals('File analysis', $context['operation']);
        $this->assertEquals('complete', $context['progress_type']);
        $this->assertEquals(10, $context['files_processed']);
    }

    public function testPerformance(): void
    {
        $duration = 1.234; // seconds
        $metrics = ['files_processed' => 100, 'memory_peak' => '50MB'];
        
        $this->logger->performance('Code generation', $duration, $metrics);
        
        $records = $this->handler->getRecords();
        $this->assertCount(1, $records);
        
        $context = $records[0]['context'];
        $this->assertEquals('Code generation', $context['operation']);
        $this->assertEquals(1234.0, $context['duration_ms']);
        $this->assertEquals('performance', $context['progress_type']);
        $this->assertEquals(100, $context['files_processed']);
        $this->assertEquals('50MB', $context['memory_peak']);
    }

    public function testAddHandler(): void
    {
        $secondHandler = new TestLogHandler();
        $this->logger->addHandler($secondHandler);
        
        $this->logger->info('Test message');
        
        // Both handlers should receive the message
        $this->assertCount(1, $this->handler->getRecords());
        $this->assertCount(1, $secondHandler->getRecords());
    }

    public function testLogRecordStructure(): void
    {
        $this->logger->info('Test message', ['key' => 'value']);
        
        $records = $this->handler->getRecords();
        $record = $records[0];
        
        $this->assertArrayHasKey('timestamp', $record);
        $this->assertArrayHasKey('level', $record);
        $this->assertArrayHasKey('message', $record);
        $this->assertArrayHasKey('context', $record);
        $this->assertArrayHasKey('memory_usage', $record);
        $this->assertArrayHasKey('peak_memory', $record);
        
        $this->assertInstanceOf(\DateTime::class, $record['timestamp']);
        $this->assertEquals('info', $record['level']);
        $this->assertEquals('Test message', $record['message']);
        $this->assertEquals(['key' => 'value'], $record['context']);
        $this->assertIsInt($record['memory_usage']);
        $this->assertIsInt($record['peak_memory']);
    }

    public function testInvalidLogLevel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level: invalid');
        
        $this->logger->setMinLevel('invalid');
    }

    public function testProgressWithZeroTotal(): void
    {
        $this->logger->progress('Empty operation', 0, 0);
        
        $records = $this->handler->getRecords();
        $context = $records[0]['context'];
        
        $this->assertEquals(0, $context['percentage']);
    }
}

/**
 * Test log handler for capturing log records
 */
class TestLogHandler implements LogHandlerInterface
{
    private array $records = [];

    public function handle(array $record): void
    {
        $this->records[] = $record;
    }

    public function canHandle(string $level): bool
    {
        return true;
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