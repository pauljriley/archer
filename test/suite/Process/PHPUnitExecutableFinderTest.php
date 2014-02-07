<?php
namespace Icecave\Archer\Process;

use Phake;
use PHPUnit_Framework_TestCase;
use RuntimeException;

class PHPUnitExecutableFinderTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->executableFinder = Phake::mock(
            'Symfony\Component\Process\ExecutableFinder'
        );
        $this->processFactory = Phake::mock(
            'Icecave\Archer\Process\ProcessFactory'
        );
        $this->isolator = Phake::mock(
            'Icecave\Archer\Support\Isolator'
        );
        $this->finder = new PHPUnitExecutableFinder(
            $this->executableFinder,
            $this->processFactory,
            $this->isolator
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->executableFinder, $this->finder->executableFinder());
        $this->assertSame($this->processFactory, $this->finder->processFactory());
    }

    public function testConstructorDefaults()
    {
        $this->finder = new PHPUnitExecutableFinder;

        $this->assertInstanceOf(
            'Symfony\Component\Process\ExecutableFinder',
            $this->finder->executableFinder()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Process\ProcessFactory',
            $this->finder->processFactory()
        );
    }

    public function testFindGeneric()
    {
        $server = $_SERVER;
        unset($_SERVER['TRAVIS']);
        Phake::when($this->executableFinder)
            ->find(Phake::anyParameters())
            ->thenReturn('foo')
        ;
        $actual = $this->finder->find();
        $_SERVER = $server;

        $this->assertSame('foo', $actual);
        Phake::verify($this->executableFinder)->find('phpunit');
    }

    public function testFindGenericFailure()
    {
        $server = $_SERVER;
        unset($_SERVER['TRAVIS']);
        Phake::when($this->executableFinder)
            ->find(Phake::anyParameters())
            ->thenReturn(null)
        ;
        $error = null;
        try {
            $this->finder->find();
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
        Phake::when($this->processFactory)
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
        $actual = $this->finder->find();
        $_SERVER = $server;

        $this->assertSame('foo', $actual);
        Phake::inOrder(
            Phake::verify($this->processFactory)->create('rbenv', 'which', 'phpunit'),
            Phake::verify($process)->isSuccessful(),
            Phake::verify($process)->getOutput()
        );
    }

    public function testFindTravisFailure()
    {
        $server = $_SERVER;
        $_SERVER['TRAVIS'] = 'true';
        $process = Phake::mock('Symfony\Component\Process\Process');
        Phake::when($this->isolator)
            ->getenv(Phake::anyParameters())
            ->thenReturn('true')
        ;
        Phake::when($this->processFactory)
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
            $this->finder->find();
        } catch (RuntimeException $error) {
        }
        $_SERVER = $server;

        $this->assertInstanceOf('RuntimeException', $error);
        $this->assertSame('Unable to find PHPUnit executable: Foo.', $error->getMessage());
    }
}
