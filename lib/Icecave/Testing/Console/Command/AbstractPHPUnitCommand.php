<?php
namespace Icecave\Testing\Console\Command;

use Icecave\Testing\Configuration\ConfigurationFileFinder;
use Icecave\Testing\Configuration\PHPConfigurationReader;
use Icecave\Testing\Process\PHPUnitExecutableFinder;
use Icecave\Testing\Process\ProcessFactory;
use Icecave\Testing\Support\Isolator;
use Symfony\Component\Console\Command\Command;
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

    private $phpFinder;
    private $phpunitFinder;
    private $phpConfigurationReader;
    private $configurationFileFinder;
    private $processFactory;
    private $isolator;
}
