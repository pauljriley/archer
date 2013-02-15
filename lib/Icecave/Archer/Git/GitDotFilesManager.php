<?php
namespace Icecave\Archer\Git;

use Icecave\Archer\FileSystem\FileSystem;

class GitDotFilesManager
{
    /**
     * @param FileSystem|null $fileSystem
     * @param Isolator|null   $isolator
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
     * @param string  $archerPackageRoot
     * @param string  $packageRoot
     * @param boolean $overwrite
     *
     * @return array<string, boolean>
     */
    public function updateDotFiles($archerPackageRoot, $packageRoot, $overwrite = false)
    {
        $files = array(
            '.gitignore'     => false,
            '.gitattributes' => false
        );

        foreach (array_keys($files) as $filename) {
            $targetPath = $packageRoot . '/' . $filename;
            if ($overwrite || !$this->fileSystem->exists($targetPath)) {
                $this->fileSystem->copy(
                    sprintf('%s/res/git/%s', $archerPackageRoot, substr($filename, 1)),
                    $targetPath
                );
                $files[$filename] = true;
            }
        }

        return $files;
    }

    private $fileSystem;
}
