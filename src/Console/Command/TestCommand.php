<?php
namespace Icecave\Archer\Console\Command;

use Symfony\Component\Console\Input\InputArgument;

class TestCommand extends AbstractPHPUnitCommand
{
    protected function configure()
    {
        $this->setName('test');
        $this->setDescription('Run the test suite for a project.');

        $this->addArgument(
            'argument',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Argument(s) to pass to PHPUnit.'
        );
    }

    /**
     * @return array<string,mixed>
     */
    protected function readPHPConfiguration()
    {
        return $this->phpConfigurationReader()->read(
            array(
                './vendor/icecave/archer/res/php/php.ini',
                './test/php.ini',
                './php.ini',
            )
        );
    }

    /**
     * @return string
     */
    protected function findPHPUnitConfiguration()
    {
        return $this->configurationFileFinder()->find(
            array(
                './phpunit.xml',
                './phpunit.xml.dist',
                './test/phpunit.xml',
                './test/phpunit.xml.dist',
            ),
            './vendor/icecave/archer/res/phpunit/phpunit.xml'
        );
    }
}
