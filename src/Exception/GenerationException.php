<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Exception;

use Throwable;

/**
 * Exception for generation-related errors
 */
class GenerationException extends FFIConverterException
{
    public static function templateNotFound(string $template): self
    {
        return new self(
            message: "Template not found: {$template}",
            context: ['template' => $template],
            recoverable: false,
            suggestion: "Ensure the template file exists in the templates directory"
        );
    }

    public static function templateRenderingFailed(string $template, string $error): self
    {
        return new self(
            message: "Failed to render template '{$template}': {$error}",
            context: ['template' => $template, 'error' => $error],
            recoverable: false,
            suggestion: "Check the template syntax and ensure all required variables are provided"
        );
    }

    public static function outputDirectoryNotWritable(string $directory): self
    {
        return new self(
            message: "Output directory is not writable: {$directory}",
            context: ['directory' => $directory],
            recoverable: true,
            suggestion: "Check directory permissions and ensure the path exists"
        );
    }

    public static function fileWriteFailed(string $file, string $reason = null): self
    {
        $message = "Failed to write file: {$file}";
        $context = ['file' => $file];
        
        if ($reason) {
            $message .= " - {$reason}";
            $context['reason'] = $reason;
        }

        return new self(
            message: $message,
            context: $context,
            recoverable: true,
            suggestion: "Check file permissions and available disk space"
        );
    }

    public static function invalidTypeMapping(string $cType, string $context = null): self
    {
        $message = "Invalid type mapping for C type: {$cType}";
        $contextData = ['c_type' => $cType];
        
        if ($context) {
            $message .= " in {$context}";
            $contextData['context'] = $context;
        }

        return new self(
            message: $message,
            context: $contextData,
            recoverable: true,
            suggestion: "Add a custom type mapping for this C type or use a supported type"
        );
    }

    public static function codeGenerationFailed(string $component, string $reason): self
    {
        return new self(
            message: "Code generation failed for {$component}: {$reason}",
            context: ['component' => $component, 'reason' => $reason],
            recoverable: false,
            suggestion: "Check the input data and template configuration"
        );
    }
}