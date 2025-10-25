<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface for console commands
 */
interface CommandInterface
{
    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Exit code
     */
    public function execute(InputInterface $input, OutputInterface $output): int;
}