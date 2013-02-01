<?php
namespace Icecave\Testing\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('update');
        $this->setDescription('Update a project with the latest testing configuration.');

        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'The path to the root of the project.',
            '.'
        );

        $this->addOption(
            'oauth-token',
            't',
            InputOption::VALUE_REQUIRED,
            'A GitHub OAuth token with succificent access to push to this repository.'
        );

        $this->addOption(
            'update-public-key',
            'k',
            InputOption::VALUE_NONE,
            'Update the Travis CI public key for this repository.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fileManager->setPackageRoot($input->getArgument('path'));
        $configReader = $this->configReaderFactory->create($this->fileManager->packageRootPath());

        // Validate the OAuth token if one was provided ...
        $token = $input->getOption('oauth-token');
        if ($token && !preg_match('/^[0-9a-f]{40}$/i', $token)) {
            $output->writeln('Invalid GitHub OAuth token <comment>"' . $token . '"</comment>.');
            $output->write(PHP_EOL);

            return 1;
        }

        // Copy git files ...
        if (!$this->fileManager->gitIgnore) {
            $output->writeln('Updating <info>.gitignore</info>.');
            $this->fileManager->gitIgnore = $this->isolator->file_get_contents($this->getApplication()->packageRoot() . '/res/git/gitignore');
        }

        if (!$this->fileManager->gitAttributes) {
            $output->writeln('Updating <info>.gitattributes</info>.');
            $this->fileManager->gitAttributes = $this->isolator->file_get_contents($this->getApplication()->packageRoot() . '/res/git/gitattributes');
        }

        $repoOwner = $configReader->repositoryOwner();
        $repoName  = $configReader->repositoryName();

        // Update the public key if requested (or it's missing) ...
        $updateKey = $input->getOption('update-public-key');
        $key = $this->fileManager->publicKey;

        if ($updateKey || ($key === null && $token)) {
            $output->writeln('Fetching public key for <info>' . $repoOwner . '/' . $repoName . '</info>.');
            $this->fileManager->publicKey = $key = $this->travisClient->publicKey($repoOwner, $repoName);
        }

        // Re-encrypt the environment if the $token or $key changed ...
        if ($token && $key) {
            $output->writeln('Encrypting OAuth token.');
            $this->fileManager->encryptedEnvironment = $this->travisClient->encryptEnvironment(
                $key,
                $repoOwner,
                $repoName,
                $token
            );
        }

        // Update the travis CI configuration ...
        $output->writeln('Updating <info>.travis.yml</info>.');
        $artifacts = $this->travisConfigManager->updateConfig($configReader);

        if (!$artifacts) {
            $output->writeln('<comment>Artifact publication is not available as no GitHub OAuth token has been configured.</comment>');
        }

        $output->writeln('Configuration updated successfully.');
        $output->write(PHP_EOL);
    }
}
