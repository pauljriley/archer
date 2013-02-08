<?php
namespace Icecave\Archer\Console\Command\Travis;

use Icecave\Archer\Support\Isolator;
use Icecave\Archer\Git\GitConfigReaderFactory;
use Icecave\Archer\GitHub\GitHubClient;
use Icecave\Archer\Git\GitDotFilesManager;
use Icecave\Archer\Travis\TravisClient;
use Icecave\Archer\Travis\TravisConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends AbstractTravisCommand
{
    public function __construct(GitHubClient $githubClient = null, Isolator $isolator = null) {
        if (null === $githubClient) {
            $githubClient = new GitHubClient;
        }

        $this->githubClient = $githubClient;

        parent::__construct($isolator);
    }

    /**
     * @return GitHubClient
     */
    public function githubClient()
    {
        return $this->githubClient;
    }

    protected function configure()
    {
        $this->setName('travis:build');
        $this->setDescription('Build and execute tests under Travis CI.');

        // $this->addArgument(
        //     'path',
        //     InputArgument::OPTIONAL,
        //     'The path to the root of the project.',
        //     '.'
        // );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // $archerRoot  = $this->getApplication()->packageRoot();
        // $packageRoot = $input->getArgument('path');

        // if ('true' === $this->isolator->getenv('ARCHER_PUBLIC')) {
        //     
        // }

        // $output->writeln('Configuration updated successfully.');
        // $output->write(PHP_EOL);
    }

    private $githubClient;
}
