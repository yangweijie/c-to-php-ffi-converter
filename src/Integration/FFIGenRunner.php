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
                $errors = [];
                if ($process->getErrorOutput()) {
                    $errors[] = 'Error output: ' . $process->getErrorOutput();
                }
                if ($process->getOutput()) {
                    $errors[] = 'Standard output: ' . $process->getOutput();
                }
                $errors[] = 'Exit code: ' . $process->getExitCode();
                
                return new BindingResult(
                    '',
                    '',
                    false,
                    $errors
                );
            }
            
            // Check if expected files were actually generated
            $outputPath = $config->getOutputPath();
            $constantsFile = $outputPath . '/constants.php';
            $methodsFile = $outputPath . '/Methods.php';
            
            if (!file_exists($constantsFile) || !file_exists($methodsFile)) {
                $errors = ['Generated files not found'];
                if ($process->getOutput()) {
                    $errors[] = 'FFIGen output: ' . $process->getOutput();
                }
                if ($process->getErrorOutput()) {
                    $errors[] = 'FFIGen error: ' . $process->getErrorOutput();
                }
                
                return new BindingResult(
                    $constantsFile,
                    $methodsFile,
                    false,
                    $errors
                );
            }
            
            // Return successful result with file paths
            return new BindingResult(
                $constantsFile,
                $methodsFile,
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
        try {
            return $this->configBuilder->writeTemporaryConfigFile($config);
        } catch (\Exception $e) {
            throw new GenerationException('Failed to create temporary configuration file: ' . $e->getMessage(), 0, $e);
        }
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
            ['vendor/bin/ffigen', 'generate', '-c', $configFile],
            ['./vendor/bin/ffigen', 'generate', '-c', $configFile],
            ['php', 'vendor/bin/ffigen', 'generate', '-c', $configFile],
        ];
        
        $lastError = '';
        
        foreach ($possibleCommands as $command) {
            try {
                $process = new Process($command);
                $process->setTimeout(300); // 5 minutes timeout
                $process->run();
                
                // Return the process regardless of success/failure
                // The caller will check isSuccessful()
                return $process;
                
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                continue;
            }
        }
        
        throw new GenerationException(
            'Could not execute klitsche/ffigen. Make sure it is installed via Composer. Last error: ' . $lastError
        );
    }
}