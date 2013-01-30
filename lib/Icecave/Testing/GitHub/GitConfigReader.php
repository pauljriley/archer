<?php
namespace Icecave\Testing\GitHub;

use Exception;
use RuntimeException;
use Icecave\Testing\Exception\ExecutionException;
use Icecave\Testing\Support\Isolator;
use Icecave\Testing\Support\ShellExecutor;

class GitConfigReader
{
    public function __construct(ShellExecutor $executor = null, Isolator $isolator = null)
    {
        $this->isolator = Isolator::get($isolator);

        if (null === $executor) {
            $executor = new ShellExecutor($this->isolator);
        }

        $this->executor = $executor;
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
        $output = $this->executor->execute('git config --list', $repositoryPath);

        foreach ($output as $line) {
            $pos = strpos($line, '=');
            if (false !== $pos) {
                $config[substr($line, 0, $pos)] = substr($line, $pos + 1);
            }
        }

        $this->config = $config;
    }

    private $config;
    private $executor;
    private $isolator;
}
