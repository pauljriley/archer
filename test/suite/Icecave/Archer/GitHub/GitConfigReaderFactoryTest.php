<?php
namespace Icecave\Archer\GitHub;

use PHPUnit_Framework_TestCase;

class GitConfigReaderFactoryTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_factory = new GitConfigReaderFactory;
    }

    public function testCreate()
    {
        $this->assertEquals(new GitConfigReader('foo'), $this->_factory->create('foo'));
    }
}
