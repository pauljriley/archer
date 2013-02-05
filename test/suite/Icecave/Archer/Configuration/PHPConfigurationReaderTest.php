<?php
namespace Icecave\Archer\Configuration;

use Phake;
use PHPUnit_Framework_TestCase;

class PHPConfigurationReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        $this->_isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->_reader = new PHPConfigurationReader(
            $this->_fileSystem,
            $this->_isolator
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->_fileSystem, $this->_reader->fileSystem());
    }

    public function testConstructorDefaults()
    {
        $this->_reader = new PHPConfigurationReader;

        $this->assertInstanceOf(
            'Icecave\Archer\FileSystem\FileSystem',
            $this->_reader->fileSystem()
        );
    }

    public function testReadSingle()
    {
        Phake::when($this->_fileSystem)
            ->fileExists(Phake::anyParameters())
            ->thenReturn(true)
            ->thenReturn(false)
        ;
        Phake::when($this->_isolator)
            ->parse_ini_file(Phake::anyParameters())
            ->thenReturn(array('foo' => 'bar'))
            ->thenReturn(array('baz' => 'qux'))
        ;
        $actual = $this->_reader->read(array('doom', 'splat'));
        $expected = array('foo' => 'bar');

        $this->assertSame($expected, $actual);
        Phake::inOrder(
            Phake::verify($this->_fileSystem)->fileExists('doom'),
            Phake::verify($this->_isolator)->parse_ini_file('doom'),
            Phake::verify($this->_fileSystem)->fileExists('splat')
        );
        Phake::verify($this->_isolator, Phake::never())->parse_ini_file('splat');
    }

    public function testReadMultiple()
    {
        Phake::when($this->_fileSystem)
            ->fileExists(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->_isolator)
            ->parse_ini_file(Phake::anyParameters())
            ->thenReturn(array('foo' => 'bar'))
            ->thenReturn(array('baz' => 'qux'))
        ;
        $actual = $this->_reader->read(array('doom', 'splat'));
        $expected = array('foo' => 'bar', 'baz' => 'qux');

        $this->assertSame($expected, $actual);
        Phake::inOrder(
            Phake::verify($this->_fileSystem)->fileExists('doom'),
            Phake::verify($this->_isolator)->parse_ini_file('doom'),
            Phake::verify($this->_fileSystem)->fileExists('splat'),
            Phake::verify($this->_isolator)->parse_ini_file('splat')
        );
    }

    public function testReadNone()
    {
        Phake::when($this->_fileSystem)
            ->fileExists(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($this->_isolator)
            ->parse_ini_file(Phake::anyParameters())
            ->thenReturn(array('foo' => 'bar'))
            ->thenReturn(array('baz' => 'qux'))
        ;
        $actual = $this->_reader->read(array('doom', 'splat'));
        $expected = array();

        $this->assertSame($expected, $actual);
        Phake::inOrder(
            Phake::verify($this->_fileSystem)->fileExists('doom'),
            Phake::verify($this->_fileSystem)->fileExists('splat')
        );
        Phake::verify($this->_isolator, Phake::never())->parse_ini_file(Phake::anyParameters());
    }
}
