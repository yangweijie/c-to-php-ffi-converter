<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Validation\ValidationErrorReporter;
use Yangweijie\CWrapper\Validation\ValidationResult;
use Yangweijie\CWrapper\Exception\ValidationException;

class ValidationErrorReporterTest extends TestCase
{
    private ValidationErrorReporter $reporter;

    protected function setUp(): void
    {
        $this->reporter = new ValidationErrorReporter();
    }

    public function testCreateExceptionFromInvalidResult(): void
    {
        $result = new ValidationResult(false, ['Error 1', 'Error 2']);
        $exception = $this->reporter->createException($result, 'test context');

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertStringContainsString('Validation failed for test context', $exception->getMessage());
        $this->assertStringContainsString('Error 1', $exception->getMessage());
        $this->assertStringContainsString('Error 2', $exception->getMessage());
    }

    public function testCreateExceptionFromValidResult(): void
    {
        $result = new ValidationResult(true);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create exception from valid validation result');
        $this->reporter->createException($result);
    }

    public function testFormatErrorMessage(): void
    {
        $errors = ['First error', 'Second error'];
        $message = $this->reporter->formatErrorMessage($errors, 'parameter validation');

        $this->assertStringContainsString('Validation failed for parameter validation', $message);
        $this->assertStringContainsString('1. First error', $message);
        $this->assertStringContainsString('2. Second error', $message);
    }

    public function testFormatErrorMessageWithoutContext(): void
    {
        $errors = ['Single error'];
        $message = $this->reporter->formatErrorMessage($errors);

        $this->assertStringContainsString('Validation failed:', $message);
        $this->assertStringContainsString('1. Single error', $message);
    }

    public function testFormatErrorMessageEmpty(): void
    {
        $message = $this->reporter->formatErrorMessage([]);
        $this->assertEquals('Validation failed', $message);
    }

    public function testCreateFunctionParameterReport(): void
    {
        $result = new ValidationResult(false, ['Parameter 0: Type error', 'Parameter 1: Range error']);
        $report = $this->reporter->createFunctionParameterReport(
            $result,
            'test_function',
            ['param1', 'param2'],
            ['int', 'char*']
        );

        $this->assertStringContainsString('Parameter validation failed for function test_function', $report);
        $this->assertStringContainsString('test_function(int param1, char* param2)', $report);
        $this->assertStringContainsString('Parameter 0: Type error', $report);
        $this->assertStringContainsString('Parameter 1: Range error', $report);
    }

    public function testCreateFunctionParameterReportValid(): void
    {
        $result = new ValidationResult(true);
        $report = $this->reporter->createFunctionParameterReport($result, 'test_function');

        $this->assertEquals('All parameters valid for function test_function', $report);
    }  
  public function testCreateSuggestion(): void
    {
        $result = new ValidationResult(false, ['Type validation failed for int']);
        $suggestion = $this->reporter->createSuggestion($result, 'int');

        $this->assertStringContainsString('Suggestions:', $suggestion);
        $this->assertStringContainsString('correct PHP type', $suggestion);
    }

    public function testCreateSuggestionForRangeError(): void
    {
        $result = new ValidationResult(false, ['Value is below minimum']);
        $suggestion = $this->reporter->createSuggestion($result, 'unsigned char');

        $this->assertStringContainsString('within the valid range', $suggestion);
    }

    public function testCreateSuggestionForParameterCount(): void
    {
        $result = new ValidationResult(false, ['Parameter count mismatch']);
        $suggestion = $this->reporter->createSuggestion($result, 'int');

        $this->assertStringContainsString('correct number of parameters', $suggestion);
    }

    public function testCreateSuggestionForConversionError(): void
    {
        $result = new ValidationResult(false, ['Float value cannot be safely converted to integer']);
        $suggestion = $this->reporter->createSuggestion($result, 'int');

        $this->assertStringContainsString('explicit type casting', $suggestion);
    }

    public function testCreateSuggestionForUnknownError(): void
    {
        $result = new ValidationResult(false, ['Some unknown error']);
        $suggestion = $this->reporter->createSuggestion($result, 'custom_type');

        $this->assertStringContainsString('Review the parameter requirements', $suggestion);
    }

    public function testCreateSuggestionForValidResult(): void
    {
        $result = new ValidationResult(true);
        $suggestion = $this->reporter->createSuggestion($result, 'int');

        $this->assertEquals('', $suggestion);
    }

    public function testCreateFunctionParameterReportWithoutTypes(): void
    {
        $result = new ValidationResult(false, ['Some error']);
        $report = $this->reporter->createFunctionParameterReport($result, 'test_function');

        $this->assertStringContainsString('Parameter validation failed for function test_function', $report);
        $this->assertStringNotContainsString('Expected signature:', $report);
        $this->assertStringContainsString('Some error', $report);
    }

    public function testCreateFunctionParameterReportWithMismatchedArrays(): void
    {
        $result = new ValidationResult(false, ['Error']);
        $report = $this->reporter->createFunctionParameterReport(
            $result,
            'test_function',
            ['param1'], // Only one parameter name
            ['int', 'char*'] // But two types
        );

        $this->assertStringContainsString('test_function(int param1, char* param1)', $report);
    }
}