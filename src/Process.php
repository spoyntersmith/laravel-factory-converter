<?php declare(strict_types=1);

namespace Rdh\LaravelFactoryConverter;

use Symfony\Component\Process\Process as SymfonyProcess;

class Process
{
    public static function run(string $command): SymfonyProcess
    {
        $process = SymfonyProcess::fromShellCommandline($command);
        $process->run();

        return $process;
    }
}
