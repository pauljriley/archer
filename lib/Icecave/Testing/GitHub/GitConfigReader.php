<?php
namespace Icecave\Testing\GitHub;

use RuntimeException;
use Icecave\Testing\Process\ProcessFactory;

class GitConfigReader
{
    /**
     * @param string              $repositoryPath
     * @param ProcessFactory|null $processFactory
     */
    public function __construct(
        $repositoryPath,
        ProcessFactory $processFactory = null
    ) {
        if (null === $processFactory) {
            $processFactory = new ProcessFactory;
        }

        $this->repositoryPath = $repositoryPath;
        $this->processFactory = $processFactory;
    }

    /**
     * @return string
     */
    public function repositoryPath()
    {
        return $this->repositoryPath;
    }

    /**
     * @return ProcessFactory
     */
    public function processFactory()
    {
        return $this->processFactory;
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $this->parse();
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }

        return $default;
    }

    /**
     * @return string
     */
    public function repositoryOwner()
    {
        list($owner, $name) = $this->parseOriginRepositoryUrl();

        return $owner;
    }

    /**
     * @return string
     */
    public function repositoryName()
    {
        list($owner, $name) = $this->parseOriginRepositoryUrl();

        return $name;
    }

    /**
     * @return tuple<string,string>
     */
    protected function parseOriginRepositoryUrl()
    {
        $url = $this->get('remote.origin.url', '');
        if (!preg_match('{github.com[/:](.+?)/(.+?).git$}i', $url, $matches)) {
            throw new RuntimeException('Origin URL "' . $url . '" is not a GitHub repository.');
        }

        return array($matches[1], $matches[2]);
    }

    protected function parse()
    {
        if (null !== $this->config) {
            return;
        }

        $config = array();
        $process = $this->processFactory()->create('git', 'config', '--list');
        $process->setWorkingDirectory($this->repositoryPath());
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

    private $repositoryPath;
    private $processFactory;
    private $config;
}
