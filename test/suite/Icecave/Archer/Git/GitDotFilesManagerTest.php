<?php
namespace Icecave\Archer\Git;

use PHPUnit_Framework_TestCase;
use Phake;

class GitDotFilesManagerTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        $this->_manager = new GitDotFilesManager(
            $this->_fileSystem
        );

        Phake::when($this->_fileSystem)
            ->exists('/path/to/project/.gitignore')
            ->thenReturn(false);

        Phake::when($this->_fileSystem)
            ->exists('/path/to/project/.gitattributes')
            ->thenReturn(true);
    }

    public function testConstructor()
    {
        $this->assertSame($this->_fileSystem, $this->_manager->fileSystem());
    }

    public function testConstructorDefaults()
    {
        $this->_manager = new GitDotFilesManager;

        $this->assertInstanceOf(
            'Icecave\Archer\FileSystem\FileSystem',
            $this->_manager->fileSystem()
        );
    }

    public function testUpdateDotFiles()
    {
        $result = $this->_manager->updateDotFiles('/path/to/archer', '/path/to/project');

        $expected = array(
            '.gitignore'     => true,
            '.gitattributes' => false
        );

        $this->assertSame($expected, $result);

        Phake::inOrder(
            Phake::verify($this->_fileSystem)->exists('/path/to/project/.gitignore'),
            Phake::verify($this->_fileSystem)->copy('/path/to/archer/res/git/gitignore', '/path/to/project/.gitignore'),
            Phake::verify($this->_fileSystem)->exists('/path/to/project/.gitattributes')
        );

        Phake::verify($this->_fileSystem, Phake::never())->copy('/path/to/archer/res/git/gitattributes', '/path/to/project/.gitattributes');
    }

    public function testUpdateDotFilesWithOverwrite()
    {
        $result = $this->_manager->updateDotFiles('/path/to/archer', '/path/to/project', true);

        $expected = array(
            '.gitignore'     => true,
            '.gitattributes' => true
        );

        $this->assertSame($expected, $result);

        Phake::inOrder(
            Phake::verify($this->_fileSystem)->copy('/path/to/archer/res/git/gitignore', '/path/to/project/.gitignore'),
            Phake::verify($this->_fileSystem)->copy('/path/to/archer/res/git/gitattributes', '/path/to/project/.gitattributes')
        );
    }
}
