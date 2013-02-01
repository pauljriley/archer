<?php
namespace Icecave\Testing\Support;

use RuntimeException;
use Icecave\Testing\Support\Isolator;

class FileManager
{
    public function __construct(Isolator $isolator = null)
    {
        $this->isolator = Isolator::get($isolator);
    }

    public function packageRoot()
    {
        return $this->packageRoot;
    }

    public function setPackageRoot($packageRoot)
    {
        $this->packageRoot = $packageRoot;
    }

    public function __get($name)
    {
        $path = $this->{$name . 'Path'}();

        if ($this->isolator->file_exists($path)) {
            return $this->isolator->file_get_contents($path);
        }

        return null;
    }

    public function __set($name, $content)
    {
        $path = $this->{$name . 'Path'}();
        $this->isolator->file_put_contents($path, $content);
    }

    public function packageRootPath()
    {
        if (null === $this->packageRoot) {
            throw new RuntimeException('No package root has been set.');
        }

        return $this->packageRoot;
    }

    public function composerJsonPath()
    {
        return $this->packageRootPath() . '/composer.json';
    }

    public function gitIgnorePath()
    {
        return $this->packageRootPath() . '/.gitignore';
    }

    public function gitAttributesPath()
    {
        return $this->packageRootPath() . '/.gitattributes';
    }

    public function publicKeyPath()
    {
        return $this->packageRootPath() . '/.travis.key';
    }

    public function encryptedEnvironmentPath()
    {
        return $this->packageRootPath() . '/.travis.env';
    }

    public function travisYamlPath()
    {
        return $this->packageRootPath() . '/.travis.yml';
    }

    public function travisBeforeInstallScriptPath()
    {
        return $this->packageRootPath() . '/.travis.before-install';
    }

    private $packageRoot;
    private $isolator;
}
