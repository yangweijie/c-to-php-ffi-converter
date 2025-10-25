<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Logging;

/**
 * Interface for log handlers
 */
interface LogHandlerInterface
{
    /**
     * Handle a log record
     */
    public function handle(array $record): void;

    /**
     * Check if the handler can handle the given log level
     */
    public function canHandle(string $level): bool;
}