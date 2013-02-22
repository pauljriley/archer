<?php
namespace Icecave\Archer\Travis;

use Icecave\Archer\Configuration\ConfigurationFileFinder;
use Icecave\Archer\FileSystem\FileSystem;
use Icecave\Archer\Support\Isolator;

class TravisConfigManager
{
    /**
     * @param FileSystem|null              $fileSystem
     * @param ConfigurationFileFinder|null $fileFinder
     * @param Isolator|null                $isolator
     */
    public function __construct(
        FileSystem $fileSystem = null,
        ConfigurationFileFinder $fileFinder = null,
        Isolator $isolator = null
    ) {
        if (null === $fileSystem) {
            $fileSystem = new FileSystem;
        }
        if (null === $fileFinder) {
            $fileFinder = new ConfigurationFileFinder;
        }

        $this->fileSystem = $fileSystem;
        $this->fileFinder = $fileFinder;
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @return FileSystem
     */
    public function fileSystem()
    {
        return $this->fileSystem;
    }

    /**
     * @return ConfigurationFileFinder
     */
    public function fileFinder()
    {
        return $this->fileFinder;
    }

    /**
     * @param string $packageRoot
     *
     * @return string|null
     */
    public function publicKeyCache($packageRoot)
    {
        $publicKeyPath = sprintf('%s/.travis.key', $packageRoot);
        if ($this->fileSystem()->fileExists($publicKeyPath)) {
            return $this->fileSystem()->read($publicKeyPath);
        }

        return null;
    }

    /**
     * @param string      $packageRoot
     * @param string|null $publicKey
     *
     * @return boolean
     */
    public function setPublicKeyCache($packageRoot, $publicKey)
    {
        $publicKeyPath = sprintf('%s/.travis.key', $packageRoot);

        // Key is the same as existing one, do nothing ...
        if ($this->publicKeyCache($packageRoot) === $publicKey) {
            return false;

        // Key is null, remove file ...
        } elseif (null === $publicKey) {
            $this->fileSystem()->delete($publicKeyPath);

        // Key is provided, write file ...
        } else {
            $this->fileSystem()->write($publicKeyPath, $publicKey);
        }

        return true;
    }

    /**
     * @param string $packageRoot
     *
     * @return string|null
     */
    public function secureEnvironmentCache($packageRoot)
    {
        $envPath = sprintf('%s/.travis.env', $packageRoot);
        if ($this->fileSystem()->fileExists($envPath)) {
            return $this->fileSystem()->read($envPath);
        }

        return null;
    }

    /**
     * @param string      $packageRoot
     * @param string|null $secureEnvironment
     *
     * @return boolean
     */
    public function setSecureEnvironmentCache($packageRoot, $secureEnvironment)
    {
        $envPath = sprintf('%s/.travis.env', $packageRoot);

        // Environment is the same as existing one, do nothing ...
        if ($this->secureEnvironmentCache($packageRoot) === $secureEnvironment) {
            return false;

        // Environment is null, remove file ...
        } elseif (null === $secureEnvironment) {
            $this->fileSystem()->delete($envPath);

        // Environment is provided, write file ...
        } else {
            $this->fileSystem()->write($envPath, $secureEnvironment);
        }

        return true;
    }

    /**
     * @param string $archerPackageRoot
     * @param string $packageRoot
     *
     * @return boolean
     */
    public function updateConfig($archerPackageRoot, $packageRoot)
    {
        $source = sprintf('%s/res/travis/travis.install.php', $archerPackageRoot);
        $target = sprintf('%s/.travis.install', $packageRoot);
        $this->fileSystem()->copy($source, $target);
        $this->fileSystem()->chmod($target, 0755);

        $secureEnvironment = $this->secureEnvironmentCache($packageRoot);

        if ($secureEnvironment) {
            $tokenEnvironment = sprintf('- secure: "%s"', $secureEnvironment);
        } else {
            $tokenEnvironment = '';
        }

        // Re-build travis.yml.
        $template = $this->fileSystem()->read(
            $this->findTemplatePath($archerPackageRoot, $packageRoot, $secureEnvironment !== null)
        );

        $this->fileSystem()->write(
            sprintf('%s/.travis.yml', $packageRoot),
            str_replace('{token-env}', $tokenEnvironment, $template)
        );

        // Return true if artifact publication is enabled.
        return $secureEnvironment !== null;
    }

    /**
     * @param string  $archerPackageRoot
     * @param string  $packageRoot
     * @param boolean $hasEncryptedEnvironment
     *
     * @return string
     */
    protected function findTemplatePath($archerPackageRoot, $packageRoot, $hasSecureEnvironment)
    {
        return $this->fileFinder()->find(
            $this->candidateTemplatePaths($packageRoot, $hasSecureEnvironment),
            $this->defaultTemplatePath($archerPackageRoot, $hasSecureEnvironment)
        );
    }

    /**
     * @param string  $packageRoot
     * @param boolean $hasSecureEnvironment
     *
     * @return array<string>
     */
    protected function candidateTemplatePaths($packageRoot, $hasSecureEnvironment)
    {
        return array(
            sprintf(
                '%s/test/%s',
                $packageRoot,
                $this->templateFilename($hasSecureEnvironment)
            )
        );
    }

    /**
     * @param string  $archerPackageRoot
     * @param boolean $hasSecureEnvironment
     *
     * @return string
     */
    protected function defaultTemplatePath($archerPackageRoot, $hasSecureEnvironment)
    {
        return sprintf(
            '%s/res/travis/%s',
            $archerPackageRoot,
            $this->templateFilename($hasSecureEnvironment)
        );
    }

    /**
     * @param string $archerPackageRoot
     *
     * @return string
     */
    protected function templateFilename($hasSecureEnvironment)
    {
        return 'travis.tpl.yml';
    }

    private $fileSystem;
    private $fileFinder;
    private $isolator;
}
