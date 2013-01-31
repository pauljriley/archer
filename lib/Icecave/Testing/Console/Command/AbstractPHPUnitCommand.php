<?php
namespace Icecave\Testing\Console\Command;

use Icecave\Testing\Configuration\ConfigurationFileFinder;
use Icecave\Testing\Configuration\PHPConfigurationReader;
use Icecave\Testing\Process\PHPUnitExecutableFinder;
use Icecave\Testing\Process\ProcessFactory;
use Icecave\Testing\Support\Isolator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;

abstract class AbstractPHPUnitCommand extends Command
{
    /**
     * @param PhpExecutableFinder|null     $phpFinder
     * @param PHPUnitExecutableFinder|null $phpunitFinder
     * @param PHPConfigurationReader|null  $phpConfigurationReader
     * @param ConfigurationFileFinder|null $configurationFileFinder
     * @param ProcessFactory|null          $processFactory
     * @param Isolator|null                $isolator
     */
    public function __construct(
        PhpExecutableFinder $phpFinder = null,
        PHPUnitExecutableFinder $phpunitFinder = null,
        PHPConfigurationReader $phpConfigurationReader = null,
        ConfigurationFileFinder $configurationFileFinder = null,
        ProcessFactory $processFactory = null,
        Isolator $isolator = null
    ) {
        if (null === $phpFinder) {
            $phpFinder = new PhpExecutableFinder;
        }
        $this->phpFinder = $phpFinder;

        $this->isolator = Isolator::get($isolator);

        if (null === $processFactory) {
            $processFactory = new ProcessFactory;
        }
        $this->processFactory = $processFactory;

        if (null === $phpunitFinder) {
            $phpunitFinder = new PHPUnitExecutableFinder(
                null,
                $this->processFactory,
                $this->isolator
            );
        }
        $this->phpunitFinder = $phpunitFinder;

        if (null === $phpConfigurationReader) {
            $phpConfigurationReader = new PHPConfigurationReader($this->isolator);
        }
        $this->phpConfigurationReader = $phpConfigurationReader;

        if (null === $configurationFileFinder) {
            $configurationFileFinder = new ConfigurationFileFinder($this->isolator);
        }
        $this->configurationFileFinder = $configurationFileFinder;

        parent::__construct();
    }

    /**
     * @return PhpExecutableFinder
     */
    public function phpFinder()
    {
        return $this->phpFinder;
    }

    /**
     * @return PHPUnitExecutableFinder
     */
    public function phpunitFinder()
    {
        return $this->phpunitFinder;
    }

    /**
     * @return PHPConfigurationReader
     */
    public function phpConfigurationReader()
    {
        return $this->phpConfigurationReader;
    }

    /**
     * @return ConfigurationFileFinder
     */
    public function configurationFileFinder()
    {
        return $this->configurationFileFinder;
    }

    /**
     * @return ProcessFactory
     */
    public function processFactory()
    {
        return $this->processFactory;
    }

    /**
     * @return Isolator
     */
    protected function isolator()
    {
        return $this->isolator;
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

        $process = $this->processFactory()->createFromArray(
            $this->generateArguments(
                $phpPath,
                $phpunitPath,
                $input->getArgument('argument')
            )
        );

        return $process->run(function ($type, $buffer) {
            if ('out' === $type) {
                $this->isolator()->fwrite(STDOUT, $buffer);
            } else {
                $this->isolator()->fwrite(STDERR, $buffer);
            }
        });
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
        return array_merge(
            array($phpPath),
            $this->phpConfigurationArguments($this->readPHPConfiguration()),
            array(
                $phpunitPath,
                '--configuration',
                $this->findPHPUnitConfiguration(),
            ),
            $phpunitArguments
        );
    }

    /**
     * @param array<string,mixed> $configuration
     *
     * @return array<string>
     */
    protected function phpConfigurationArguments(array $configuration)
    {
        $arguments = array();
        foreach ($configuration as $key => $value) {
            $arguments[] = '--define';
            $arguments[] = sprintf('%s=%s', $key, $value);
        }

        return $arguments;
    }

    /**
     * @return array<string,mixed>
     */
    abstract protected function readPHPConfiguration();

    /**
     * @return string
     */
    abstract protected function findPHPUnitConfiguration();

    private $phpFinder;
    private $phpunitFinder;
    private $phpConfigurationReader;
    private $configurationFileFinder;
    private $processFactory;
    private $isolator;
}
