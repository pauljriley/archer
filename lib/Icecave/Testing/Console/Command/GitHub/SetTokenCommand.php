<?php
namespace Icecave\Testing\Console\Command\GitHub;

use Icecave\Testing\Support\FileManager;
use Icecave\Testing\Travis\TravisClient;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Icecave\Testing\Console\Command\AbstractCommand;

class SetTokenCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('github:set-token');
        $this->setDescription('Set the GitHub OAuth token to use for publishing test results.');

        $this->addArgument(
            'oauth-token',
            InputArgument::REQUIRED,
            'A GitHub OAuth token with write access to the GitHub repository used for publishing.'
        );

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

        $token = $input->getArgument('oauth-token');
        if (!preg_match('/^[0-9a-f]{40}$/i', $token)) {
            $output->writeln('Invalid GitHub OAuth token <comment>"' . $token . '"</comment>.');
            $output->write(PHP_EOL);
            return 1;
        }

        $repoOwner = $this->configReader->repositoryOwner();
        $repoName  = $this->configReader->repositoryName();

        $key = $this->fileManager->publicKey;

        if (null === $key) {
            $output->writeln('Fetching public key for <info>' . $repoOwner . '/' . $repoName . '</info>.');
            $this->fileManager->publicKey = $this->travisClient->publicKey($repoOwner, $repoName);
        }

        $this->fileManager->encryptedEnvironment = $this->travisClient->encryptEnvironment(
            $key,
            $repoOwner,
            $repoName,
            $token
        );

        // TODO: not implemented ...
        // $this->fileManager->travisYaml = $this->travisClient->generateYaml();

        $output->writeln('Token updated successfully.');
        $output->write(PHP_EOL);
    }
}
