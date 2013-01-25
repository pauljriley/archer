<?php
namespace Icecave\Woodhouse\Publisher;

use Exception;
use Icecave\Isolator\Isolator;
use Icecave\Woodhouse\TypeCheck\TypeCheck;
use InvalidArgumentException;
use RuntimeException;

class GitHubPublisher extends AbstractPublisher
{
    const REPOSITORY_PATTERN = '/^[a-z0-9_-]+\/[a-z0-9_-]+$/i';
    const AUTH_TOKEN_PATTERN = '/^[0-9a-f]{40}$/i';

    /**
     * @param Isolator|null $isolator
     */
    public function __construct(Isolator $isolator = null)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->isolator = Isolator::get($isolator);
        $this->branch = 'gh-pages';
        $this->commitMessage = 'Content published by Woodhouse.';
        $this->maxPushAttempts = 3;

        parent::__construct();
    }

    /**
     * Publish enqueued content.
     */
    public function publish()
    {
        $this->typeCheck->publish(func_get_args());

        if (null === $this->repository) {
            throw new RuntimeException('No repository set.');
        }

        $tempDir = $this->isolator->sys_get_temp_dir() . '/woodhouse-' . $this->isolator->getmypid();

        try {
            $this->doPublish($tempDir);
            $this->execute('rm', '-rf', $tempDir);
        } catch (Exception $e) {
            $this->execute('rm', '-rf', $tempDir);
            throw $e;
        }
    }

    /**
     * Publish enqueued content.
     *
     * @param string $tempDir
     */
    protected function doPublish($tempDir)
    {
        $this->typeCheck->doPublish(func_get_args());

        // Clone the Git repository ...
        $output = $this->execute(
            'git', 'clone', '--quiet',
            '--branch', $this->branch(),
            '--depth', 0,
            $this->repositoryUrl(),
            $tempDir
        );

        $this->isolator->chdir($tempDir);

        // Create the brach if it doesn't exist ...
        if (false !== strpos($output, $this->branch() . ' not found in upstream origin')) {
            $this->execute('git', 'checkout', '--orphan', $this->branch());
            $this->execute('git', 'rm', '-rf', '--ignore-unmatch', '.');

        // Branch does exist ...
        } else {
            // Remove existing content that exists in target paths ...
            foreach ($this->contentPaths() as $sourcePath => $targetPath) {
                $this->execute('git', 'rm', '-rf', '--ignore-unmatch', $targetPath);
            }
        }

        // Copy in published content and add it to the repo ...
        $system = $this->isolator->php_uname('s');
        if ('Linux' === $system) {
            $copyFlags = '-rT';
        } elseif ('Darwin' === $system || false !== strpos($system, 'BSD')) {
            $copyFlags = '-R';
        } else {
            $copyFlags = '-r';
        }

        foreach ($this->contentPaths() as $sourcePath => $targetPath) {
            $fullTargetPath = $tempDir . '/' . $targetPath;
            $fullTargetParentPath = dirname($fullTargetPath);
            if (!$this->isolator->is_dir($fullTargetParentPath)) {
                $this->isolator->mkdir($fullTargetParentPath, 0777, true);
            }

            if ($this->isolator->is_dir($sourcePath)) {
                $sourcePath = rtrim($sourcePath, '/') . '/';
                $this->execute('cp', $copyFlags, $sourcePath, $fullTargetPath);
            } else {
                $this->execute('cp', $sourcePath, $fullTargetPath);
            }

            $this->execute('git', 'add', $targetPath);
        }

        // Commit the published content ...
        $this->execute('git', 'commit', '-m', $this->commitMessage());

        // Make push attempts ...
        $attemptsRemaining = $this->maxPushAttempts;
        while (true) {
            if (null !== $this->tryExecute('git', 'push', 'origin', $this->branch())) {
                return;
            } elseif (--$attemptsRemaining) {
                $this->execute('git', 'pull');
            } else {
                break;
            }
        }

        throw new RuntimeException('Unable to publish content.');
    }

    /**
     * @return string
     */
    public function repository()
    {
        $this->typeCheck->repository(func_get_args());

        return $this->repository;
    }

    /**
     * @param string $repository
     */
    public function setRepository($repository)
    {
        $this->typeCheck->setRepository(func_get_args());

        if (!preg_match(self::REPOSITORY_PATTERN, $repository)) {
            throw new InvalidArgumentException('Invalid repository name: "' . $repository . '".');
        }

        $this->repository = $repository;
    }

    /**
     * @return string
     */
    public function repositoryUrl()
    {
        $this->typeCheck->repositoryUrl(func_get_args());

        if (null === $this->repository) {
            return null;
        }

        if (null === $this->authToken) {
            return sprintf('https://github.com/%s.git', $this->repository);
        }

        return sprintf('https://%s:x-oauth-basic@github.com/%s.git', $this->authToken, $this->repository);
    }

    /**
     * @return string
     */
    public function branch()
    {
        $this->typeCheck->branch(func_get_args());

        return $this->branch;
    }

    /**
     * @param string $branch
     */
    public function setBranch($branch)
    {
        $this->typeCheck->setBranch(func_get_args());

        $this->branch = $branch;
    }

    /**
     * @return string
     */
    public function commitMessage()
    {
        $this->typeCheck->commitMessage(func_get_args());

        return $this->commitMessage;
    }

    /**
     * @param string $commitMessage
     */
    public function setCommitMessage($commitMessage)
    {
        $this->typeCheck->setCommitMessage(func_get_args());

        $this->commitMessage = $commitMessage;
    }

    /**
     * @return string|null
     */
    public function authToken()
    {
        $this->typeCheck->authToken(func_get_args());

        return $this->authToken;

    }

    /**
     * @param string|null $authToken
     */
    public function setAuthToken($authToken)
    {
        $this->typeCheck->setAuthToken(func_get_args());

        if (!preg_match(self::AUTH_TOKEN_PATTERN, $authToken)) {
            // Note that the provided token is deliberately not included in the exception
            // message to prevent possible leaks of strings that are very-near to a real token.
            throw new InvalidArgumentException('Invalid authentication token.');
        }

        $this->authToken = strtolower($authToken);
    }

    /**
     * @param string     $command
     * @param stringable $argument,...
     */
    protected function execute($command)
    {
        $this->typeCheck->execute(func_get_args());

        $result = $this->tryExecuteArray(
            $command,
            array_slice(func_get_args(), 1)
        );

        if (null === $result) {
            throw new RuntimeException('Failed executing command: "' . $command . '".');
        }

        return $result;
    }

    /**
     * @param string     $command
     * @param stringable $argument,...
     */
    protected function tryExecute($command)
    {
        $this->typeCheck->tryExecute(func_get_args());

        $arguments = array_slice(func_get_args(), 1);

        return $this->tryExecuteArray($command, $arguments);
    }

    /**
     * @param string            $command
     * @param array<stringable> $arguments
     */
    protected function tryExecuteArray($command, array $arguments)
    {
        $this->typeCheck->tryExecuteArray(func_get_args());

        $commandLine = '/usr/bin/env ' . escapeshellarg($command);
        foreach ($arguments as $arg) {
            $commandLine .= ' ' . escapeshellarg($arg);
        }
        $commandLine .=  ' 2>&1';

        $exitCode = null;
        $output = array();

        $this->isolator->exec($commandLine, $output, $exitCode);

        if (0 === $exitCode) {
            return implode(PHP_EOL, $output);
        }

        return null;
    }

    private $typeCheck;
    private $repository;
    private $branch;
    private $commitMessage;
    private $authToken;
    private $maxPushAttempts;
}
