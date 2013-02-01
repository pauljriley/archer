<?php
namespace Icecave\Testing\Console\Command;

use Icecave\Testing\GitHub\GitConfigReaderFactory;
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
        GitConfigReaderFactory $configReaderFactory = null,
        TravisClient $travisClient = null,
        TravisConfigManager $travisConfigManager = null,
        Isolator $isolator = null
    ) {
        $this->isolator = Isolator::get($isolator);

        if (null === $fileManager) {
            $fileManager = new FileManager($this->isolator);
        }

        if (null === $configReaderFactory) {
            $configReaderFactory = new GitConfigReaderFactory;
        }

        if (null === $travisClient) {
            $travisClient = new TravisClient($this->isolator);
        }

        if (null === $travisConfigManager) {
            $travisConfigManager = new TravisConfigManager(
                $fileManager,
                null,
                $this->isolator
            );
        }

        $this->fileManager = $fileManager;
        $this->configReaderFactory = $configReaderFactory;
        $this->travisClient = $travisClient;
        $this->travisConfigManager = $travisConfigManager;

        parent::__construct();
    }

    protected $fileManager;
    protected $configReaderFactory;
    protected $travisClient;
    protected $travisConfigManager;
    protected $isolator;
}
