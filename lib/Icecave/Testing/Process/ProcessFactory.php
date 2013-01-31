<?php
namespace Icecave\Testing\Process;

use Symfony\Component\Process\Process;

class ProcessFactory
{
    /**
     * @param string $executablePath
     * @param string $argument,...
     *
     * @return Process
     */
    public function create($executablePath)
    {
        return static::createFromArray(func_get_args());
    }

    /**
     * @param array<string> $arguments
     *
     * @return Process
     */
    public function createFromArray(array $arguments)
    {
        return new Process(
            implode(' ', array_map('escapeshellarg', $arguments))
        );
    }
}
