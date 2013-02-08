<?php
namespace Icecave\Archer\GitHub;

use Phake;
use PHPUnit_Framework_TestCase;

class TravisClientTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->_client = new GitHubClient($this->_isolator);
    }

    public function testDefaultBranch()
    {
        Phake::when($this->_isolator)
            ->file_get_contents(Phake::anyParameters())
            ->thenReturn('{"master_branch": "foo"}');

        $this->assertSame('foo', $this->_client->defaultBranch('bar', 'baz'));

        Phake::verify($this->_isolator)->file_get_contents('https://api.github.com/repos/bar/baz');
    }
}
