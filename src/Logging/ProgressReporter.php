<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Logging;

/**
 * Progress reporter for long-running operations
 */
class ProgressReporter
{
    private LoggerInterface $logger;
    private array $operations = [];
    private int $reportInterval = 10; // Report every 10% by default

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Start tracking progress for an operation
     */
    public function startOperation(string $operationId, string $description, int $totalSteps): void
    {
        $this->operations[$operationId] = [
            'description' => $description,
            'total_steps' => $totalSteps,
            'current_step' => 0,
            'start_time' => microtime(true),
            'last_report_percentage' => 0,
            'substeps' => []
        ];

        $this->logger->startOperation($description, [
            'operation_id' => $operationId,
            'total_steps' => $totalSteps
        ]);
    }

    /**
     * Update progress for an operation
     */
    public function updateProgress(string $operationId, int $currentStep, string $stepDescription = null): void
    {
        if (!isset($this->operations[$operationId])) {
            return;
        }

        $operation = &$this->operations[$operationId];
        $operation['current_step'] = $currentStep;
        
        if ($stepDescription) {
            $operation['current_step_description'] = $stepDescription;
        }

        $percentage = $operation['total_steps'] > 0 
            ? round(($currentStep / $operation['total_steps']) * 100, 1) 
            : 0;

        // Report progress at intervals or on completion
        if ($this->shouldReportProgress($operation, $percentage)) {
            $this->reportProgress($operationId, $operation, $percentage, $stepDescription);
            $operation['last_report_percentage'] = floor($percentage / $this->reportInterval) * $this->reportInterval;
        }
    }

    /**
     * Add a substep to track detailed progress
     */
    public function addSubstep(string $operationId, string $substepId, string $description, int $totalItems): void
    {
        if (!isset($this->operations[$operationId])) {
            return;
        }

        $this->operations[$operationId]['substeps'][$substepId] = [
            'description' => $description,
            'total_items' => $totalItems,
            'current_item' => 0,
            'start_time' => microtime(true)
        ];
    }

    /**
     * Update substep progress
     */
    public function updateSubstep(string $operationId, string $substepId, int $currentItem, string $itemDescription = null): void
    {
        if (!isset($this->operations[$operationId]['substeps'][$substepId])) {
            return;
        }

        $substep = &$this->operations[$operationId]['substeps'][$substepId];
        $substep['current_item'] = $currentItem;
        
        if ($itemDescription) {
            $substep['current_item_description'] = $itemDescription;
        }

        $percentage = $substep['total_items'] > 0 
            ? round(($currentItem / $substep['total_items']) * 100, 1) 
            : 0;

        // Report substep progress for detailed operations
        if ($substep['total_items'] > 100 && $currentItem % 10 === 0) {
            $this->logger->debug("Substep progress: {$substep['description']}", [
                'operation_id' => $operationId,
                'substep_id' => $substepId,
                'current' => $currentItem,
                'total' => $substep['total_items'],
                'percentage' => $percentage,
                'item_description' => $itemDescription
            ]);
        }
    }

    /**
     * Complete an operation
     */
    public function completeOperation(string $operationId, array $summary = []): void
    {
        if (!isset($this->operations[$operationId])) {
            return;
        }

        $operation = $this->operations[$operationId];
        $duration = microtime(true) - $operation['start_time'];

        $context = array_merge($summary, [
            'operation_id' => $operationId,
            'total_steps' => $operation['total_steps'],
            'duration_seconds' => round($duration, 2),
            'steps_per_second' => $operation['total_steps'] > 0 ? round($operation['total_steps'] / $duration, 2) : 0
        ]);

        $this->logger->completeOperation($operation['description'], $context);
        $this->logger->performance($operation['description'], $duration, [
            'total_steps' => $operation['total_steps'],
            'substeps_count' => count($operation['substeps'])
        ]);

        unset($this->operations[$operationId]);
    }

    /**
     * Fail an operation
     */
    public function failOperation(string $operationId, string $reason, array $context = []): void
    {
        if (!isset($this->operations[$operationId])) {
            return;
        }

        $operation = $this->operations[$operationId];
        $duration = microtime(true) - $operation['start_time'];

        $this->logger->error("Operation failed: {$operation['description']}", array_merge($context, [
            'operation_id' => $operationId,
            'reason' => $reason,
            'completed_steps' => $operation['current_step'],
            'total_steps' => $operation['total_steps'],
            'duration_seconds' => round($duration, 2)
        ]));

        unset($this->operations[$operationId]);
    }

    /**
     * Get current progress for an operation
     */
    public function getProgress(string $operationId): ?array
    {
        if (!isset($this->operations[$operationId])) {
            return null;
        }

        $operation = $this->operations[$operationId];
        $percentage = $operation['total_steps'] > 0 
            ? round(($operation['current_step'] / $operation['total_steps']) * 100, 1) 
            : 0;

        return [
            'operation_id' => $operationId,
            'description' => $operation['description'],
            'current_step' => $operation['current_step'],
            'total_steps' => $operation['total_steps'],
            'percentage' => $percentage,
            'elapsed_time' => microtime(true) - $operation['start_time'],
            'substeps' => array_map(function($substep) {
                return [
                    'description' => $substep['description'],
                    'current_item' => $substep['current_item'],
                    'total_items' => $substep['total_items'],
                    'percentage' => $substep['total_items'] > 0 
                        ? round(($substep['current_item'] / $substep['total_items']) * 100, 1) 
                        : 0
                ];
            }, $operation['substeps'])
        ];
    }

    /**
     * Get all active operations
     */
    public function getActiveOperations(): array
    {
        return array_keys($this->operations);
    }

    /**
     * Set the progress report interval (percentage)
     */
    public function setReportInterval(int $interval): void
    {
        $this->reportInterval = max(1, min(100, $interval));
    }

    /**
     * Check if progress should be reported
     */
    private function shouldReportProgress(array $operation, float $percentage): bool
    {
        // Always report completion
        if ($operation['current_step'] >= $operation['total_steps']) {
            return true;
        }

        // Report at intervals
        $currentReportLevel = floor($percentage / $this->reportInterval) * $this->reportInterval;
        return $currentReportLevel > $operation['last_report_percentage'];
    }

    /**
     * Report progress to the logger
     */
    private function reportProgress(string $operationId, array $operation, float $percentage, string $stepDescription = null): void
    {
        $context = [
            'operation_id' => $operationId,
            'current_step' => $operation['current_step'],
            'total_steps' => $operation['total_steps'],
            'percentage' => $percentage
        ];

        if ($stepDescription) {
            $context['step_description'] = $stepDescription;
        }

        if (isset($operation['current_step_description'])) {
            $context['current_step_description'] = $operation['current_step_description'];
        }

        $this->logger->progress($operation['description'], $operation['current_step'], $operation['total_steps'], $context);
    }
}