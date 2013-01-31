<?php
namespace Icecave\Testing\Configuration;

use Icecave\Testing\Support\Isolator;

class ConfigurationFileFinder
{
    /**
     * @param Isolator|null $isolator
     */
    public function __construct(Isolator $isolator = null)
    {
        $this->isolator = Isolator::get($isolator);
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
            if ($this->isolator->is_file($path)) {
                return $path;
            }
        }

        return $defaultPath;
    }

    private $isolator;
}
