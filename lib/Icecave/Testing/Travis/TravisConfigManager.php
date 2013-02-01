<?php
namespace Icecave\Testing\Travis;

use Icecave\Testing\GitHub\GitConfigReader;
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

    public function updateConfig(GitConfigReader $configReader)
    {
        $replace = array(
            '{repo-owner}' => $configReader->repositoryOwner(),
            '{repo-name}'  => $configReader->repositoryName(),
        );

        $env = $this->fileManager->encryptedEnvironment;

        if (null === $env) {
            $filename = 'travis.no-oauth.yaml';
        } else {
            $filename = 'travis.yaml';
            $replace['{oauth-env}'] = $env;

            // Copy the install token script.
            $this->isolator->copy($this->packageRoot . '/res/travis/travis.before-install.php', $this->fileManager->travisBeforeInstallScriptPath());
            $this->isolator->chmod($this->fileManager->travisBeforeInstallScriptPath(), 0755);
        }

        // Re-build travis.yml.
        $config = $this->isolator->file_get_contents($this->packageRoot . '/res/travis/' . $filename);
        $this->fileManager->travisYaml = str_replace(array_keys($replace), array_values($replace), $config);

        // Return true if artifact publication is enabled.
        return null !== $env;
    }

    private $packageRoot;
    private $fileManager;
    private $isolator;
}
