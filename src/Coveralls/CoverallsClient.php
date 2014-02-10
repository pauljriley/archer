<?php
namespace Icecave\Archer\Coveralls;

use ErrorException;
use Icecave\Archer\Support\Isolator;

class CoverallsClient
{
    /**
     * @param Isolator|null $isolator
     */
    public function __construct(Isolator $isolator = null)
    {
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @param string $repoOwner
     * @param string $repoName
     *
     * @return string
     */
    public function exists($repoOwner, $repoName)
    {
        $uri = sprintf(
            'https://coveralls.io/r/%s/%s.json',
            urlencode($repoOwner),
            urlencode($repoName)
        );

        try {
            $response = $this->isolator->file_get_contents($uri);
        } catch (ErrorException $e) {
            return false;
        }

        $this->isolator->json_decode($response);
        if (JSON_ERROR_NONE !== $this->isolator->json_last_error()) {
            return false;
        }

        return true;
    }

    private $isolator;
}
