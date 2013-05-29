<?php
namespace Icecave\Archer\Configuration;

use Icecave\Archer\FileSystem\Exception\ReadException;
use Icecave\Archer\FileSystem\FileSystem;
use stdClass;

class ComposerConfigurationReader
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
     * @param string $projectPath
     *
     * @return stdClass
     */
    public function read($projectPath)
    {
        $composerPath = sprintf('%s/composer.json', $projectPath);
        $json = $this->fileSystem()->read($composerPath);

        $data = json_decode($json);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new ReadException($composerPath);
        }

        return $data;
    }

    private $fileSystem;
}
