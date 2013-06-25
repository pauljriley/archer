<?php
namespace Icecave\Archer\Coveralls;

use Phake;
use PHPUnit_Framework_TestCase;

class CoverallsConfigManagerTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        $this->fileFinder = Phake::mock('Icecave\Archer\Configuration\ConfigurationFileFinder');
        $this->isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->manager = new CoverallsConfigManager(
            $this->fileSystem,
            $this->fileFinder,
            $this->isolator
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->fileSystem, $this->manager->fileSystem());
        $this->assertSame($this->fileFinder, $this->manager->fileFinder());
    }

    public function testConstructorDefaults()
    {
        $this->manager = new CoverallsConfigManager;

        $this->assertInstanceOf(
            'Icecave\Archer\FileSystem\FileSystem',
            $this->manager->fileSystem()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Configuration\ConfigurationFileFinder',
            $this->manager->fileFinder()
        );
    }

    public function testCreateConfig()
    {
        Phake::when($this->fileFinder)
            ->find(Phake::anyParameters())
            ->thenReturn('/path/to/coveralls.tpl.yml');
        Phake::when($this->fileSystem)
            ->read('/path/to/coveralls.tpl.yml')
            ->thenReturn("foo\n{lib-dir}\nbar");
        Phake::when($this->fileSystem)
            ->exists('/path/to/project/src')
            ->thenReturn(false);

        $this->assertSame(
            '/path/to/project/artifacts/tests/coverage/coveralls.yml',
            $this->manager->createConfig(
                '/path/to/archer',
                '/path/to/project'
            )
        );
        Phake::inOrder(
            Phake::verify($this->fileFinder)->find(
                array(
                    '/path/to/project/.coveralls.yml',
                    '/path/to/project/coveralls.tpl.yml',
                    '/path/to/project/test/.coveralls.yml',
                    '/path/to/project/test/coveralls.yml',
                    '/path/to/project/test/coveralls.tpl.yml',
                ),
                '/path/to/archer/res/coveralls/coveralls.tpl.yml'
            ),
            Phake::verify($this->fileSystem)->write(
                '/path/to/project/artifacts/tests/coverage/coveralls.yml',
                "foo\nlib\nbar"
            )
        );
    }

    public function testCreateConfigSrcAsLibDir()
    {
        Phake::when($this->fileFinder)
            ->find(Phake::anyParameters())
            ->thenReturn('/path/to/coveralls.tpl.yml');
        Phake::when($this->fileSystem)
            ->read('/path/to/coveralls.tpl.yml')
            ->thenReturn("foo\n{lib-dir}\nbar");
        Phake::when($this->fileSystem)
            ->exists('/path/to/project/src')
            ->thenReturn(true);

        $this->assertSame(
            '/path/to/project/artifacts/tests/coverage/coveralls.yml',
            $this->manager->createConfig(
                '/path/to/archer',
                '/path/to/project'
            )
        );
        Phake::inOrder(
            Phake::verify($this->fileFinder)->find(
                array(
                    '/path/to/project/.coveralls.yml',
                    '/path/to/project/coveralls.tpl.yml',
                    '/path/to/project/test/.coveralls.yml',
                    '/path/to/project/test/coveralls.yml',
                    '/path/to/project/test/coveralls.tpl.yml',
                ),
                '/path/to/archer/res/coveralls/coveralls.tpl.yml'
            ),
            Phake::verify($this->fileSystem)->write(
                '/path/to/project/artifacts/tests/coverage/coveralls.yml',
                "foo\nsrc\nbar"
            )
        );
    }
}
