<?php
namespace Icecave\Testing\GitHub;

use RuntimeException;
use Icecave\Testing\Process\ProcessFactory;

class GitConfigReader
{
    public function __construct(ProcessFactory $processFactory = null)
    {
        if (null === $processFactory) {
            $processFactory = new ProcessFactory;
        }

        $this->processFactory = $processFactory;
    }

    public function processFactory()
    {
        return $this->processFactory;
    }

    public function get($key, $default = null)
    {
        if ($this->config && array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }

        return $default;
    }

    public function repositoryOwner()
    {
        $url = $this->get('remote.origin.url', '');
        if (!preg_match('{github.com[/:](.+?)/(.+?).git$}i', $url, $matches)) {
            throw new RuntimeException('Origin URL "' . $url . '" is not a GitHub repository.');
        }

        return $matches[1];
    }

    public function repositoryName()
    {
        $url = $this->get('remote.origin.url', '');
        if (!preg_match('{github.com[/:](.+?)/(.+?).git$}i', $url, $matches)) {
            throw new RuntimeException('Origin URL "' . $url . '" is not a GitHub repository.');
        }

        return $matches[2];
    }

    public function parse($repositoryPath)
    {
        $config = array();
        $process = $this->processFactory()->create('git', 'config', '--list');
        $process->setWorkingDirectory($repositoryPath);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                'Unable to read git configuration: %s',
                $process->getErrorOutput()
            ));
        }

        if (preg_match_all('/^([^=]*)=(.*)$/m', $process->getOutput(), $matches)) {
            foreach ($matches[1] as $index => $key) {
                $config[$key] = $matches[2][$index];
            }
        }

        $this->config = $config;
    }

    private $config;
    private $processFactory;
}
