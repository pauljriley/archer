<?php
namespace Icecave\Archer\Console\Command\Travis;

use Icecave\Archer\Support\Isolator;
use Icecave\Archer\GitHub\GitHubClient;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends AbstractTravisCommand
{
    public function __construct(GitHubClient $githubClient = null, Isolator $isolator = null)
    {
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

        if ($authToken && $travisPhpVersion === $publishVersion) {
            $this->githubClient()->setAuthToken($authToken);
            $publishArtifacts = $this->githubClient()->defaultBranch($repoOwner, $repoName) === $currentBranch;
        } else {
            $publishArtifacts = false;
        }

        // Run default tests ...
        if (!$publishArtifacts) {
            $testsExitCode = 255;
            $this->isolator->passthru($archerRoot . '/bin/archer test', $testsExitCode);

            return $testsExitCode;
        }

        // Run tests with reports ...
        $testsExitCode = 255;
        $this->isolator->passthru($archerRoot . '/bin/archer coverage', $testsExitCode);

        // Generate documentation ...
        $documentationExitCode = 255;
        $this->isolator->passthru($archerRoot . '/bin/archer documentation', $documentationExitCode);

        // Publish artifacts ...
        $command  = $archerRoot . '/bin/woodhouse';
        $command .= ' publish %s';
        $command .= ' %s/artifacts:artifacts';
        $command .= ' --message "Publishing artifacts from build #%d."';
        $command .= ' --coverage-image artifacts/images/coverage.png';
        $command .= ' --coverage-phpunit artifacts/tests/coverage/coverage.txt';
        $command .= ' --build-status-image artifacts/images/build-status.png';
        $command .= ' --build-status-tap artifacts/tests/report.tap';
        $command .= ' --auth-token-env ARCHER_TOKEN';
        $command .= ' --image-theme travis/variable-width';
        $command .= ' --image-theme icecave/regular';
        $command .= ' --no-interaction';
        $command .= ' --verbose';

        $command = sprintf(
            $command,
            escapeshellarg($repoSlug),
            $packageRoot,
            $buildNumber
        );

        $publishExitCode = 255;
        $this->isolator->passthru($command, $publishExitCode);

        if ($testsExitCode !== 0) {
            return $testsExitCode;
        }
        if ($documentationExitCode !== 0) {
            return $documentationExitCode;
        }

        return $publishExitCode;
    }

    private $githubClient;
}
