<?php
namespace Icecave\Archer\Configuration;

use Phake;
use PHPUnit_Framework_TestCase;

class ConfigurationFileFinderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        $this->_finder = new ConfigurationFileFinder($this->_fileSystem);
    }

    public function testConstructor()
    {
        $this->assertSame($this->_fileSystem, $this->_finder->fileSystem());
    }

    public function testConstructorDefaults()
    {
        $this->_finder = new ConfigurationFileFinder;

        $this->assertInstanceOf(
            'Icecave\Archer\FileSystem\FileSystem',
            $this->_finder->fileSystem()
        );
    }

    public function testFindFirst()
    {
        Phake::when($this->_fileSystem)
            ->fileExists(Phake::anyParameters())
            ->thenReturn(true)
        ;
        $actual = $this->_finder->find(
            array('foo', 'bar'),
            'baz'
        );

        $this->assertSame('foo', $actual);
        Phake::verify($this->_fileSystem)->fileExists('foo');
        Phake::verify($this->_fileSystem, Phake::never())->fileExists('bar');
    }

    public function testFindLast()
    {
        Phake::when($this->_fileSystem)
            ->fileExists(Phake::anyParameters())
            ->thenReturn(false)
            ->thenReturn(true)
        ;
        $actual = $this->_finder->find(
            array('foo', 'bar'),
            'baz'
        );

        $this->assertSame('bar', $actual);
        Phake::inOrder(
            Phake::verify($this->_fileSystem)->fileExists('foo'),
            Phake::verify($this->_fileSystem)->fileExists('bar')
        );
    }

    public function testFindDefault()
    {
        Phake::when($this->_fileSystem)
            ->fileExists(Phake::anyParameters())
            ->thenReturn(false)
        ;
        $actual = $this->_finder->find(
            array('foo', 'bar'),
            'baz'
        );

        $this->assertSame('baz', $actual);
        Phake::inOrder(
            Phake::verify($this->_fileSystem)->fileExists('foo'),
            Phake::verify($this->_fileSystem)->fileExists('bar')
        );
    }
}
