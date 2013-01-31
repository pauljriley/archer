<?php
namespace Icecave\Testing\Configuration;

use Phake;
use PHPUnit_Framework_TestCase;

class ConfigurationFileFinderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_isolator = Phake::mock('Icecave\Testing\Support\Isolator');
        $this->_finder = new ConfigurationFileFinder($this->_isolator);
    }

    public function testFindFirst()
    {
        Phake::when($this->_isolator)
            ->is_file(Phake::anyParameters())
            ->thenReturn(true)
        ;
        $actual = $this->_finder->find(
            array('foo', 'bar'),
            'baz'
        );

        $this->assertSame('foo', $actual);
        Phake::verify($this->_isolator)->is_file('foo');
        Phake::verify($this->_isolator, Phake::never())->is_file('bar');
    }

    public function testFindLast()
    {
        Phake::when($this->_isolator)
            ->is_file(Phake::anyParameters())
            ->thenReturn(false)
            ->thenReturn(true)
        ;
        $actual = $this->_finder->find(
            array('foo', 'bar'),
            'baz'
        );

        $this->assertSame('bar', $actual);
        Phake::inOrder(
            Phake::verify($this->_isolator)->is_file('foo'),
            Phake::verify($this->_isolator)->is_file('bar')
        );
    }

    public function testFindDefault()
    {
        Phake::when($this->_isolator)
            ->is_file(Phake::anyParameters())
            ->thenReturn(false)
        ;
        $actual = $this->_finder->find(
            array('foo', 'bar'),
            'baz'
        );

        $this->assertSame('baz', $actual);
        Phake::inOrder(
            Phake::verify($this->_isolator)->is_file('foo'),
            Phake::verify($this->_isolator)->is_file('bar')
        );
    }
}
