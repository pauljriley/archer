<?php
namespace Icecave\Archer\Process;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Process\Process;

class ProcessFactoryTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->factory = new ProcessFactory;
    }

    public function testFactory()
    {
        $expected = new Process("'foo' 'bar'");

        $this->assertEquals($expected, $this->factory->create('foo', 'bar'));
        $this->assertEquals($expected, $this->factory->createFromArray(array('foo', 'bar')));
    }
}
