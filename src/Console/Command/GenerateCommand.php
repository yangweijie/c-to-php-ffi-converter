<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yangweijie\CWrapper\Console\CommandInterface;
use Yangweijie\CWrapper\Config\ConfigLoader;
use Yangweijie\CWrapper\Config\ProjectConfig;
use Yangweijie\CWrapper\Config\ValidationConfig;
use Yangweijie\CWrapper\Exception\ConfigurationException;
use Yangweijie\CWrapper\Exception\ValidationException;

/**
 * Main command for generating PHP FFI wrapper classes from C projects
 */
class GenerateCommand extends Command implements CommandInterface
{
    protected static $defaultName = 'generate';
    protected static $defaultDescription = 'Generate PHP FFI wrapper classes from C header files';

    protected function configure(): void
    {
        $this
            ->setName('generate')
            ->setDescription('Generate PHP FFI wrapper classes from C header files')
            ->setHelp('This command generates PHP FFI wrapper classes from C header files using klitsche/ffigen')
            ->addArgument(
                'header-files',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Path(s) to C header files to process'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Output directory for generated wrapper classes',
                './generated'
            )
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_REQUIRED,
                'PHP namespace for generated classes',
                'Generated\\FFI'
            )
            ->addOption(
                'library',
                'l',
                InputOption::VALUE_REQUIRED,
                'Path to shared library file (.so, .dll, .dylib)'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                'Path to configuration file (YAML format)'
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Patterns to exclude from generation',
                []
            )
            ->addOption(
                'validation',
                null,
                InputOption::VALUE_NONE,
                'Enable parameter validation in generated wrappers'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite existing files without confirmation'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            $io->title('C-to-PHP FFI Converter');
            
            // Build project configuration from CLI arguments
            $projectConfig = $this->buildProjectConfig($input);
            
            // Validate the configuration
            $this->validateProjectConfig($projectConfig, $io);
            
            // Display configuration summary
            $this->displayConfiguration($projectConfig, $io);
            
            // TODO: Implement actual generation logic in future tasks
            $io->success('Configuration validated successfully!');
            $io->note('Generation logic will be implemented in upcoming tasks.');
            
            return Command::SUCCESS;
            
        } catch (ConfigurationException $e) {
            $io->error('Configuration Error: ' . $e->getMessage());
            return Command::FAILURE;
        } catch (ValidationException $e) {
            $io->error('Validation Error: ' . $e->getMessage());
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $io->error('Unexpected Error: ' . $e->getMessage());
            if ($output->isVerbose()) {
                $io->writeln($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    /**
     * Build ProjectConfig from CLI input
     */
    private function buildProjectConfig(InputInterface $input): ProjectConfig
    {
        $configFile = $input->getOption('config');
        
        // Start with config file if provided, otherwise use defaults
        if ($configFile) {
            $configLoader = new ConfigLoader();
            $projectConfig = $configLoader->loadFromFile($configFile);
        } else {
            $projectConfig = new ProjectConfig();
        }
        
        // Override with CLI arguments
        $headerFiles = $input->getArgument('header-files');
        if (!empty($headerFiles)) {
            $projectConfig->setHeaderFiles($headerFiles);
        }
        
        // Only override config file values if explicitly provided via CLI
        if ($input->hasParameterOption(['--output', '-o'])) {
            $outputDir = $input->getOption('output');
            $projectConfig->setOutputPath($outputDir);
        }
        
        if ($input->hasParameterOption('--namespace')) {
            $namespace = $input->getOption('namespace');
            $projectConfig->setNamespace($namespace);
        }
        
        if ($input->hasParameterOption(['--library', '-l'])) {
            $libraryFile = $input->getOption('library');
            $projectConfig->setLibraryFile($libraryFile);
        }
        
        if ($input->hasParameterOption('--exclude')) {
            $excludePatterns = $input->getOption('exclude');
            $projectConfig->setExcludePatterns($excludePatterns);
        }
        
        // Handle validation option
        $enableValidation = $input->getOption('validation');
        if ($enableValidation) {
            $validationConfig = $projectConfig->getValidationConfig();
            $validationConfig->setParameterValidation(true);
            $projectConfig->setValidationConfig($validationConfig);
        }
        
        return $projectConfig;
    }

    /**
     * Validate project configuration
     */
    private function validateProjectConfig(ProjectConfig $projectConfig, SymfonyStyle $io): void
    {
        $headerFiles = $projectConfig->getHeaderFiles();
        $outputDir = $projectConfig->getOutputPath();
        $libraryFile = $projectConfig->getLibraryFile();
        $namespace = $projectConfig->getNamespace();

        // Validate header files exist and are readable
        if (empty($headerFiles)) {
            throw new ConfigurationException("No header files specified");
        }
        
        foreach ($headerFiles as $headerFile) {
            if (!file_exists($headerFile)) {
                throw new ConfigurationException("Header file not found: {$headerFile}");
            }
            if (!is_readable($headerFile)) {
                throw new ConfigurationException("Header file not readable: {$headerFile}");
            }
        }

        // Validate output directory is writable or can be created
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
                throw new ConfigurationException("Cannot create output directory: {$outputDir}");
            }
        }
        if (!is_writable($outputDir)) {
            throw new ConfigurationException("Output directory not writable: {$outputDir}");
        }

        // Validate library file if provided
        if ($libraryFile && !file_exists($libraryFile)) {
            throw new ConfigurationException("Library file not found: {$libraryFile}");
        }

        // Validate namespace format
        if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*$/', $namespace)) {
            throw new ValidationException("Invalid namespace format: {$namespace}");
        }
    }

    /**
     * Display configuration summary to user
     */
    private function displayConfiguration(ProjectConfig $projectConfig, SymfonyStyle $io): void
    {
        $headerFiles = $projectConfig->getHeaderFiles();
        $outputDir = $projectConfig->getOutputPath();
        $namespace = $projectConfig->getNamespace();
        $libraryFile = $projectConfig->getLibraryFile();
        $excludePatterns = $projectConfig->getExcludePatterns();
        $validationConfig = $projectConfig->getValidationConfig();

        $io->section('Configuration Summary');
        
        $io->definitionList(
            ['Header Files' => implode(', ', $headerFiles)],
            ['Output Directory' => $outputDir],
            ['Namespace' => $namespace],
            ['Library File' => $libraryFile ?: 'Not specified'],
            ['Exclude Patterns' => empty($excludePatterns) ? 'None' : implode(', ', $excludePatterns)],
            ['Parameter Validation' => $validationConfig->isParameterValidationEnabled() ? 'Enabled' : 'Disabled'],
            ['Type Conversion' => $validationConfig->isTypeConversionEnabled() ? 'Enabled' : 'Disabled']
        );
    }
}