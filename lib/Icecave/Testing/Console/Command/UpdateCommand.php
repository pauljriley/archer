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
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        $packageRoot = $input->getArgument('path');
        $configReader = $this->configReaderFactory()->create($packageRoot);

        // Validate the OAuth token if one was provided ...
        $token = $input->getOption('oauth-token');
        if ($token && !preg_match('/^[0-9a-f]{40}$/i', $token)) {
            $output->writeln('Invalid GitHub OAuth token <comment>"' . $token . '"</comment>.');
            $output->write(PHP_EOL);

            return 1;
        }

        // Copy git files ...
        $gitIgnorePath = sprintf('%s/.gitignore', $packageRoot);
        if (!$this->fileSystem()->exists($gitIgnorePath)) {
            $output->writeln('Updating <info>.gitignore</info>.');

            $this->fileSystem()->copy(
                sprintf('%s/res/git/gitignore', $this->getApplication()->packageRoot()),
                $gitIgnorePath
            );
        }
        $gitAttributesPath = sprintf('%s/.gitattributes', $packageRoot);
        if (!$this->fileSystem()->exists($gitAttributesPath)) {
            $output->writeln('Updating <info>.gitattributes</info>.');

            $this->fileSystem()->copy(
                sprintf('%s/res/git/gitattributes', $this->getApplication()->packageRoot()),
                $gitAttributesPath
            );
        }

        $repoOwner = $configReader->repositoryOwner();
        $repoName  = $configReader->repositoryName();

        // Update the public key if requested (or it's missing) ...
        $keyPath = sprintf('%s/.travis.key', $packageRoot);
        if ($this->fileSystem()->exists($keyPath)) {
            $key = $this->fileSystem()->read($keyPath);
        } else {
            $key = null;
        }
        $updateKey = $input->getOption('update-public-key');

        if ($updateKey || (null === $key && $token)) {
            $output->writeln(sprintf(
                'Fetching public key for <info>%s/%s</info>.',
                $repoOwner,
                $repoName
            ));

            $key = $this->travisClient()->publicKey($repoOwner, $repoName);
            $this->fileSystem()->write($keyPath, $key);
        }

        // Re-encrypt the environment if the $token or $key changed ...
        if ($token && $key) {
            $output->writeln('Encrypting OAuth token.');
            $this->fileSystem()->write(
                sprintf('%s/.travis.env', $packageRoot),
                $this->travisClient()->encryptEnvironment(
                    $key,
                    $token
                )
            );
        }

        // Update the travis CI configuration ...
        $output->writeln('Updating <info>.travis.yml</info>.');
        $artifacts = $this->travisConfigManager()->updateConfig(
            $this->getApplication()->packageRoot(),
            $packageRoot,
            $configReader
        );

        if (!$artifacts) {
            $output->writeln('<comment>Artifact publication is not available as no GitHub OAuth token has been configured.</comment>');
        }

        $output->writeln('Configuration updated successfully.');
        $output->write(PHP_EOL);
    }
}
