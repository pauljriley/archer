<?php
namespace Icecave\Testing\Console;

use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    public function __construct($packageRoot)
    {
        parent::__construct('Icecave Testing', '3.0.0-dev');

        $this->packageRoot = $packageRoot;

        $this->add(new Command\InitializeCommand);
        $this->add(new Command\UpdateBinariesCommand);
    }

    public function packageRoot()
    {
        return $this->packageRoot;
    }

    private $packageRoot;
}
