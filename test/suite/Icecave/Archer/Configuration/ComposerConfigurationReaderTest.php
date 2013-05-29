<?php
namespace Icecave\Archer\Configuration;

use Phake;
use PHPUnit_Framework_TestCase;
use stdClass;

class ComposerConfigurationReaderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        $this->reader = new ComposerConfigurationReader($this->fileSystem);
    }

    public function testConstructor()
    {
        $this->assertSame($this->fileSystem, $this->reader->fileSystem());
    }

    public function testConstructorDefaults()
    {
        $this->reader = new ComposerConfigurationReader;

        $this->assertInstanceOf(
            'Icecave\Archer\FileSystem\FileSystem',
            $this->reader->fileSystem()
        );
    }

    public function testRead()
    {
        Phake::when($this->fileSystem)
            ->read(Phake::anyParameters())
            ->thenReturn('{"foo": "bar"}')
        ;
        $actual = $this->reader->read('baz');
        $expected = new stdClass;
        $expected->foo = 'bar';

        $this->assertEquals($expected, $actual);
        Phake::verify($this->fileSystem)->read('baz/composer.json');
    }

    public function testReadFailureJson()
    {
        Phake::when($this->fileSystem)
            ->read(Phake::anyParameters())
            ->thenReturn('{')
        ;

        $this->setExpectedException(
            'Icecave\Archer\FileSystem\Exception\ReadException',
            "Unable to read from 'baz/composer.json'."
        );
        $actual = $this->reader->read('baz');
    }
}
