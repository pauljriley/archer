<?php
namespace Icecave\Archer\Travis;

use Icecave\Archer\Configuration\ConfigurationFileFinder;
use Icecave\Archer\FileSystem\FileSystem;
use Icecave\Archer\Git\GitConfigReader;
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
     * @param string          $archerPackageRoot
     * @param string          $packageRoot
     * @param GitConfigReader $configReader
     *
     * @return boolean
     */
    public function updateConfig($archerPackageRoot, $packageRoot, GitConfigReader $configReader)
    {
        $replace = array(
            '{repo-owner}' => $configReader->repositoryOwner(),
            '{repo-name}'  => $configReader->repositoryName(),
        );

        $secureEnvironment = $this->secureEnvironmentCache($packageRoot);
        $hasEncryptedEnvironment = null !== $secureEnvironment;

        if ($hasEncryptedEnvironment) {
            $replace['{oauth-env}'] = $secureEnvironment;

            // Copy the install token script.
            $travisBeforeInstallScriptPath = sprintf('%s/.travis.before-install', $packageRoot);
            $this->fileSystem()->copy(
                sprintf('%s/res/travis/travis.before-install.php', $archerPackageRoot),
                $travisBeforeInstallScriptPath
            );
            $this->fileSystem()->chmod($travisBeforeInstallScriptPath, 0755);
        }

        // Re-build travis.yml.
        $template = $this->fileSystem()->read(
            $this->findTemplatePath($archerPackageRoot, $packageRoot, $hasEncryptedEnvironment)
        );
        $this->fileSystem()->write(
            sprintf('%s/.travis.yml', $packageRoot),
            str_replace(array_keys($replace), array_values($replace), $template)
        );

        // Return true if artifact publication is enabled.
        return $hasEncryptedEnvironment;
    }

    /**
     * @param string  $archerPackageRoot
     * @param string  $packageRoot
     * @param boolean $hasEncryptedEnvironment
     *
     * @return string
     */
    protected function findTemplatePath($archerPackageRoot, $packageRoot, $hasEncryptedEnvironment)
    {
        return $this->fileFinder()->find(
            $this->candidateTemplatePaths($packageRoot, $hasEncryptedEnvironment),
            $this->defaultTemplatePath($archerPackageRoot, $hasEncryptedEnvironment)
        );
    }

    /**
     * @param string  $packageRoot
     * @param boolean $hasEncryptedEnvironment
     *
     * @return array<string>
     */
    protected function candidateTemplatePaths($packageRoot, $hasEncryptedEnvironment)
    {
        if ($hasEncryptedEnvironment) {
            $paths = array(
                'test/travis.tpl.yml',
            );
        } else {
            $paths = array(
                'test/travis.no-oauth.tpl.yml',
            );
        }

        return array_map(function ($path) use ($packageRoot) {
            return sprintf('%s/%s', $packageRoot, $path);
        }, $paths);
    }

    /**
     * @param string  $archerPackageRoot
     * @param boolean $hasEncryptedEnvironment
     *
     * @return string
     */
    protected function defaultTemplatePath($archerPackageRoot, $hasEncryptedEnvironment)
    {
        if ($hasEncryptedEnvironment) {
            $path = 'travis.tpl.yml';
        } else {
            $path = 'travis.no-oauth.tpl.yml';
        }

        return sprintf('%s/res/travis/%s', $archerPackageRoot, $path);
    }

    private $fileSystem;
    private $fileFinder;
    private $isolator;
}
