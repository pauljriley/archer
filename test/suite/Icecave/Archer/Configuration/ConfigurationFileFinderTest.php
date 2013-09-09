<?php
namespace Icecave\Archer\Configuration;

use Phake;
use PHPUnit_Framework_TestCase;

class ConfigurationFileFinderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        $this->finder = new ConfigurationFileFinder($this->fileSystem);
    }

    public function testConstructor()
    {
        $this->assertSame($this->fileSystem, $this->finder->fileSystem());
    }

    public function testConstructorDefaults()
    {
        $this->finder = new ConfigurationFileFinder;

        $this->assertInstanceOf(
            'Icecave\Archer\FileSystem\FileSystem',
            $this->finder->fileSystem()
        );
    }

    public function testFindFirst()
    {
        Phake::when($this->fileSystem)
            ->fileExists(Phake::anyParameters())
            ->thenReturn(true)
        ;
        $actual = $this->finder->find(
            array('foo', 'bar'),
            'baz'
        );

        $this->assertSame('foo', $actual);
        Phake::verify($this->fileSystem)->fileExists('foo');
        Phake::verify($this->fileSystem, Phake::never())->fileExists('bar');
    }

    public function testFindLast()
    {
        Phake::when($this->fileSystem)
            ->fileExists(Phake::anyParameters())
            ->thenReturn(false)
            ->thenReturn(true)
        ;
        $actual = $this->finder->find(
            array('foo', 'bar'),
            'baz'
        );

        $this->assertSame('bar', $actual);
        Phake::inOrder(
            Phake::verify($this->fileSystem)->fileExists('foo'),
            Phake::verify($this->fileSystem)->fileExists('bar')
        );
    }

    public function testFindDefault()
    {
        Phake::when($this->fileSystem)
            ->fileExists(Phake::anyParameters())
            ->thenReturn(false)
        ;
        $actual = $this->finder->find(
            array('foo', 'bar'),
            'baz'
        );

        $this->assertSame('baz', $actual);
        Phake::inOrder(
            Phake::verify($this->fileSystem)->fileExists('foo'),
            Phake::verify($this->fileSystem)->fileExists('bar')
        );
    }
}
