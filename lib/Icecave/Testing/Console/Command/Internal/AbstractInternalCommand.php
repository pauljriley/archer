<?php
namespace Icecave\Testing\Console\Command\Internal;

use Icecave\Testing\Support\Isolator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

abstract class AbstractInternalCommand extends Command
{
    public function __construct(Isolator $isolator = null)
    {
        $this->isolator = Isolator::get($isolator);

        parent::__construct();
    }
    
    public function isEnabled()
    {
        if (null === self::$enabled) {
            $composer = $this->getApplication()->packageRoot() . '/composer.json';
            $contents = $this->isolator->file_get_contents($composer);
            $config   = json_decode($contents);

            self::$enabled = isset($config->name)
                          && $config->name === 'icecave/testing';
        }

        return self::$enabled;
    }

    private static $enabled;
    protected $isolator;
}
