<?php
namespace Icecave\Testing\Support;

use Icecave\Testing\Support\Isolator;
use Exception;

class ShellExecutor
{
    public function __construct(Isolator $isolator = null)
    {
        $this->isolator = Isolator::get($isolator);
    }

    public function execute($command, $workingDir = '.')
    {
        $cwd = $this->isolator->getcwd();

        if ('.' !== $workingDir) {
            $this->isolator->chdir($workingDir);
        }

        try {
            $exitCode = null;
            $output = null;
            $this->isolator->exec($command, $output, $exitCode);
            if (0 !== $exitCode) {
                throw new Exception\ExecutionException($command, $exitCode, $output);
            }
            $this->isolator->chdir($cwd);
        } catch (Exception $e) {
            $this->isolator->chdir($cwd);
            throw $e;
        }

        return $output;
    }

    private $isolator;
}
