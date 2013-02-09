<?php
namespace Icecave\Archer\Console\Command\Travis;

use Icecave\Archer\Support\Isolator;
use Icecave\Archer\GitHub\GitHubClient;
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
        $publishArtifacts = $this->isolator->getenv('ARCHER_PUBLISH') === 'true';

        $this->isolator->chdir($packageRoot);

        // Run default tests ...
        if (!$publishArtifacts) {
            $exitCode = 1;
            $this->isolator->passthru($archerRoot . '/bin/archer test --no-interaction', $exitCode);

            return $exitCode;
        }

        $currentBranch = $this->isolator->getenv('TRAVIS_BRANCH');
        $authToken     = $this->isolator->getenv('ARCHER_TOKEN');
        $buildNumber   = $this->isolator->getenv('TRAVIS_BUILD_NUMBER');
        $repoOwner     = $this->isolator->getenv('ARCHER_REPO_OWNER');
        $repoName      = $this->isolator->getenv('ARCHER_REPO_NAME');

        $this->githubClient()->setAuthToken($authToken);

        if ($currentBranch !== $this->githubClient()->defaultBranch($repoOwner, $repoName)) {
            $output->writeln('Skipping artifact publication for branch "' . $currentBranch . '".');

            return 0;
        }

        // Run tests with reports ...
        $this->isolator->passthru($archerRoot . '/bin/archer coverage --no-interaction');

        $command  = $archerRoot . '/res/bin/woodhouse';
        $command .= ' publish %s/%s';
        $command .= ' artifacts:artifacts';
        $command .= ' --message "Publishing artifacts from build #%d."';
        $command .= ' --coverage-image artifacts/images/coverage.png';
        $command .= ' --coverage-phpunit artifacts/tests/coverage/coverage.txt';
        $command .= ' --build-status-image artifacts/images/build-status.png';
        $command .= ' --build-status-tap artifacts/tests/report.tap';
        $command .= ' --auth-token-env ARCHER_TOKEN';
        $command .= ' --no-interaction';
        $command .= ' --verbose';

        $command = sprintf(
            $command,
            $repoOwner,
            $repoName,
            $buildNumber
        );

        $exitCode = 1;
        $this->isolator->passthru($command, $exitCode);

        return $exitCode;
    }

    private $githubClient;
}
