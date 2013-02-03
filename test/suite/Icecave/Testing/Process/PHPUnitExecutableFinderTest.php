<?php
namespace Icecave\Testing\Process;

use Phake;
use PHPUnit_Framework_TestCase;
use RuntimeException;

class PHPUnitExecutableFinderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_executableFinder = Phake::mock(
            'Symfony\Component\Process\ExecutableFinder'
        );
        $this->_processFactory = Phake::mock(
            'Icecave\Testing\Process\ProcessFactory'
        );
        $this->_isolator = Phake::mock(
            'Icecave\Testing\Support\Isolator'
        );
        $this->_finder = new PHPUnitExecutableFinder(
            $this->_executableFinder,
            $this->_processFactory,
            $this->_isolator
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->_executableFinder, $this->_finder->executableFinder());
        $this->assertSame($this->_processFactory, $this->_finder->processFactory());
    }

    public function testConstructorDefaults()
    {
        $this->_finder = new PHPUnitExecutableFinder;

        $this->assertInstanceOf(
            'Symfony\Component\Process\ExecutableFinder',
            $this->_finder->executableFinder()
        );
        $this->assertInstanceOf(
            'Icecave\Testing\Process\ProcessFactory',
            $this->_finder->processFactory()
        );
    }

    public function testFindGeneric()
    {
        $server = $_SERVER;
        unset($_SERVER['TRAVIS']);
        Phake::when($this->_executableFinder)
            ->find(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        $actual = $this->_finder->find();
        $_SERVER = $server;

        $this->assertSame('foo', $actual);
        Phake::verify($this->_executableFinder)->find('phpunit');
    }

    public function testFindGenericFailure()
    {
        $server = $_SERVER;
        unset($_SERVER['TRAVIS']);
        Phake::when($this->_executableFinder)
            ->find(Phake::anyParameters())
            ->thenReturn(null)
        ;
        $error = null;
        try {
            $this->_finder->find();
        } catch (RuntimeException $error) {
        }
        $_SERVER = $server;

        $this->assertInstanceOf('RuntimeException', $error);
        $this->assertSame('Unable to find PHPUnit executable.', $error->getMessage());
    }

    public function testFindTravis()
    {
        $server = $_SERVER;
        $_SERVER['TRAVIS'] = 'true';
        $process = Phake::mock('Symfony\Component\Process\Process');
        Phake::when($this->_processFactory)
            ->create(Phake::anyParameters())
            ->thenReturn($process)
        ;
        Phake::when($process)
            ->isSuccessful(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($process)
            ->getOutput(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        $actual = $this->_finder->find();
        $_SERVER = $server;

        $this->assertSame('foo', $actual);
        Phake::inOrder(
            Phake::verify($this->_processFactory)->create('rbenv', 'which', 'phpunit'),
            Phake::verify($process)->isSuccessful(),
            Phake::verify($process)->getOutput()
        );
    }

    public function testFindTravisFailure()
    {
        $server = $_SERVER;
        $_SERVER['TRAVIS'] = 'true';
        $process = Phake::mock('Symfony\Component\Process\Process');
        Phake::when($this->_isolator)
            ->getenv(Phake::anyParameters())
            ->thenReturn('true')
        ;
        Phake::when($this->_processFactory)
            ->create(Phake::anyParameters())
            ->thenReturn($process)
        ;
        Phake::when($process)
            ->isSuccessful(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($process)
            ->getErrorOutput(Phake::anyParameters())
            ->thenReturn('Foo.')
        ;
        $error = null;
        try {
            $this->_finder->find();
        } catch (RuntimeException $error) {
        }
        $_SERVER = $server;

        $this->assertInstanceOf('RuntimeException', $error);
        $this->assertSame('Unable to find PHPUnit executable: Foo.', $error->getMessage());
    }
}
