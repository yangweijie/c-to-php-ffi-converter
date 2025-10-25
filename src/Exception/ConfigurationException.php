<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Exception;

use Throwable;

/**
 * Exception for configuration-related errors
 */
class ConfigurationException extends FFIConverterException
{
    public static function missingRequiredField(string $field, string $configFile = null): self
    {
        $message = "Missing required configuration field: {$field}";
        $context = ['field' => $field];
        $suggestion = "Add the '{$field}' field to your configuration";
        
        if ($configFile) {
            $message .= " in {$configFile}";
            $context['config_file'] = $configFile;
            $suggestion .= " file: {$configFile}";
        }

        return new self(
            message: $message,
            context: $context,
            recoverable: true,
            suggestion: $suggestion
        );
    }

    public static function invalidConfigurationFile(string $file, string $reason = null): self
    {
        $message = "Invalid configuration file: {$file}";
        if ($reason) {
            $message .= " - {$reason}";
        }

        return new self(
            message: $message,
            context: ['file' => $file, 'reason' => $reason],
            recoverable: true,
            suggestion: "Check the configuration file format and syntax"
        );
    }

    public static function invalidPath(string $path, string $type = 'path'): self
    {
        return new self(
            message: "Invalid {$type}: {$path}",
            context: ['path' => $path, 'type' => $type],
            recoverable: true,
            suggestion: "Ensure the {$type} exists and is accessible"
        );
    }

    public static function unsupportedConfigurationVersion(string $version, array $supportedVersions): self
    {
        return new self(
            message: "Unsupported configuration version: {$version}",
            context: [
                'version' => $version,
                'supported_versions' => $supportedVersions
            ],
            recoverable: true,
            suggestion: "Use one of the supported versions: " . implode(', ', $supportedVersions)
        );
    }
}