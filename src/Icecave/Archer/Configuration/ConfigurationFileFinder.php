<?php
namespace Icecave\Archer\Configuration;

use Icecave\Archer\FileSystem\FileSystem;

class ConfigurationFileFinder
{
    /**
     * @param FileSystem|null $fileSystem
     */
    public function __construct(FileSystem $fileSystem = null)
    {
        if (null === $fileSystem) {
            $fileSystem = new FileSystem;
        }

        $this->fileSystem = $fileSystem;
    }

    /**
     * @return FileSystem
     */
    public function fileSystem()
    {
        return $this->fileSystem;
    }

    /**
     * @param array<string> $candidatePaths
     * @param string        $defaultPath
     *
     * @return string
     */
    public function find(array $candidatePaths, $defaultPath)
    {
        foreach ($candidatePaths as $path) {
            if ($this->fileSystem()->fileExists($path)) {
                return $path;
            }
        }

        return $defaultPath;
    }

    private $fileSystem;
}
