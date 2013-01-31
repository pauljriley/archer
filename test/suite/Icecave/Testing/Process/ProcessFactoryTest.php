<?php
namespace Icecave\Testing\Process;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Process\Process;

class ProcessFactoryTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_factory = new ProcessFactory;
    }

    public function testFactory()
    {
        $expected = new Process("'foo' 'bar'");

        $this->assertEquals($expected, $this->_factory->create('foo', 'bar'));
        $this->assertEquals($expected, $this->_factory->createFromArray(array('foo', 'bar')));
    }
}
