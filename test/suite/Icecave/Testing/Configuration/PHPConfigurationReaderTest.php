<?php
namespace Icecave\Testing\Configuration;

use Phake;
use PHPUnit_Framework_TestCase;

class PHPConfigurationReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_isolator = Phake::mock('Icecave\Testing\Support\Isolator');
        $this->_finder = new PHPConfigurationReader($this->_isolator);
    }

    public function testReadSingle()
    {
        Phake::when($this->_isolator)
            ->is_file(Phake::anyParameters())
            ->thenReturn(true)
            ->thenReturn(false)
        ;
        Phake::when($this->_isolator)
            ->parse_ini_file(Phake::anyParameters())
            ->thenReturn(array('foo' => 'bar'))
            ->thenReturn(array('baz' => 'qux'))
        ;
        $actual = $this->_finder->read(array('doom', 'splat'));
        $expected = array('foo' => 'bar');

        $this->assertSame($expected, $actual);
        Phake::inOrder(
            Phake::verify($this->_isolator)->is_file('doom'),
            Phake::verify($this->_isolator)->parse_ini_file('doom'),
            Phake::verify($this->_isolator)->is_file('splat')
        );
        Phake::verify($this->_isolator, Phake::never())->parse_ini_file('splat');
    }

    public function testReadMultiple()
    {
        Phake::when($this->_isolator)
            ->is_file(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->_isolator)
            ->parse_ini_file(Phake::anyParameters())
            ->thenReturn(array('foo' => 'bar'))
            ->thenReturn(array('baz' => 'qux'))
        ;
        $actual = $this->_finder->read(array('doom', 'splat'));
        $expected = array('foo' => 'bar', 'baz' => 'qux');

        $this->assertSame($expected, $actual);
        Phake::inOrder(
            Phake::verify($this->_isolator)->is_file('doom'),
            Phake::verify($this->_isolator)->parse_ini_file('doom'),
            Phake::verify($this->_isolator)->is_file('splat'),
            Phake::verify($this->_isolator)->parse_ini_file('splat')
        );
    }

    public function testReadNone()
    {
        Phake::when($this->_isolator)
            ->is_file(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($this->_isolator)
            ->parse_ini_file(Phake::anyParameters())
            ->thenReturn(array('foo' => 'bar'))
            ->thenReturn(array('baz' => 'qux'))
        ;
        $actual = $this->_finder->read(array('doom', 'splat'));
        $expected = array();

        $this->assertSame($expected, $actual);
        Phake::inOrder(
            Phake::verify($this->_isolator)->is_file('doom'),
            Phake::verify($this->_isolator)->is_file('splat')
        );
        Phake::verify($this->_isolator, Phake::never())->parse_ini_file(Phake::anyParameters());
    }
}
