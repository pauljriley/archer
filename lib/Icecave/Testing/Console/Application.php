<?php
namespace Icecave\Testing\Console;

use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    public function __construct($packageRoot)
    {
        parent::__construct('Icecave Testing', '2.1.0-dev');

        $this->packageRoot = $packageRoot;

        $this->add(new Command\Internal\UpdateBundledPackagesCommand);
    }

    public function packageRoot()
    {
        return $this->packageRoot;
    }

    private $packageRoot;
}
