<?php
namespace Icecave\Testing\FileSystem;

use Phake;
use PHPUnit_Framework_TestCase;

class FileSystemTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_isolator = Phake::mock('Icecave\Testing\Support\Isolator');
        $this->_fileSystem = new FileSystem($this->_isolator);
    }

    public function testExists()
    {
        Phake::when($this->_isolator)
            ->file_exists(Phake::anyParameters())
            ->thenReturn(true)
            ->thenReturn(false)
        ;

        $this->assertTrue($this->_fileSystem->exists('foo'));
        $this->assertFalse($this->_fileSystem->exists('bar'));
        Phake::inOrder(
            Phake::verify($this->_isolator)->file_exists('foo'),
            Phake::verify($this->_isolator)->file_exists('bar')
        );
    }

    public function testExistsFailure()
    {
        Phake::when($this->_isolator)
            ->file_exists(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->_fileSystem->exists('foo');
    }

    public function testFileExists()
    {
        Phake::when($this->_isolator)
            ->is_file(Phake::anyParameters())
            ->thenReturn(true)
            ->thenReturn(false)
        ;

        $this->assertTrue($this->_fileSystem->fileExists('foo'));
        $this->assertFalse($this->_fileSystem->fileExists('bar'));
        Phake::inOrder(
            Phake::verify($this->_isolator)->is_file('foo'),
            Phake::verify($this->_isolator)->is_file('bar')
        );
    }

    public function testFileExistsFailure()
    {
        Phake::when($this->_isolator)
            ->is_file(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->_fileSystem->fileExists('foo');
    }

    public function testDirectoryExists()
    {
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
            ->thenReturn(false)
        ;

        $this->assertTrue($this->_fileSystem->directoryExists('foo'));
        $this->assertFalse($this->_fileSystem->directoryExists('bar'));
        Phake::inOrder(
            Phake::verify($this->_isolator)->is_dir('foo'),
            Phake::verify($this->_isolator)->is_dir('bar')
        );
    }

    public function testDirectoryExistsFailure()
    {
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->_fileSystem->directoryExists('foo');
    }

    public function testRead()
    {
        Phake::when($this->_isolator)
            ->file_get_contents(Phake::anyParameters())
            ->thenReturn('bar')
        ;

        $this->assertSame('bar', $this->_fileSystem->read('foo'));
        Phake::verify($this->_isolator)->file_get_contents('foo');
    }

    public function testReadFailure()
    {
        Phake::when($this->_isolator)
            ->file_get_contents(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->_fileSystem->read('foo');
    }

    public function testListPaths()
    {
        Phake::when($this->_isolator)
            ->scandir(Phake::anyParameters())
            ->thenReturn(array('.', '..', 'bar', 'baz'))
        ;

        $this->assertSame(array('bar', 'baz'), $this->_fileSystem->listPaths('foo'));
        Phake::verify($this->_isolator)->scandir('foo');
    }

    public function testListPathsFailure()
    {
        Phake::when($this->_isolator)
            ->scandir(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->_fileSystem->listPaths('foo');
    }

    public function testWrite()
    {
        Phake::when($this->_isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
        ;
        $this->_fileSystem->write('foo/bar', 'baz');

        Phake::inOrder(
            Phake::verify($this->_isolator)->dirname('foo/bar'),
            Phake::verify($this->_isolator)->is_dir('foo'),
            Phake::verify($this->_isolator)->file_put_contents('foo/bar', 'baz')
        );
        Phake::verify($this->_isolator, Phake::never())->mkdir(Phake::anyParameters());
    }

    public function testWriteCreateParentDirectory()
    {
        Phake::when($this->_isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(false)
        ;
        $this->_fileSystem->write('foo/bar', 'baz');

        Phake::inOrder(
            Phake::verify($this->_isolator)->dirname('foo/bar'),
            Phake::verify($this->_isolator)->is_dir('foo'),
            Phake::verify($this->_isolator)->mkdir('foo', 0777, true),
            Phake::verify($this->_isolator)->file_put_contents('foo/bar', 'baz')
        );
    }

    public function testWriteFailure()
    {
        Phake::when($this->_isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->_isolator)
            ->file_put_contents(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\WriteException'
        );
        $this->_fileSystem->write('foo/bar', 'baz');
    }

    public function testWriteFailureDirname()
    {
        Phake::when($this->_isolator)
            ->dirname(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->_fileSystem->write('foo/bar', 'baz');
    }

    public function testCopy()
    {
        Phake::when($this->_isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('bar')
        ;
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
        ;
        $this->_fileSystem->copy('foo', 'bar/baz');

        Phake::inOrder(
            Phake::verify($this->_isolator)->dirname('bar/baz'),
            Phake::verify($this->_isolator)->is_dir('bar'),
            Phake::verify($this->_isolator)->copy('foo', 'bar/baz')
        );
        Phake::verify($this->_isolator, Phake::never())->mkdir(Phake::anyParameters());
    }

    public function testCopyCreateParentDirectory()
    {
        Phake::when($this->_isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('bar')
        ;
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(false)
        ;
        $this->_fileSystem->copy('foo', 'bar/baz');

        Phake::inOrder(
            Phake::verify($this->_isolator)->dirname('bar/baz'),
            Phake::verify($this->_isolator)->is_dir('bar'),
            Phake::verify($this->_isolator)->mkdir('bar', 0777, true),
            Phake::verify($this->_isolator)->copy('foo', 'bar/baz')
        );
    }

    public function testCopyFailure()
    {
        Phake::when($this->_isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->_isolator)
            ->copy(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\WriteException'
        );
        $this->_fileSystem->copy('foo', 'bar/baz');
    }

    public function testCopyFailureDirname()
    {
        Phake::when($this->_isolator)
            ->dirname(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->_fileSystem->copy('foo', 'bar/baz');
    }

    public function testMove()
    {
        Phake::when($this->_isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('bar')
        ;
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
        ;
        $this->_fileSystem->move('foo', 'bar/baz');

        Phake::inOrder(
            Phake::verify($this->_isolator)->dirname('bar/baz'),
            Phake::verify($this->_isolator)->is_dir('bar'),
            Phake::verify($this->_isolator)->rename('foo', 'bar/baz')
        );
        Phake::verify($this->_isolator, Phake::never())->mkdir(Phake::anyParameters());
    }

    public function testMoveCreateParentDirectory()
    {
        Phake::when($this->_isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('bar')
        ;
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(false)
        ;
        $this->_fileSystem->move('foo', 'bar/baz');

        Phake::inOrder(
            Phake::verify($this->_isolator)->dirname('bar/baz'),
            Phake::verify($this->_isolator)->is_dir('bar'),
            Phake::verify($this->_isolator)->mkdir('bar', 0777, true),
            Phake::verify($this->_isolator)->rename('foo', 'bar/baz')
        );
    }

    public function testMoveFailure()
    {
        Phake::when($this->_isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->_isolator)
            ->rename(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\WriteException'
        );
        $this->_fileSystem->move('foo', 'bar/baz');
    }

    public function testMoveFailureDirname()
    {
        Phake::when($this->_isolator)
            ->dirname(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->_fileSystem->move('foo', 'bar/baz');
    }

    public function testCreateDirectory()
    {
        $this->_fileSystem->createDirectory('foo');

        Phake::verify($this->_isolator)->mkdir('foo', 0777, true);
    }

    public function testCreateDirectoryFailure()
    {
        Phake::when($this->_isolator)
            ->mkdir(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\WriteException'
        );
        $this->_fileSystem->createDirectory('foo');
    }

    public function testDeleteFile()
    {
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(false)
        ;
        $this->_fileSystem->delete('foo');

        Phake::inOrder(
            Phake::verify($this->_isolator)->is_dir('foo'),
            Phake::verify($this->_isolator)->unlink('foo')
        );
        Phake::verify($this->_isolator, Phake::never())->rmdir(Phake::anyParameters());
    }

    public function testDeleteFileFailure()
    {
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($this->_isolator)
            ->unlink(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\WriteException'
        );
        $this->_fileSystem->delete('foo');
    }

    public function testDeleteDirectory()
    {
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
            ->thenReturn(false)
        ;
        Phake::when($this->_isolator)
            ->scandir(Phake::anyParameters())
            ->thenReturn(array('.', '..', 'bar', 'baz'))
        ;
        $this->_fileSystem->delete('foo');

        Phake::inOrder(
            Phake::verify($this->_isolator)->is_dir('foo'),
            Phake::verify($this->_isolator)->scandir('foo'),
            Phake::verify($this->_isolator)->is_dir('foo/bar'),
            Phake::verify($this->_isolator)->unlink('foo/bar'),
            Phake::verify($this->_isolator)->is_dir('foo/baz'),
            Phake::verify($this->_isolator)->unlink('foo/baz'),
            Phake::verify($this->_isolator)->rmdir('foo')
        );
        Phake::verify($this->_isolator, Phake::never())->unlink('foo');
    }

    public function testDeleteDirectoryFailure()
    {
        Phake::when($this->_isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->_isolator)
            ->scandir(Phake::anyParameters())
            ->thenReturn(array('.', '..'))
        ;
        Phake::when($this->_isolator)
            ->rmdir(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\WriteException'
        );
        $this->_fileSystem->delete('foo');
    }
}
