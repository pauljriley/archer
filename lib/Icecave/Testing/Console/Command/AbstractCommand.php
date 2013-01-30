<?php
namespace Icecave\Testing\Console\Command;

use Icecave\Testing\GitHub\GitConfigReader;
use Icecave\Testing\Support\FileManager;
use Icecave\Testing\Support\Isolator;
use Icecave\Testing\Travis\TravisClient;
use Symfony\Component\Console\Command\Command;

abstract class AbstractCommand extends Command
{
    public function __construct(
        FileManager $fileManager = null,
        GitConfigReader $configReader = null,
        TravisClient $travisClient = null,
        Isolator $isolator = null
    ) {
        $this->isolator = Isolator::get($isolator);

        if (null === $fileManager) {
            $fileManager = new FileManager;
        }

        if (null === $configReader) {
            $configReader = new GitConfigReader;
        }

        if (null === $travisClient) {
            $travisClient = new TravisClient;
        }

        $this->fileManager = $fileManager;
        $this->configReader = $configReader;
        $this->travisClient = $travisClient;

        parent::__construct();
    }

    protected $fileManager;
    protected $configReader;
    protected $travisClient;
    protected $isolator;
}
