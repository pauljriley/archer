<?php
namespace Icecave\Archer\GitHub;

use PHPUnit_Framework_TestCase;

class GitHubTokenTest extends PHPUnit_Framework_TestCase
{
    public function testValidate()
    {
        $this->assertTrue(GitHubToken::validate('b1a94b90073382b330f601ef198bb0729b0168aa'));

        // Too short ...
        $this->assertFalse(GitHubToken::validate('b1a94b90073382b330f601ef198bb0729b0168a'));

        // Too long ...
        $this->assertFalse(GitHubToken::validate('b1a94b90073382b330f601ef198bb0729b0168aaa'));

        // Invalid character ...
        $this->assertFalse(GitHubToken::validate('b1a94b90073382b330f601ef198bb0729b0168aq'));

        // Empty ...
        $this->assertFalse(GitHubToken::validate(''));
    }
}
