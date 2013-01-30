<?php
namespace Icecave\Testing\Travis;

use Icecave\Testing\Support\FileManager;
use Icecave\Testing\Support\Isolator;

class TravisConfigManager
{
    public function __construct(FileManager $fileManager, Isolator $isolator = null)
    {
        $this->fileManager = $fileManager;
        $this->isolator = Isolator::get($isolator);
    }

    public function setPackageRoot($packageRoot)
    {
        $this->packageRoot = $packageRoot;
    }

    public function updateConfig()
    {
        $env = $this->fileManager->encryptedEnvironment;

        if (null === $env) {
            $config = $this->isolator->file_get_contents($this->packageRoot . '/res/travis/travis.yaml');
        } else {
            $config = $this->isolator->file_get_contents($this->packageRoot . '/res/travis/travis.artifacts.yaml');
            $config = str_replace('%env%', $env, $config);
        }

        $this->fileManager->travisYaml = $config;

        // Return true if artifact publication is enabled.
        return null !== $env;
    }

    private $packageRoot;
    private $fileManager;
    private $isolator;
}
