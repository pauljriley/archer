<?php
namespace Icecave\Archer\Git;

use PHPUnit_Framework_TestCase;

class GitConfigReaderFactoryTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->factory = new GitConfigReaderFactory;
    }

    public function testCreate()
    {
        $this->assertEquals(new GitConfigReader('foo'), $this->factory->create('foo'));
    }
}
