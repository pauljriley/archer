<?php
namespace Icecave\Testing\Console\Command;

use Icecave\Testing\GitHub\GitConfigReader;
use Icecave\Testing\Support\FileManager;
use Icecave\Testing\Support\Isolator;
use Icecave\Testing\Travis\TravisClient;
use Icecave\Testing\Travis\TravisConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application as SymfonyApplication;

abstract class AbstractCommand extends Command
{
    public function __construct(
        FileManager $fileManager = null,
        GitConfigReader $configReader = null,
        TravisClient $travisClient = null,
        TravisConfigManager $travisConfigManager = null,
        Isolator $isolator = null
    ) {
        $this->isolator = Isolator::get($isolator);

        if (null === $fileManager) {
            $fileManager = new FileManager($this->isolator);
        }

        if (null === $configReader) {
            $configReader = new GitConfigReader(null, $this->isolator);
        }

        if (null === $travisClient) {
            $travisClient = new TravisClient($this->isolator);
        }

        if (null === $travisConfigManager) {
            $travisConfigManager = new TravisConfigManager(
                $configReader,
                $fileManager,
                $this->isolator
            );
        }

        $this->fileManager = $fileManager;
        $this->configReader = $configReader;
        $this->travisClient = $travisClient;
        $this->travisConfigManager = $travisConfigManager;

        parent::__construct();
    }

    public function setApplication(SymfonyApplication $application = null)
    {
        $this->travisConfigManager->setPackageRoot($application->packageRoot());

        parent::setApplication($application);
    }

    protected $fileManager;
    protected $configReader;
    protected $travisClient;
    protected $travisConfigManager;
    protected $isolator;
}
