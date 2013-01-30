<?php
namespace Icecave\Testing\Console\Command\Travis;

use Icecave\Testing\Support\FileManager;
use Icecave\Testing\Travis\TravisClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Icecave\Testing\Console\Command\AbstractCommand;

class FetchPublicKeyCommand extends AbstractCommand
{
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

        $output->write(PHP_EOL);
    }
}
