<?php
namespace Icecave\Archer\Git;

class GitConfigReaderFactory
{
    /**
     * @param string $repositoryPath
     *
     * @return GitConfigReader
     */
    public function create($repositoryPath)
    {
        return new GitConfigReader($repositoryPath);
    }
}
