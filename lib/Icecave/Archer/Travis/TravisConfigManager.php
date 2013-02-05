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

        $encryptedEnvironmentPath = sprintf('%s/.travis.env', $packageRoot);
        $hasEncryptedEnvironment = $this->fileSystem()->fileExists($encryptedEnvironmentPath);
        if ($hasEncryptedEnvironment) {
            $env = $this->fileSystem()->read($encryptedEnvironmentPath);
            $replace['{oauth-env}'] = $env;

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
