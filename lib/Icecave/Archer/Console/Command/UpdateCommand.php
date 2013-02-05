<?php
namespace Icecave\Archer\Console\Command;

use Icecave\Archer\Git\GitConfigReaderFactory;
use Icecave\Archer\GitHub\GitHubToken;
use Icecave\Archer\Git\GitDotFilesManager;
use Icecave\Archer\Travis\TravisClient;
use Icecave\Archer\Travis\TravisConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{
    public function __construct(
        GitDotFilesManager $dotFilesManager = null,
        GitConfigReaderFactory $configReaderFactory = null,
        TravisClient $travisClient = null,
        TravisConfigManager $travisConfigManager = null
    ) {
        if (null === $dotFilesManager) {
            $dotFilesManager = new GitDotFilesManager;
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

        $this->dotFilesManager = $dotFilesManager;
        $this->configReaderFactory = $configReaderFactory;
        $this->travisClient = $travisClient;
        $this->travisConfigManager = $travisConfigManager;

        parent::__construct();
    }

    /**
     * @return GitDotFilesManager
     */
    public function dotFilesManager()
    {
        return $this->dotFilesManager;
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

    protected function configure()
    {
        $this->setName('update');
        $this->setDescription('Update a project with the latest Archer configuration.');

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

        // Validate the OAuth token if one was provided ...
        $token = $input->getOption('oauth-token');
        if ($token && !GitHubToken::validate($token)) {
            $output->writeln('Invalid GitHub OAuth token <comment>"' . $token . '"</comment>.');
            $output->write(PHP_EOL);

            return 1;
        }

        // Verify that a token is provided if --update-public-key is used.
        $updateKey = $input->getOption('update-public-key');
        if ($updateKey && !$token) {
            $output->writeln('Can not update public key without --oauth-token.');
            $output->write(PHP_EOL);

            return 1;
        }

        $archerRoot  = $this->getApplication()->packageRoot();
        $packageRoot = $input->getArgument('path');

        // Update Git dotfiles ...
        foreach ($this->dotFilesManager()->updateDotFiles($archerRoot, $packageRoot) as $filename => $updated) {
            if ($updated) {
                $output->writeln(sprintf('Updated <info>%s</info>.', $filename));
            }
        }

        // Fetch the public key ...
        $configReader = $this->configReaderFactory()->create($packageRoot);
        $repoOwner    = $configReader->repositoryOwner();
        $repoName     = $configReader->repositoryName();
        $publicKey    = $this->travisConfigManager()->publicKeyCache($packageRoot);

        if ($updateKey || ($token && null === $publicKey)) {
            $output->writeln(sprintf(
                'Fetching public key for <info>%s/%s</info>.',
                $repoOwner,
                $repoName
            ));

            $publicKey = $this->travisClient()->publicKey($repoOwner, $repoName);
            $this->travisConfigManager()->setPublicKeyCache($packageRoot, $publicKey);
        }

        // Encrypt the new token ..
        if ($token) {
            $output->writeln('Encrypting OAuth token.');
            $secureEnv = $this->travisClient()->encryptEnvironment($publicKey, $token);
            $this->travisConfigManager()->setSecureEnvironmentCache($packageRoot, $secureEnv);
        }

        // Update the travis CI configuration ...
        $artifacts = $this->travisConfigManager()->updateConfig(
            $archerRoot,
            $packageRoot,
            $configReader
        );

        $output->writeln('Updated <info>.travis.yml</info>.');

        if (!$artifacts) {
            $output->writeln('<comment>Artifact publication is not available as no GitHub OAuth token has been configured.</comment>');
        }

        $output->writeln('Configuration updated successfully.');
        $output->write(PHP_EOL);
    }

    private $dotFilesManager;
    private $configReaderFactory;
    private $travisClient;
    private $travisConfigManager;
    private $isolator;
}
