<?php
namespace Icecave\Archer\Configuration;

use Phake;
use PHPUnit_Framework_TestCase;

class PHPConfigurationReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        $this->isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->reader = new PHPConfigurationReader(
            $this->fileSystem,
            $this->isolator
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->fileSystem, $this->reader->fileSystem());
    }

    public function testConstructorDefaults()
    {
        $this->reader = new PHPConfigurationReader;

        $this->assertInstanceOf(
            'Icecave\Archer\FileSystem\FileSystem',
            $this->reader->fileSystem()
        );
    }

    public function testReadSingle()
    {
        Phake::when($this->fileSystem)
            ->fileExists(Phake::anyParameters())
            ->thenReturn(true)
            ->thenReturn(false)
        ;
        Phake::when($this->isolator)
            ->parse_ini_file(Phake::anyParameters())
            ->thenReturn(array('foo' => 'bar'))
            ->thenReturn(array('baz' => 'qux'))
        ;
        $actual = $this->reader->read(array('doom', 'splat'));
        $expected = array('foo' => 'bar');

        $this->assertSame($expected, $actual);
        Phake::inOrder(
            Phake::verify($this->fileSystem)->fileExists('doom'),
            Phake::verify($this->isolator)->parse_ini_file('doom'),
            Phake::verify($this->fileSystem)->fileExists('splat')
        );
        Phake::verify($this->isolator, Phake::never())->parse_ini_file('splat');
    }

    public function testReadMultiple()
    {
        Phake::when($this->fileSystem)
            ->fileExists(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->isolator)
            ->parse_ini_file(Phake::anyParameters())
            ->thenReturn(array('foo' => 'bar'))
            ->thenReturn(array('baz' => 'qux'))
        ;
        $actual = $this->reader->read(array('doom', 'splat'));
        $expected = array('foo' => 'bar', 'baz' => 'qux');

        $this->assertSame($expected, $actual);
        Phake::inOrder(
            Phake::verify($this->fileSystem)->fileExists('doom'),
            Phake::verify($this->isolator)->parse_ini_file('doom'),
            Phake::verify($this->fileSystem)->fileExists('splat'),
            Phake::verify($this->isolator)->parse_ini_file('splat')
        );
    }

    public function testReadNone()
    {
        Phake::when($this->fileSystem)
            ->fileExists(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($this->isolator)
            ->parse_ini_file(Phake::anyParameters())
            ->thenReturn(array('foo' => 'bar'))
            ->thenReturn(array('baz' => 'qux'))
        ;
        $actual = $this->reader->read(array('doom', 'splat'));
        $expected = array();

        $this->assertSame($expected, $actual);
        Phake::inOrder(
            Phake::verify($this->fileSystem)->fileExists('doom'),
            Phake::verify($this->fileSystem)->fileExists('splat')
        );
        Phake::verify($this->isolator, Phake::never())->parse_ini_file(Phake::anyParameters());
    }
}
