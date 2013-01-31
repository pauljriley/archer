<?php
namespace Icecave\Testing\Console\Command;

use Symfony\Component\Console\Input\InputArgument;

class CoverageCommand extends AbstractPHPUnitCommand
{
    protected function configure()
    {
        $this->setName('coverage');
        $this->setDescription(
            'Run the test suite for a project and generate a code coverage report.'
        );

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
        return $this->phpConfigurationReader()->read(array(
            './vendor/icecave/testing/res/php/php.ini',
            './vendor/icecave/testing/res/php/php.coverage.ini',
            './test/php.ini',
            './test/php.coverage.ini',
            './php.ini',
            './php.coverage.ini',
        ));
    }

    /**
     * @return string
     */
    protected function findPHPUnitConfiguration()
    {
        return $this->configurationFileFinder()->find(
            array(
                './phpunit.coverage.xml',
                './test/phpunit.coverage.xml',
            ),
            './vendor/icecave/testing/res/phpunit/phpunit.coverage.xml'
        );
    }
}
