<?php
namespace Icecave\Testing\Console;

use Icecave\Testing\TypeCheck\TypeCheck;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    public function __construct() {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        parent::__construct('Icecave Testing', 'DEV');

        $this->add(new Command\PublishCommand);
    }

    private $typeCheck;
}
