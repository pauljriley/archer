<?php
namespace Icecave\Archer\Console\Command;

use Icecave\Archer\Configuration\ConfigurationFileFinder;
use Icecave\Archer\Configuration\PHPConfigurationReader;
use Icecave\Archer\FileSystem\FileSystem;
use Icecave\Archer\Process\PHPUnitExecutableFinder;
use Icecave\Archer\Process\ProcessFactory;
use Icecave\Archer\Support\Liftoff\Launcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class CoverageCommand extends AbstractPHPUnitCommand
{
    /**
     * @param FileSystem|null              $fileSystem
     * @param PhpExecutableFinder|null     $phpFinder
     * @param PHPUnitExecutableFinder|null $phpunitFinder
     * @param PHPConfigurationReader|null  $phpConfigurationReader
     * @param ConfigurationFileFinder|null $configurationFileFinder
     * @param ProcessFactory|null          $processFactory
     * @param string|null                  $commandName
     */
    public function __construct(
        FileSystem $fileSystem = null,
        PhpExecutableFinder $phpFinder = null,
        PHPUnitExecutableFinder $phpunitFinder = null,
        PHPConfigurationReader $phpConfigurationReader = null,
        ConfigurationFileFinder $configurationFileFinder = null,
        ProcessFactory $processFactory = null,
        Launcher $launcher = null,
        $commandName = null
    ) {
        if (null === $launcher) {
            $launcher = new Launcher;
        }

        $this->launcher = $launcher;

        parent::__construct(
            $fileSystem,
            $phpFinder,
            $phpunitFinder,
            $phpConfigurationReader,
            $configurationFileFinder,
            $processFactory,
            $commandName
        );
    }

    /**
     * @return Launcher
     */
    public function launcher()
    {
        return $this->launcher;
    }

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

        $this->addOption(
            'open',
            'o',
            InputOption::VALUE_NONE,
            'Open the generated report in your default web browser.'
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $phpunitResult = parent::execute($input, $output);
        if (0 !== $phpunitResult) {
            return $phpunitResult;
        }

        if ($input->getOption('open')) {
            $output->writeln('');
            $output->write('<info>Opening coverage report... </info>');
            $this->launcher()->launch('./artifacts/tests/coverage/index.html');
            $output->writeln('done.');
        }

        return 0;
    }

    /**
     * @return array<string,mixed>
     */
    protected function readPHPConfiguration()
    {
        return $this->phpConfigurationReader()->read(
            array(
                './vendor/icecave/archer/res/php/php.ini',
                './vendor/icecave/archer/res/php/php.coverage.ini',
                './test/php.ini',
                './test/php.coverage.ini',
                './php.ini',
                './php.coverage.ini',
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
                './phpunit.coverage.xml',
                './test/phpunit.coverage.xml',
            ),
            './vendor/icecave/archer/res/phpunit/phpunit.coverage.xml'
        );
    }

    /**
     * @param string        $phpPath
     * @param string        $phpunitPath
     * @param array<string> $phpunitArguments
     *
     * @return array<string>
     */
    protected function generateArguments(
        $phpPath,
        $phpunitPath,
        array $phpunitArguments
    ) {
        $phpunitArguments = array_filter(
            $phpunitArguments,
            function ($element) {
                switch ($element) {
                    case '--open':
                    case '-o':
                        return false;
                }

                return true;
            }
        );

        return parent::generateArguments(
            $phpPath,
            $phpunitPath,
            $phpunitArguments
        );
    }

    private $launcher;
}
