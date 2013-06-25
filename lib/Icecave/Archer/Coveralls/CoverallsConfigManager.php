<?php
namespace Icecave\Archer\Coveralls;

use Icecave\Archer\Configuration\ConfigurationFileFinder;
use Icecave\Archer\FileSystem\FileSystem;
use Icecave\Archer\Support\Isolator;

class CoverallsConfigManager
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
     * @param string $archerPackageRoot
     * @param string $packageRoot
     *
     * @return string
     */
    public function createConfig($archerPackageRoot, $packageRoot)
    {
        $template = $this->fileSystem()->read(
            $this->findTemplatePath($archerPackageRoot, $packageRoot)
        );

        if ($this->fileSystem()->exists(sprintf('%s/src', $packageRoot))) {
            $libDir = 'src';
        } else {
            $libDir = 'lib';
        }

        $path = sprintf(
            '%s/artifacts/tests/coverage/coveralls.yml',
            $packageRoot
        );

        $this->fileSystem()->write(
            $path,
            str_replace('{lib-dir}', $libDir, $template)
        );

        return $path;
    }

    /**
     * @param string $archerPackageRoot
     * @param string $packageRoot
     *
     * @return string
     */
    protected function findTemplatePath($archerPackageRoot, $packageRoot)
    {
        return $this->fileFinder()->find(
            $this->candidateTemplatePaths($packageRoot),
            $this->defaultTemplatePath($archerPackageRoot)
        );
    }

    /**
     * @param string $packageRoot
     *
     * @return array<string>
     */
    protected function candidateTemplatePaths($packageRoot)
    {
        return array(
            sprintf('%s/.coveralls.yml', $packageRoot),
            sprintf('%s/coveralls.tpl.yml', $packageRoot),
            sprintf('%s/test/.coveralls.yml', $packageRoot),
            sprintf('%s/test/coveralls.yml', $packageRoot),
            sprintf('%s/test/coveralls.tpl.yml', $packageRoot),
        );
    }

    /**
     * @param string $archerPackageRoot
     *
     * @return string
     */
    protected function defaultTemplatePath($archerPackageRoot)
    {
        return sprintf(
            '%s/res/coveralls/coveralls.tpl.yml',
            $archerPackageRoot
        );
    }

    private $fileSystem;
    private $fileFinder;
    private $isolator;
}
