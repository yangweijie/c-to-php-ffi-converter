<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Exception;

use Throwable;

/**
 * Exception thrown during C project analysis
 */
class AnalysisException extends FFIConverterException
{
    public static function headerFileNotFound(string $headerFile): self
    {
        return new self(
            message: "Header file not found: {$headerFile}",
            context: ['header_file' => $headerFile],
            recoverable: true,
            suggestion: "Check that the header file path is correct and the file exists"
        );
    }

    public static function parseError(string $file, int $line = null, string $details = null): self
    {
        $message = "Failed to parse C header file: {$file}";
        $context = ['file' => $file];
        
        if ($line !== null) {
            $message .= " at line {$line}";
            $context['line'] = $line;
        }
        
        if ($details) {
            $message .= " - {$details}";
            $context['details'] = $details;
        }

        return new self(
            message: $message,
            context: $context,
            recoverable: false,
            suggestion: "Check the C header file syntax and ensure it's valid C code"
        );
    }

    public static function dependencyResolutionFailed(string $dependency, array $searchPaths = []): self
    {
        return new self(
            message: "Failed to resolve dependency: {$dependency}",
            context: [
                'dependency' => $dependency,
                'search_paths' => $searchPaths
            ],
            recoverable: true,
            suggestion: "Ensure the dependency is available in the system include paths or add it to the search paths"
        );
    }

    public static function unsupportedConstruct(string $construct, string $file, int $line = null): self
    {
        $message = "Unsupported C construct '{$construct}' in {$file}";
        $context = ['construct' => $construct, 'file' => $file];
        
        if ($line !== null) {
            $message .= " at line {$line}";
            $context['line'] = $line;
        }

        return new self(
            message: $message,
            context: $context,
            recoverable: true,
            suggestion: "This C construct is not yet supported. Consider using a simpler alternative or file an issue"
        );
    }

    public static function libraryNotFound(string $library, array $searchPaths = []): self
    {
        return new self(
            message: "Shared library not found: {$library}",
            context: [
                'library' => $library,
                'search_paths' => $searchPaths
            ],
            recoverable: true,
            suggestion: "Ensure the library is compiled and available in the system library paths"
        );
    }
}