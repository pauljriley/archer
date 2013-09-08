<?php
namespace Icecave\Archer\Console\Command\Travis;

use Icecave\Archer\Coveralls\CoverallsClient;
use Icecave\Archer\FileSystem\FileSystem;
use Icecave\Archer\GitHub\GitHubClient;
use Icecave\Archer\Support\Isolator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends AbstractTravisCommand
{
    public function __construct(
        GitHubClient $githubClient = null,
        CoverallsClient $coverallsClient = null,
        FileSystem $fileSystem = null,
        Isolator $isolator = null
    ) {
        if (null === $githubClient) {
            $githubClient = new GitHubClient;
        }

        if (null === $coverallsClient) {
            $coverallsClient = new CoverallsClient;
        }

        if (null === $fileSystem) {
            $fileSystem = new FileSystem;
        }

        $this->githubClient = $githubClient;
        $this->coverallsClient = $coverallsClient;
        $this->fileSystem = $fileSystem;

        parent::__construct($isolator);
    }

    /**
     * @return GitHubClient
     */
    public function githubClient()
    {
        return $this->githubClient;
    }

    /**
     * @return CoverallsClient
     */
    public function coverallsClient()
    {
        return $this->coverallsClient;
    }

    /**
     * @param Application|null $application
     */
    public function setApplication(Application $application = null)
    {
        parent::setApplication($application);

        if ($application) {
            $this->githubClient->setUserAgent(
                $application->getName() . '/' . $application->getVersion()
            );
        }
    }

    protected function configure()
    {
        $this->setName('travis:build');
        $this->setDescription('Build and execute tests under Travis CI.');

        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'The path to the root of the project.',
            '.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $archerRoot       = $this->getApplication()->packageRoot();
        $packageRoot      = $input->getArgument('path');
        $travisPhpVersion = $this->isolator->getenv('TRAVIS_PHP_VERSION');
        $publishVersion   = $this->isolator->getenv('ARCHER_PUBLISH_VERSION');
        $currentBranch    = $this->isolator->getenv('TRAVIS_BRANCH');
        $authToken        = $this->isolator->getenv('ARCHER_TOKEN');
        $buildNumber      = $this->isolator->getenv('TRAVIS_BUILD_NUMBER');
        $repoSlug         = $this->isolator->getenv('TRAVIS_REPO_SLUG');

        list($repoOwner, $repoName) = explode('/', $repoSlug);

        $isPublishVersion = $travisPhpVersion === $publishVersion;

        if ($authToken && $isPublishVersion) {
            $this->githubClient()->setAuthToken($authToken);
            $publishArtifacts = $this->githubClient()->defaultBranch($repoOwner, $repoName) === $currentBranch;
        } else {
            $publishArtifacts = false;
        }

        $publishCoveralls = false;
        if ($isPublishVersion) {
            $output->write('Checking for Coveralls... ');
            $publishCoveralls = $this->coverallsClient()->exists($repoOwner, $repoName);
            if ($publishCoveralls) {
                $output->writeln('enabled.');
            } else {
                $output->writeln('not enabled.');
            }
        }

        if ($publishArtifacts || $publishCoveralls) {
            // Run tests with reports
            $testsExitCode = 255;
            $this->isolator->passthru($archerRoot . '/bin/archer coverage', $testsExitCode);
        } else {
            // Run default tests
            $testsExitCode = 255;
            $this->isolator->passthru($archerRoot . '/bin/archer test', $testsExitCode);
        }

        $coverallsExitCode = 0;
        if ($publishCoveralls) {
            $output->write('Publishing Coveralls data... ');
            $coverallsConfigPath = $packageRoot . '/.coveralls.yml';

            if (!$this->isolator->file_exists($coverallsConfigPath)) {
                $this->isolator->copy($archerRoot . '/res/coveralls/coveralls.yml', $coverallsConfigPath);
            }

            $coverallsExitCode = 255;
            $this->isolator->passthru(
                sprintf(
                    '%s/vendor/bin/coveralls --config %s',
                    $packageRoot,
                    escapeshellarg($coverallsConfigPath)
                ),
                $coverallsExitCode
            );

            if (0 === $coverallsExitCode) {
                $output->writeln('done.');
            } else {
                $output->writeln('failed.');
            }
        }

        $documentationExitCode = 0;
        $publishExitCode = 0;
        if ($publishArtifacts) {
            // Generate documentation
            $documentationExitCode = 255;
            $this->isolator->passthru($archerRoot . '/bin/archer documentation', $documentationExitCode);

            // Publish artifacts
            $command  = $archerRoot . '/bin/woodhouse';
            $command .= ' publish %s';
            $command .= ' %s/artifacts:artifacts';
            $command .= ' --message "Publishing artifacts from build %d."';
            $command .= ' --auth-token-env ARCHER_TOKEN';
            $command .= ' --no-interaction';
            $command .= ' --verbose';

            if ($publishCoveralls) {
                // Remove test artifacts if coveralls is being used ...
                $this->fileSystem->delete($packageRoot . '/artifacts/tests');
            } else {
                $command .= ' --coverage-image artifacts/images/coverage.png';
                $command .= ' --coverage-phpunit artifacts/tests/coverage/coverage.txt';
                $command .= ' --image-theme buckler/buckler';
            }

            $command = sprintf(
                $command,
                escapeshellarg($repoSlug),
                $packageRoot,
                $buildNumber
            );

            $publishExitCode = 255;
            $this->isolator->passthru($command, $publishExitCode);
        }

        if ($testsExitCode !== 0) {
            return $testsExitCode;
        }
        if ($coverallsExitCode !== 0) {
            return $coverallsExitCode;
        }
        if ($documentationExitCode !== 0) {
            return $documentationExitCode;
        }

        return $publishExitCode;
    }

    private $githubClient;
    private $coverallsClient;
    private $fileSystem;
}
