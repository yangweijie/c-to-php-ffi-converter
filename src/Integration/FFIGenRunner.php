<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Integration;

use Yangweijie\CWrapper\Config\ConfigInterface;
use Yangweijie\CWrapper\Exception\GenerationException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Executes klitsche/ffigen with generated configuration
 */
class FFIGenRunner
{
    private FFIGenConfigurationBuilder $configBuilder;

    public function __construct(?FFIGenConfigurationBuilder $configBuilder = null)
    {
        $this->configBuilder = $configBuilder ?? new FFIGenConfigurationBuilder();
    }

    /**
     * Execute klitsche/ffigen with the provided configuration
     *
     * @param ConfigInterface $config Project configuration
     * @return BindingResult Result of FFIGen execution
     * @throws GenerationException If FFIGen execution fails
     */
    public function run(ConfigInterface $config): BindingResult
    {
        try {
            // Create temporary configuration file
            $configFile = $this->createTemporaryConfigFile($config);
            
            // Ensure output directory exists
            $this->ensureOutputDirectory($config->getOutputPath());
            
            // Execute FFIGen
            $process = $this->executeFFIGen($configFile);
            
            // Clean up temporary file
            unlink($configFile);
            
            // Check if execution was successful
            if (!$process->isSuccessful()) {
                return new BindingResult(
                    '',
                    '',
                    false,
                    [$process->getErrorOutput()]
                );
            }
            
            // Return successful result with file paths
            $outputPath = $config->getOutputPath();
            return new BindingResult(
                $outputPath . '/constants.php',
                $outputPath . '/Methods.php',
                true
            );
            
        } catch (\Exception $e) {
            throw new GenerationException(
                'Failed to execute klitsche/ffigen: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Create a temporary configuration file for FFIGen
     *
     * @param ConfigInterface $config Project configuration
     * @return string Path to temporary configuration file
     * @throws GenerationException If file creation fails
     */
    private function createTemporaryConfigFile(ConfigInterface $config): string
    {
        $yamlConfig = $this->configBuilder->buildYamlConfiguration($config);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'ffigen_config_') . '.yaml';
        
        if ($tempFile === false || file_put_contents($tempFile, $yamlConfig) === false) {
            throw new GenerationException('Failed to create temporary configuration file');
        }
        
        return $tempFile;
    }

    /**
     * Ensure the output directory exists
     *
     * @param string $outputPath Output directory path
     * @throws GenerationException If directory creation fails
     */
    private function ensureOutputDirectory(string $outputPath): void
    {
        if (!is_dir($outputPath) && !mkdir($outputPath, 0755, true) && !is_dir($outputPath)) {
            throw new GenerationException("Failed to create output directory: {$outputPath}");
        }
    }

    /**
     * Execute the FFIGen process
     *
     * @param string $configFile Path to configuration file
     * @return Process The executed process
     * @throws GenerationException If process execution fails
     */
    private function executeFFIGen(string $configFile): Process
    {
        // Try different possible FFIGen executable locations
        $possibleCommands = [
            ['vendor/bin/ffigen', $configFile],
            ['./vendor/bin/ffigen', $configFile],
            ['ffigen', $configFile],
            ['php', 'vendor/bin/ffigen', $configFile],
        ];
        
        $lastException = null;
        
        foreach ($possibleCommands as $command) {
            try {
                $process = new Process($command);
                $process->setTimeout(300); // 5 minutes timeout
                $process->run();
                
                return $process;
            } catch (ProcessFailedException $e) {
                $lastException = $e;
                continue;
            }
        }
        
        throw new GenerationException(
            'Could not execute klitsche/ffigen. Make sure it is installed via Composer.',
            0,
            $lastException
        );
    }
}