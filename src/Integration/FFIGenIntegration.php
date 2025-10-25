<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Integration;

use Yangweijie\CWrapper\Config\ConfigInterface;

/**
 * Main FFIGen integration implementation
 */
class FFIGenIntegration implements FFIGenIntegrationInterface
{
    private FFIGenRunner $runner;
    private BindingProcessor $processor;

    public function __construct(
        ?FFIGenRunner $runner = null,
        ?BindingProcessor $processor = null
    ) {
        $this->runner = $runner ?? new FFIGenRunner();
        $this->processor = $processor ?? new BindingProcessor();
    }

    /**
     * Generate bindings using klitsche/ffigen
     *
     * @param ConfigInterface $config Project configuration
     * @return BindingResult Generated bindings result
     */
    public function generateBindings(ConfigInterface $config): BindingResult
    {
        return $this->runner->run($config);
    }

    /**
     * Process generated bindings from klitsche/ffigen
     *
     * @param BindingResult $result Raw binding result
     * @return ProcessedBindings Processed bindings
     */
    public function processBindings(BindingResult $result): ProcessedBindings
    {
        return $this->processor->process($result);
    }
}