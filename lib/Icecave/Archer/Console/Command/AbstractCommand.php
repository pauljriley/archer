<?php
namespace Icecave\Archer\Console\Command;

use Icecave\Archer\FileSystem\FileSystem;
use Icecave\Archer\GitHub\GitConfigReaderFactory;
use Icecave\Archer\Travis\TravisClient;
use Icecave\Archer\Travis\TravisConfigManager;
use Symfony\Component\Console\Command\Command;

abstract class AbstractCommand extends Command
{
    public function __construct(
        FileSystem $fileSystem = null,
        GitConfigReaderFactory $configReaderFactory = null,
        TravisClient $travisClient = null,
        TravisConfigManager $travisConfigManager = null
    ) {
        if (null === $fileSystem) {
            $fileSystem = new FileSystem;
        }
        if (null === $configReaderFactory) {
            $configReaderFactory = new GitConfigReaderFactory;
        }
        if (null === $travisClient) {
            $travisClient = new TravisClient;
        }
        if (null === $travisConfigManager) {
            $travisConfigManager = new TravisConfigManager;
        }

        $this->fileSystem = $fileSystem;
        $this->configReaderFactory = $configReaderFactory;
        $this->travisClient = $travisClient;
        $this->travisConfigManager = $travisConfigManager;

        parent::__construct();
    }

    /**
     * @return FileSystem
     */
    public function fileSystem()
    {
        return $this->fileSystem;
    }

    /**
     * @return GitConfigReaderFactory
     */
    public function configReaderFactory()
    {
        return $this->configReaderFactory;
    }

    /**
     * @return TravisClient
     */
    public function travisClient()
    {
        return $this->travisClient;
    }

    /**
     * @return TravisConfigManager
     */
    public function travisConfigManager()
    {
        return $this->travisConfigManager;
    }

    private $fileSystem;
    private $configReaderFactory;
    private $travisClient;
    private $travisConfigManager;
    private $isolator;
}
