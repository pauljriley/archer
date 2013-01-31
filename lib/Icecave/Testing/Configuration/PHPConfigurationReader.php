<?php
namespace Icecave\Testing\Configuration;

use Icecave\Testing\Support\Isolator;

class PHPConfigurationReader
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
     *
     * @return array<string,mixed>
     */
    public function read(array $candidatePaths)
    {
        $settings = array();
        foreach ($candidatePaths as $path) {
            if ($this->isolator->is_file($path)) {
                $settings = array_merge(
                    $settings,
                    $this->isolator->parse_ini_file($path)
                );
            }
        }

        return $settings;
    }

    private $isolator;
}
