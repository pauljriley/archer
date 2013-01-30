<?php
namespace Icecave\Testing\Console\Command\Travis;

use Icecave\Testing\GitHub\GitConfigReader;
use Icecave\Testing\Support\FileManager;
use Icecave\Testing\Travis\TravisClient;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchPublicKeyCommand extends Command
{
    public function __construct(FileManager $fileManager = null, GitConfigReader $configReader = null, TravisClient $travisClient = null)
    {
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

    protected function configure()
    {
        $this->setName('travis:fetch-public-key');
        $this->setDescription('Fetch the Travis CI public key for this repository.');

        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'The path to the root of the project.',
            '.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fileManager->setPackageRoot($input->getArgument('path'));
        $this->configReader->parse($this->fileManager->packageRootPath());

        $repoOwner = $this->configReader->repositoryOwner();
        $repoName  = $this->configReader->repositoryName();

        $output->writeln('Fetching public key for <info>' . $repoOwner . '/' . $repoName . '</info>.');

        $key = $this->travisClient->publicKey($repoOwner, $repoName);

        if (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity()) {
            $output->writeln(PHP_EOL . '<comment>' . trim($key) . '</comment>' . PHP_EOL);
        }

        if ($key === $this->fileManager->publicKey) {
            $output->writeln('Key has not changed.');
        } else {
            $this->fileManager->publicKey = $key;
            $output->writeln('Key updated successfully.');
        }
    }

    private $fileManager;
    private $configReader;
    private $travisClient;
}
