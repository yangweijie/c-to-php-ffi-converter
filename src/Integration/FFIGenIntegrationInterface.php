<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Integration;

use Yangweijie\CWrapper\Config\ConfigInterface;

/**
 * Interface for FFIGen integration
 */
interface FFIGenIntegrationInterface
{
    /**
     * Generate bindings using klitsche/ffigen
     *
     * @param ConfigInterface $config Project configuration
     * @return BindingResult Generated bindings result
     */
    public function generateBindings(ConfigInterface $config): BindingResult;

    /**
     * Process generated bindings from klitsche/ffigen
     *
     * @param BindingResult $result Raw binding result
     * @return ProcessedBindings Processed bindings
     */
    public function processBindings(BindingResult $result): ProcessedBindings;
}