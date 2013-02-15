<?php
namespace Icecave\Archer\Console\Command\Travis;

use Icecave\Archer\Support\Isolator;
use Symfony\Component\Console\Command\Command;

abstract class AbstractTravisCommand extends Command
{
    /**
     * @param Isolator|null $isolator
     */
    public function __construct(Isolator $isolator = null, $name = null)
    {
        $this->isolator = Isolator::get($isolator);

        parent::__construct($name);
    }

    public function isEnabled()
    {
        return $this->isolator->getenv('TRAVIS') ? true : false;
    }

    protected $isolator;
}
