<?php
namespace Icecave\Archer\Console\Command;

use Icecave\Archer\FileSystem\FileSystem;
use Icecave\Archer\Git\GitConfigReaderFactory;
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
        FileSystem $fileSystem = null,
        GitDotFilesManager $dotFilesManager = null,
        GitConfigReaderFactory $configReaderFactory = null,
        TravisClient $travisClient = null,
        TravisConfigManager $travisConfigManager = null
    ) {
        if (null === $fileSystem) {
            $fileSystem = new FileSystem;
        }
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

        $this->fileSystem = $fileSystem;
        $this->dotFilesManager = $dotFilesManager;
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

        $packageRoot = $input->getArgument('path');
        $configReader = $this->configReaderFactory()->create($packageRoot);

        // Validate the OAuth token if one was provided ...
        $token = $input->getOption('oauth-token');
        if ($token && GitHuToken::validate($token)) {
            $output->writeln('Invalid GitHub OAuth token <comment>"' . $token . '"</comment>.');
            $output->write(PHP_EOL);

            return 1;
        }

        // Update Git dotfiles ...
        $files = $this->dotFilesManager()->updateDotFiles(
            $this->getApplication()->packageRoot(),
            $packageRoot
        );

        foreach ($files as $filename => $updated) {
            if ($updated) {
                $output->writeln(sprintf('Updated <info>%s</info>.', $filename));
            }
        }

        // Fetch the public key ...
        $repoOwner = $configReader->repositoryOwner();
        $repoName  = $configReader->repositoryName();
        $publicKey = $this->travisConfigManager()->publicKeyCache($packageRoot);
        $updateKey = $input->getOption('update-public-key');

        if ($updateKey || (null === $publicKey && $token)) {
            $output->writeln(sprintf(
                'Fetching public key for <info>%s/%s</info>.',
                $repoOwner,
                $repoName
            ));

            $publicKey = $this->travisClient()->publicKey($repoOwner, $repoName);
            $this->travisConfigManager()->setPublicKeyCache($packageRoot, $publicKey);
        }

        // Re-encrypt the environment if the $token or $key changed ...
        if ($token && $publicKey) {
            $output->writeln('Encrypting OAuth token.');
            $secureEnv = $this->travisClient()->encryptEnvironment($publicKey, $token);
            $this->travisConfigManager()->setSecureEnvironmentCache($packageRoot, $secureEnv);
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

    private $fileSystem;
    private $dotFilesManager;
    private $configReaderFactory;
    private $travisClient;
    private $travisConfigManager;
    private $isolator;
}
