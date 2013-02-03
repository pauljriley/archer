<?php
namespace Icecave\Testing\Travis;

use Icecave\Testing\Configuration\ConfigurationFileFinder;
use Icecave\Testing\GitHub\GitConfigReader;
use Icecave\Testing\Support\FileManager;
use Icecave\Testing\Support\Isolator;

class TravisConfigManager
{
    /**
     * @param FileManager                  $fileManager
     * @param ConfigurationFileFinder|null $fileFinder
     * @param Isolator|null                $isolator
     */
    public function __construct(
        FileManager $fileManager,
        ConfigurationFileFinder $fileFinder = null,
        Isolator $isolator = null
    ) {
        if (null === $fileFinder) {
            $fileFinder = new ConfigurationFileFinder;
        }

        $this->fileManager = $fileManager;
        $this->fileFinder = $fileFinder;
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @return FileManager
     */
    public function fileManager()
    {
        return $this->fileManager;
    }

    /**
     * @return ConfigurationFileFinder
     */
    public function fileFinder()
    {
        return $this->fileFinder;
    }

    /**
     * @param string          $ictPackageRoot
     * @param GitConfigReader $configReader
     *
     * @return boolean
     */
    public function updateConfig($ictPackageRoot, GitConfigReader $configReader)
    {
        $replace = array(
            '{repo-owner}' => $configReader->repositoryOwner(),
            '{repo-name}'  => $configReader->repositoryName(),
        );

        $env = $this->fileManager()->encryptedEnvironment;
        $templatePath = $this->findTemplatePath($ictPackageRoot, null !== $env);

        if (null !== $env) {
            $replace['{oauth-env}'] = $env;

            // Copy the install token script.
            $this->isolator->copy(
                $ictPackageRoot . '/res/travis/travis.before-install.php',
                $this->fileManager()->travisBeforeInstallScriptPath()
            );
            $this->isolator->chmod(
                $this->fileManager()->travisBeforeInstallScriptPath(),
                0755
            );
        }

        // Re-build travis.yml.
        $template = $this->isolator->file_get_contents($templatePath);
        $this->fileManager()->travisYaml = str_replace(array_keys($replace), array_values($replace), $template);

        // Return true if artifact publication is enabled.
        return null !== $env;
    }

    /**
     * @param string  $ictPackageRoot
     * @param boolean $hasEncryptedEnvironment
     *
     * @return string
     */
    protected function findTemplatePath($ictPackageRoot, $hasEncryptedEnvironment)
    {
        return $this->fileFinder()->find(
            $this->candidateTemplatePaths($hasEncryptedEnvironment),
            $this->defaultTemplatePath($ictPackageRoot, $hasEncryptedEnvironment)
        );
    }

    /**
     * @param boolean $hasEncryptedEnvironment
     *
     * @return array<string>
     */
    protected function candidateTemplatePaths($hasEncryptedEnvironment)
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

        $packageRoot = $this->fileManager()->packageRoot();

        return array_map(function ($path) use ($packageRoot) {
            return sprintf('%s/%s', $packageRoot, $path);
        }, $paths);
    }

    /**
     * @param string  $ictPackageRoot
     * @param boolean $hasEncryptedEnvironment
     *
     * @return string
     */
    protected function defaultTemplatePath($ictPackageRoot, $hasEncryptedEnvironment)
    {
        if ($hasEncryptedEnvironment) {
            $path = 'travis.tpl.yml';
        } else {
            $path = 'travis.no-oauth.tpl.yml';
        }

        return sprintf('%s/res/travis/%s', $ictPackageRoot, $path);
    }

    private $fileManager;
    private $fileFinder;
    private $isolator;
}
