<?php
namespace Icecave\Testing\Console;

use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    public function __construct($packageRoot)
    {
        parent::__construct('Icecave Testing', '3.0.0-dev');

        $this->packageRoot = $packageRoot;

        $this->add(new Command\BuildCommand);
        $this->add(new Command\InitializeCommand);
        $this->add(new Command\UpdateCommand);

        $this->add(new Command\GitHub\CreateTokenCommand);
        $this->add(new Command\GitHub\FetchTokenCommand);
        $this->add(new Command\GitHub\SetTokenCommand);

        $this->add(new Command\Travis\FetchPublicKeyCommand);
        $this->add(new Command\Travis\UpdateConfigCommand);

        $this->add(new Command\Internal\UpdateBinariesCommand);
    }

    public function packageRoot()
    {
        return $this->packageRoot;
    }

    private $packageRoot;
}
