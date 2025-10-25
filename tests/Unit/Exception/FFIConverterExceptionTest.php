<?php

declare(strict_types=1);

namespace Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Exception\FFIConverterException;

class FFIConverterExceptionTest extends TestCase
{
    private TestableFFIConverterException $exception;

    protected function setUp(): void
    {
        $this->exception = new TestableFFIConverterException(
            'Test error message',
            100,
            null,
            ['key1' => 'value1', 'key2' => 'value2'],
            ['debug_key' => 'debug_value'],
            true,
            'This is a test suggestion'
        );
    }

    public function testConstructorSetsAllProperties(): void
    {
        $this->assertEquals('Test error message', $this->exception->getMessage());
        $this->assertEquals(100, $this->exception->getCode());
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $this->exception->getContext());
        $this->assertEquals(['debug_key' => 'debug_value'], $this->exception->getDebugInfo());
        $this->assertTrue($this->exception->isRecoverable());
        $this->assertEquals('This is a test suggestion', $this->exception->getSuggestion());
    }

    public function testAddContext(): void
    {
        $this->exception->addContext('new_key', 'new_value');
        
        $context = $this->exception->getContext();
        $this->assertArrayHasKey('new_key', $context);
        $this->assertEquals('new_value', $context['new_key']);
        $this->assertEquals('value1', $context['key1']); // Original context preserved
    }

    public function testAddDebugInfo(): void
    {
        $this->exception->addDebugInfo('new_debug', 'debug_data');
        
        $debugInfo = $this->exception->getDebugInfo();
        $this->assertArrayHasKey('new_debug', $debugInfo);
        $this->assertEquals('debug_data', $debugInfo['new_debug']);
        $this->assertEquals('debug_value', $debugInfo['debug_key']); // Original debug info preserved
    }

    public function testSetRecoverable(): void
    {
        $this->assertTrue($this->exception->isRecoverable());
        
        $this->exception->setRecoverable(false);
        $this->assertFalse($this->exception->isRecoverable());
        
        $this->exception->setRecoverable(true);
        $this->assertTrue($this->exception->isRecoverable());
    }

    public function testSetSuggestion(): void
    {
        $newSuggestion = 'Updated suggestion';
        $this->exception->setSuggestion($newSuggestion);
        
        $this->assertEquals($newSuggestion, $this->exception->getSuggestion());
    }

    public function testGetFormattedError(): void
    {
        $formatted = $this->exception->getFormattedError();
        
        $this->assertIsArray($formatted);
        $this->assertArrayHasKey('type', $formatted);
        $this->assertArrayHasKey('message', $formatted);
        $this->assertArrayHasKey('code', $formatted);
        $this->assertArrayHasKey('file', $formatted);
        $this->assertArrayHasKey('line', $formatted);
        $this->assertArrayHasKey('context', $formatted);
        $this->assertArrayHasKey('debug_info', $formatted);
        $this->assertArrayHasKey('recoverable', $formatted);
        $this->assertArrayHasKey('suggestion', $formatted);
        $this->assertArrayHasKey('trace', $formatted);
        
        $this->assertEquals(TestableFFIConverterException::class, $formatted['type']);
        $this->assertEquals('Test error message', $formatted['message']);
        $this->assertEquals(100, $formatted['code']);
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $formatted['context']);
        $this->assertEquals(['debug_key' => 'debug_value'], $formatted['debug_info']);
        $this->assertTrue($formatted['recoverable']);
        $this->assertEquals('This is a test suggestion', $formatted['suggestion']);
    }

    public function testCreateErrorReport(): void
    {
        $report = $this->exception->createErrorReport();
        
        $this->assertIsString($report);
        $this->assertStringContainsString('Error Type: ' . TestableFFIConverterException::class, $report);
        $this->assertStringContainsString('Message: Test error message', $report);
        $this->assertStringContainsString('Context:', $report);
        $this->assertStringContainsString('key1: value1', $report);
        $this->assertStringContainsString('Debug Information:', $report);
        $this->assertStringContainsString('debug_key: debug_value', $report);
        $this->assertStringContainsString('Suggestion: This is a test suggestion', $report);
        $this->assertStringContainsString('Recoverable: Yes', $report);
    }

    public function testCreateErrorReportWithComplexValues(): void
    {
        $this->exception->addContext('array_value', ['nested' => 'data']);
        $this->exception->addContext('object_value', new \stdClass());
        
        $report = $this->exception->createErrorReport();
        
        $this->assertStringContainsString('array_value:', $report);
        $this->assertStringContainsString('object_value: stdClass', $report);
    }

    public function testMinimalConstructor(): void
    {
        $exception = new TestableFFIConverterException('Simple message');
        
        $this->assertEquals('Simple message', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertEquals([], $exception->getContext());
        $this->assertEquals([], $exception->getDebugInfo());
        $this->assertFalse($exception->isRecoverable());
        $this->assertNull($exception->getSuggestion());
    }
}

/**
 * Testable concrete implementation of FFIConverterException
 */
class TestableFFIConverterException extends FFIConverterException
{
    // Concrete implementation for testing
}