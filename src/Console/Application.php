<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yangweijie\CWrapper\Console\Command\GenerateCommand;

/**
 * Main console application for C-to-PHP FFI Converter
 */
class Application extends BaseApplication
{
    private const NAME = 'C-to-PHP FFI Converter';
    private const VERSION = '1.0.0';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);
        
        $this->addCommands([
            new GenerateCommand(),
        ]);
        
        // Set the default command to generate
        $this->setDefaultCommand('generate', true);
    }

    /**
     * Handle uncaught exceptions and provide user-friendly error messages
     */
    public function renderThrowable(\Throwable $e, OutputInterface $output): void
    {
        $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
        
        if ($output->isVerbose()) {
            $output->writeln('<comment>Stack trace:</comment>');
            $output->writeln($e->getTraceAsString());
        } else {
            $output->writeln('<comment>Use -v for more details</comment>');
        }
    }

    /**
     * Configure error handling for the application
     */
    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        try {
            return parent::run($input, $output);
        } catch (\Throwable $e) {
            if ($output !== null) {
                $this->renderThrowable($e, $output);
            }
            return 1;
        }
    }
}