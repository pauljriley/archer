<?php
namespace Icecave\Testing\Process;

use Phake;
use PHPUnit_Framework_TestCase;

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
        Phake::when($this->_isolator)
            ->getenv(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($this->_executableFinder)
            ->find(Phake::anyParameters())
            ->thenReturn('foo')
        ;

        $this->assertSame('foo', $this->_finder->find());
        Phake::inOrder(
            Phake::verify($this->_isolator)->getenv('TRAVIS'),
            Phake::verify($this->_executableFinder)->find('phpunit')
        );
    }

    public function testFindGenericFailure()
    {
        Phake::when($this->_isolator)
            ->getenv(Phake::anyParameters())
            ->thenReturn(false)
        ;
        Phake::when($this->_executableFinder)
            ->find(Phake::anyParameters())
            ->thenReturn(null)
        ;

        $this->setExpectedException(
            'RuntimeException',
            'Unable to find PHPUnit executable.'
        );
        $this->_finder->find();
    }

    public function testFindTravis()
    {
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
            ->thenReturn(true)
        ;
        Phake::when($process)
            ->getOutput(Phake::anyParameters())
            ->thenReturn('foo')
        ;

        $this->assertSame('foo', $this->_finder->find());
        Phake::inOrder(
            Phake::verify($this->_isolator)->getenv('TRAVIS'),
            Phake::verify($this->_processFactory)->create('rbenv', 'which', 'phpunit'),
            Phake::verify($process)->isSuccessful(),
            Phake::verify($process)->getOutput()
        );
    }

    public function testFindTravisFailure()
    {
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

        $this->setExpectedException(
            'RuntimeException',
            'Unable to find PHPUnit executable: Foo.'
        );
        $this->_finder->find();
    }
}
