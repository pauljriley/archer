<?php
namespace Icecave\Archer\GitHub;

class GitHubToken
{
    /**
     * @param string $token
     *
     * @return boolean True if $token is a well-formed GitHub API token; otherwise, false.
     */
    public static function validate($token)
    {
        return preg_match('/^[0-9a-f]{40}$/i', $token) === 1;
    }
}
