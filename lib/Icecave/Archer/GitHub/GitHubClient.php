<?php
namespace Icecave\Archer\GitHub;

use Icecave\Archer\Support\Isolator;

class GitHubClient
{
    /**
     * @param Isolator|null   $isolator
     */
    public function __construct(Isolator $isolator = null) {
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @param string $repoOwner
     * @param string $repoName
     *
     * @return string
     */
    public function defaultBranch($repoOwner, $repoName)
    {
        $response = $this->isolator->file_get_contents(
            sprintf(
                'https://api.github.com/repos/%s/%s',
                urlencode($repoOwner),
                urlencode($repoName)
            )
        );

        return json_decode($response)->master_branch;
    }

    private $isolator;
}
