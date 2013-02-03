<?php
namespace Icecave\Testing\Configuration;

use Icecave\Testing\FileSystem\FileSystem;
use Icecave\Testing\Support\Isolator;

class PHPConfigurationReader
{
    /**
     * @param FileSystem|null $fileSystem
     */
    public function __construct(FileSystem $fileSystem = null, Isolator $isolator = null)
    {
        if (null === $fileSystem) {
            $fileSystem = new FileSystem;
        }

        $this->fileSystem = $fileSystem;
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
     * @param array<string> $candidatePaths
     *
     * @return array<string,mixed>
     */
    public function read(array $candidatePaths)
    {
        $settings = array();
        foreach ($candidatePaths as $path) {
            if ($this->fileSystem()->fileExists($path)) {
                $settings = array_merge(
                    $settings,
                    $this->isolator->parse_ini_file($path)
                );
            }
        }

        return $settings;
    }

    private $fileSystem;
    private $isolator;
}
