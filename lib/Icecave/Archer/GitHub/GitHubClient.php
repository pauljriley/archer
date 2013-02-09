<?php
namespace Icecave\Archer\GitHub;

use Icecave\Archer\Support\Isolator;
use InvalidArgumentException;

class GitHubClient
{
    /**
     * @param Isolator|null $isolator
     */
    public function __construct(Isolator $isolator = null)
    {
        $this->isolator = Isolator::get($isolator);
    }

    /**
     * @param string $token
     *
     * @return boolean True if $token is a well-formed GitHub API token; otherwise, false.
     */
    public static function validateToken($token)
    {
        return preg_match('/^[0-9a-f]{40}$/i', $token) === 1;
    }

    /**
     * @param string $repoOwner
     * @param string $repoName
     *
     * @return string
     */
    public function defaultBranch($repoOwner, $repoName)
    {
        $response = $this->apiGet('repos/%s/%s', $repoOwner, $repoName);

        // Planned change to GitHub API, rename master_branch => default_branch.
        if (isset($response->default_branch)) {
            return $response->default_branch;
        } elseif (isset($response->master_branch)) {
            return $response->master_branch;
        } else {
            return 'master';
        }
    }

    /**
     * @return string
     */
    public function authToken()
    {
        return $this->authToken;
    }

    /**
     * @param string $authToken
     */
    public function setAuthToken($authToken)
    {
        if (!self::validateToken($authToken)) {
            throw new InvalidArgumentException('Invalid auth token.');
        }

        $this->authToken = $authToken;
    }

    public function apiGet($resource)
    {
        $url = vsprintf(
            'https://api.github.com/' . $resource,
            array_slice(func_get_args(), 1)
        );

        if (null === $this->authToken) {
            $context = null;
        } else {
            $options = array(
                'http' => array(
                    'header' => sprintf('Authorization: token %s', $this->authToken)
                )
            );
            $context = $this->isolator->stream_context_create($options);
        }

        $response = $this->isolator->file_get_contents($url, false, $context);

        return json_decode($response);
    }

    private $authToken;
    private $isolator;
}
