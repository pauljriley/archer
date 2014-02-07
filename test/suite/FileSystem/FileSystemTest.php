<?php
namespace Icecave\Archer\FileSystem;

use Phake;
use PHPUnit_Framework_TestCase;

class FileSystemTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->fileSystem = new FileSystem($this->isolator);
    }

    public function testExists()
    {
        Phake::when($this->isolator)
            ->file_exists(Phake::anyParameters())
            ->thenReturn(true)
            ->thenReturn(false)
        ;

        $this->assertTrue($this->fileSystem->exists('foo'));
        $this->assertFalse($this->fileSystem->exists('bar'));
        Phake::inOrder(
            Phake::verify($this->isolator)->file_exists('foo'),
            Phake::verify($this->isolator)->file_exists('bar')
        );
    }

    public function testExistsFailure()
    {
        Phake::when($this->isolator)
            ->file_exists(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->fileSystem->exists('foo');
    }

    public function testFileExists()
    {
        Phake::when($this->isolator)
            ->is_file(Phake::anyParameters())
            ->thenReturn(true)
            ->thenReturn(false)
        ;

        $this->assertTrue($this->fileSystem->fileExists('foo'));
        $this->assertFalse($this->fileSystem->fileExists('bar'));
        Phake::inOrder(
            Phake::verify($this->isolator)->is_file('foo'),
            Phake::verify($this->isolator)->is_file('bar')
        );
    }

    public function testFileExistsFailure()
    {
        Phake::when($this->isolator)
            ->is_file(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->fileSystem->fileExists('foo');
    }

    public function testDirectoryExists()
    {
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
            ->thenReturn(false)
        ;

        $this->assertTrue($this->fileSystem->directoryExists('foo'));
        $this->assertFalse($this->fileSystem->directoryExists('bar'));
        Phake::inOrder(
            Phake::verify($this->isolator)->is_dir('foo'),
            Phake::verify($this->isolator)->is_dir('bar')
        );
    }

    public function testDirectoryExistsFailure()
    {
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->fileSystem->directoryExists('foo');
    }

    public function testRead()
    {
        Phake::when($this->isolator)
            ->file_get_contents(Phake::anyParameters())
            ->thenReturn('bar')
        ;

        $this->assertSame('bar', $this->fileSystem->read('foo'));
        Phake::verify($this->isolator)->file_get_contents('foo');
    }

    public function testReadFailure()
    {
        Phake::when($this->isolator)
            ->file_get_contents(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->fileSystem->read('foo');
    }

    public function testListPaths()
    {
        Phake::when($this->isolator)
            ->scandir(Phake::anyParameters())
            ->thenReturn(array('.', '..', 'bar', 'baz'))
        ;

        $this->assertSame(array('bar', 'baz'), $this->fileSystem->listPaths('foo'));
        Phake::verify($this->isolator)->scandir('foo');
    }

    public function testListPathsFailure()
    {
        Phake::when($this->isolator)
            ->scandir(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->fileSystem->listPaths('foo');
    }

    public function testWrite()
    {
        Phake::when($this->isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
        ;
        $this->fileSystem->write('foo/bar', 'baz');

        Phake::inOrder(
            Phake::verify($this->isolator)->dirname('foo/bar'),
            Phake::verify($this->isolator)->is_dir('foo'),
            Phake::verify($this->isolator)->file_put_contents('foo/bar', 'baz')
        );
        Phake::verify($this->isolator, Phake::never())->mkdir(Phake::anyParameters());
    }

    public function testWriteCreateParentDirectory()
    {
        Phake::when($this->isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(false)
        ;
        $this->fileSystem->write('foo/bar', 'baz');

        Phake::inOrder(
            Phake::verify($this->isolator)->dirname('foo/bar'),
            Phake::verify($this->isolator)->is_dir('foo'),
            Phake::verify($this->isolator)->mkdir('foo', 0777, true),
            Phake::verify($this->isolator)->file_put_contents('foo/bar', 'baz')
        );
    }

    public function testWriteFailure()
    {
        Phake::when($this->isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->isolator)
            ->file_put_contents(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\WriteException'
        );
        $this->fileSystem->write('foo/bar', 'baz');
    }

    public function testWriteFailureDirname()
    {
        Phake::when($this->isolator)
            ->dirname(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->fileSystem->write('foo/bar', 'baz');
    }

    public function testCopy()
    {
        Phake::when($this->isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('bar')
        ;
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
        ;
        $this->fileSystem->copy('foo', 'bar/baz');

        Phake::inOrder(
            Phake::verify($this->isolator)->dirname('bar/baz'),
            Phake::verify($this->isolator)->is_dir('bar'),
            Phake::verify($this->isolator)->copy('foo', 'bar/baz')
        );
        Phake::verify($this->isolator, Phake::never())->mkdir(Phake::anyParameters());
    }

    public function testCopyCreateParentDirectory()
    {
        Phake::when($this->isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('bar')
        ;
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(false)
        ;
        $this->fileSystem->copy('foo', 'bar/baz');

        Phake::inOrder(
            Phake::verify($this->isolator)->dirname('bar/baz'),
            Phake::verify($this->isolator)->is_dir('bar'),
            Phake::verify($this->isolator)->mkdir('bar', 0777, true),
            Phake::verify($this->isolator)->copy('foo', 'bar/baz')
        );
    }

    public function testCopyFailure()
    {
        Phake::when($this->isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->isolator)
            ->copy(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\WriteException'
        );
        $this->fileSystem->copy('foo', 'bar/baz');
    }

    public function testCopyFailureDirname()
    {
        Phake::when($this->isolator)
            ->dirname(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->fileSystem->copy('foo', 'bar/baz');
    }

    public function testMove()
    {
        Phake::when($this->isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('bar')
        ;
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
        ;
        $this->fileSystem->move('foo', 'bar/baz');

        Phake::inOrder(
            Phake::verify($this->isolator)->dirname('bar/baz'),
            Phake::verify($this->isolator)->is_dir('bar'),
            Phake::verify($this->isolator)->rename('foo', 'bar/baz')
        );
        Phake::verify($this->isolator, Phake::never())->mkdir(Phake::anyParameters());
    }

    public function testMoveCreateParentDirectory()
    {
        Phake::when($this->isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('bar')
        ;
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(false)
        ;
        $this->fileSystem->move('foo', 'bar/baz');

        Phake::inOrder(
            Phake::verify($this->isolator)->dirname('bar/baz'),
            Phake::verify($this->isolator)->is_dir('bar'),
            Phake::verify($this->isolator)->mkdir('bar', 0777, true),
            Phake::verify($this->isolator)->rename('foo', 'bar/baz')
        );
    }

    public function testMoveFailure()
    {
        Phake::when($this->isolator)
            ->dirname(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->isolator)
            ->rename(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\WriteException'
        );
        $this->fileSystem->move('foo', 'bar/baz');
    }

    public function testMoveFailureDirname()
    {
        Phake::when($this->isolator)
            ->dirname(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\ReadException'
        );
        $this->fileSystem->move('foo', 'bar/baz');
    }

    public function testCreateDirectory()
    {
        $this->fileSystem->createDirectory('foo');

        Phake::verify($this->isolator)->mkdir('foo', 0777, true);
    }

    public function testCreateDirectoryFailure()
    {
        Phake::when($this->isolator)
            ->mkdir(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\WriteException'
        );
        $this->fileSystem->createDirectory('foo');
    }

    public function testChmod()
    {
        $this->fileSystem->chmod('foo', 0755);

        Phake::verify($this->isolator)->chmod('foo', 0755);
    }

    public function testChmodFailure()
    {
        Phake::when($this->isolator)
            ->chmod(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\WriteException'
        );
        $this->fileSystem->chmod('foo', 0755);
    }

    public function testDeleteFile()
    {
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(false)
        ;
        $this->fileSystem->delete('foo');

        Phake::inOrder(
            Phake::verify($this->isolator)->is_dir('foo'),
            Phake::verify($this->isolator)->unlink('foo')
        );
        Phake::verify($this->isolator, Phake::never())->rmdir(Phake::anyParameters());
    }

    public function testDeleteFileFailure()
    {
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($this->isolator)
            ->unlink(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\WriteException'
        );
        $this->fileSystem->delete('foo');
    }

    public function testDeleteDirectory()
    {
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
            ->thenReturn(false)
        ;
        Phake::when($this->isolator)
            ->scandir(Phake::anyParameters())
            ->thenReturn(array('.', '..', 'bar', 'baz'))
        ;
        $this->fileSystem->delete('foo');

        Phake::inOrder(
            Phake::verify($this->isolator)->is_dir('foo'),
            Phake::verify($this->isolator)->scandir('foo'),
            Phake::verify($this->isolator)->is_dir('foo/bar'),
            Phake::verify($this->isolator)->unlink('foo/bar'),
            Phake::verify($this->isolator)->is_dir('foo/baz'),
            Phake::verify($this->isolator)->unlink('foo/baz'),
            Phake::verify($this->isolator)->rmdir('foo')
        );
        Phake::verify($this->isolator, Phake::never())->unlink('foo');
    }

    public function testDeleteDirectoryFailure()
    {
        Phake::when($this->isolator)
            ->is_dir(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->isolator)
            ->scandir(Phake::anyParameters())
            ->thenReturn(array('.', '..'))
        ;
        Phake::when($this->isolator)
            ->rmdir(Phake::anyParameters())
            ->thenThrow(Phake::mock('ErrorException'))
        ;

        $this->setExpectedException(
            __NAMESPACE__ . '\Exception\WriteException'
        );
        $this->fileSystem->delete('foo');
    }
}
