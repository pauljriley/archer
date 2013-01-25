<?php
namespace Icecave\Woodhouse\Console;

use Icecave\Woodhouse\TypeCheck\TypeCheck;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    /**
     * @param string $vendorPath The path to the composer vendor folder.
     */
    public function __construct($vendorPath)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->vendorPath = $vendorPath;

        parent::__construct('Woodhouse', 'DEV');

        $this->add(new Command\GitHub\PublishCommand);
        // $this->add(new Command\GitHub\GetTokenCommand);
    }

    public function vendorPath()
    {
        return $this->vendorPath;
    }

    private $typeCheck;
    private $vendorPath;
}
