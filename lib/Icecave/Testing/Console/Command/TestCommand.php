<?php
namespace Icecave\Testing\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $phpPath = $this->phpFinder()->find();
        $output->writeln(sprintf('<info>Using PHP:</info> %s', $phpPath));

        $phpunitPath = $this->phpunitFinder()->find();
        $output->writeln(sprintf('<info>Using PHPUnit:</info> %s', $phpunitPath));

        $arguments = array_merge(
            array($phpPath),
            $this->phpConfigurationArguments(),
            array(
                $phpunitPath,
                '--configuration',
                $this->findPHPUnitConfiguration(),
            ),
            $input->getArgument('argument')
        );
        $process = $this->processFactory()->createFromArray($arguments);

        return $process->run(function ($type, $buffer) {
            if ('out' === $type) {
                $this->isolator()->fwrite(STDOUT, $buffer);
            } else {
                $this->isolator()->fwrite(STDERR, $buffer);
            }
        });
    }

    /**
     * @return array<string>
     */
    protected function candidatePHPConfigurationPaths()
    {
        return array(
            './vendor/icecave/testing/res/php/php.ini',
            './test/php.ini',
            './php.ini',
        );
    }

    /**
     * @return array<string>
     */
    protected function candidatePHPUnitConfigurationPaths()
    {
        return array(
            './phpunit.xml',
            './phpunit.xml.dist',
            './test/phpunit.xml',
            './test/phpunit.xml.dist',
        );
    }

    /**
     * @return string
     */
    protected function defaultPHPUnitConfigurationPath()
    {
        return './vendor/icecave/testing/res/phpunit/phpunit.xml';
    }
}
