<?php
namespace Icecave\Archer\Console\Command;

use Icecave\Archer\Git\GitConfigReaderFactory;
use Icecave\Archer\GitHub\GitHubClient;
use Icecave\Archer\Git\GitDotFilesManager;
use Icecave\Archer\Process\ProcessFactory;
use Icecave\Archer\Travis\TravisClient;
use Icecave\Archer\Travis\TravisConfigManager;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{
    /**
     * @param GitDotFilesManager|null     $dotFilesManager
     * @param GitConfigReaderFactory|null $configReaderFactory
     * @param TravisClient|null           $travisClient
     * @param TravisConfigManager|null    $travisConfigManager
     * @param ProcessFactory|null         $processFactory
     */
    public function __construct(
        GitDotFilesManager $dotFilesManager = null,
        GitConfigReaderFactory $configReaderFactory = null,
        TravisClient $travisClient = null,
        TravisConfigManager $travisConfigManager = null,
        ProcessFactory $processFactory = null
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
        if (null === $processFactory) {
            $processFactory = new ProcessFactory;
        }

        $this->dotFilesManager = $dotFilesManager;
        $this->configReaderFactory = $configReaderFactory;
        $this->travisClient = $travisClient;
        $this->travisConfigManager = $travisConfigManager;
        $this->processFactory = $processFactory;

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

    /**
     * @return ProcessFactory
     */
    public function processFactory()
    {
        return $this->processFactory;
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
            'authorize',
            'a',
            InputOption::VALUE_NONE,
            'Set up authorization for this repository.'
        );
        $this->addOption(
            'auth-token',
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
        $this->addOption(
            'username',
            'u',
            InputOption::VALUE_REQUIRED,
            'A GitHub username to use for API authentication.'
        );
        $this->addOption(
            'password',
            'p',
            InputOption::VALUE_REQUIRED,
            'A GitHub password to use for API authentication.'
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Fetch the OAuth token if necessary.
        $token = $input->getOption('auth-token');
        if (null === $token && $input->getOption('authorize')) {
            $output->writeln('Searching for existing authorization.');
            list($username, $password) = $this->credentials($input, $output);

            $token = $this->existingToken($username, $password);
            if (null === $token) {
                $output->writeln('No existing authorization found.');
                $output->writeln('Creating new authorization.');

                $token = $this->createToken($username, $password);
            } else {
                $output->writeln('Existing authorization found.');
            }
        }

        // Validate the OAuth token if one was provided.
        if ($token && !GitHubClient::validateToken($token)) {
            $output->writeln('Invalid GitHub OAuth token <comment>"' . $token . '"</comment>.');
            $output->writeln('');

            return 1;
        }

        // Verify that a token is provided if --update-public-key is used.
        $updateKey = $input->getOption('update-public-key');
        if ($updateKey && !$token) {
            $output->writeln('Can not update public key without --authorize or --auth-token.');
            $output->writeln('');

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
            $packageRoot
        );

        $output->writeln('Updated <info>.travis.yml</info>.');

        if (!$artifacts) {
            $output->writeln('<comment>Artifact publication is not available as no GitHub OAuth token has been configured.</comment>');
        }

        $output->writeln('Configuration updated successfully.');
        $output->writeln('');
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return string|null
     */
    protected function existingToken($username, $password)
    {
        $result = $this->executeWoodhouse(
            $username,
            $password,
            array(
                'github:list-auth',
                '--name',
                '/^Archer$/',
                '--url',
                '~^https://github\.com/IcecaveStudios/archer$~',
            )
        );

        return $this->parseAuthorization($result);
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return string
     */
    protected function createToken($username, $password)
    {
        $result = $this->executeWoodhouse(
            $username,
            $password,
            array(
                'github:create-auth',
                '--name',
                'Archer',
                '--url',
                'https://github.com/IcecaveStudios/archer',
            )
        );

        return $this->parseAuthorization($result);
    }

    /**
     * @param string $data
     *
     * @return string|null
     */
    protected function parseAuthorization($data)
    {
        if ('' === trim($data)) {
            return null;
        }

        $pattern = '~^\d+: ([0-9a-f]{40}) Archer \(API\) \[([a-z, ]*)\] https://github.com/IcecaveStudios/archer$~m';
        if (preg_match_all($pattern, $data, $matches)) {
            if (count($matches[0]) > 1) {
                throw new RuntimeException(
                    'Mutiple Archer GitHub authorizations found. Delete redundant authorizations before continuing.'
                );
            }

            if ('repo' !== $matches[2][0]) {
                throw new RuntimeException(sprintf(
                    'Archer GitHub authorization has incorrect scope. Expected [repo], but actual token scope is [%s].',
                    $matches[2][0]
                ));
            }

            return $matches[1][0];
        }

        throw new RuntimeException('Unable to parse authorization token.');
    }

    /**
     * @param string        $username
     * @param string        $password
     * @param array<string> $arguments
     *
     * @return string
     */
    protected function executeWoodhouse($username, $password, array $arguments)
    {
        array_unshift(
            $arguments,
            sprintf('%s/bin/woodhouse', $this->getApplication()->packageRoot())
        );
        $arguments[] = '--username';
        $arguments[] = $username;
        $arguments[] = '--password';
        $arguments[] = $password;

        $process = $this->processFactory()->createFromArray($arguments);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                'Failed to execute authorization management command (%s).',
                $arguments[1]
            ));
        }

        return $process->getOutput();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return tuple<string,string>
     */
    protected function credentials(InputInterface $input, OutputInterface $output)
    {
        if (null === $this->credentials) {
            $username = $input->getOption('username');
            $password = $input->getOption('password');
            if ($input->isInteractive()) {
                if (null === $username) {
                    $username = $this->getHelperSet()->get('dialog')->ask($output, 'Username: ');
                }
                if (null === $password) {
                    $password = $this->getHelperSet()->get('hidden-input')->askHiddenResponse($output, 'Password: ');
                }
            }

            $this->credentials = array($username, $password);
        }

        return $this->credentials;
    }

    private $dotFilesManager;
    private $configReaderFactory;
    private $travisClient;
    private $travisConfigManager;
    private $processFactory;
    private $credentials;
    private $isolator;
}
