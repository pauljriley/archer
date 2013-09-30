<?php
namespace Icecave\Archer\Console;

use Icecave\Archer\Support\Isolator;
use Phake;
use PHPUnit_Framework_TestCase;
use ReflectionObject;
use Symfony\Component\Console\Input\ArrayInput;

class ApplicationTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        Command\Internal\AbstractInternalCommand::setIsEnabled(null);

        $this->fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        $this->isolator = Phake::mock('Icecave\Archer\Support\Isolator');
        $this->application = Phake::partialMock(
            __NAMESPACE__ . '\Application',
            'foo',
            $this->fileSystem,
            $this->isolator
        );
        $this->reflector = new ReflectionObject($this->application);
    }

    public function testConstructor()
    {
        $this->assertSame('Archer', $this->application->getName());
        $this->assertSame('1.1.0', $this->application->getVersion());

        $this->assertSame('foo', $this->application->packageRoot());
        $this->assertSame($this->fileSystem, $this->application->fileSystem());
    }

    public function testConstructorDefaults()
    {
        $this->application = new Application(
            'foo'
        );

        $this->assertInstanceOf(
            'Icecave\Archer\FileSystem\FileSystem',
            $this->application->fileSystem()
        );
    }

    public function testEnabledCommands()
    {
        $expected = array(
            'help',
            'list',
            'coverage',
            'documentation',
            'test',
            'update',
        );

        $this->assertSame($expected, array_keys($this->application->all()));
    }

    public function testEnabledCommandsArcher()
    {
        Command\Internal\AbstractInternalCommand::setIsEnabled(null);
        $this->fileSystem = Phake::mock('Icecave\Archer\FileSystem\FileSystem');
        Phake::when($this->fileSystem)
            ->fileExists(Phake::anyParameters())
            ->thenReturn(true)
        ;
        Phake::when($this->fileSystem)
            ->read(Phake::anyParameters())
            ->thenReturn('{"name": "icecave/archer"}')
        ;
        $this->application = new Application('foo', $this->fileSystem, $this->isolator);
        $expected = array(
            'help',
            'list',
            'coverage',
            'documentation',
            'test',
            'update',
            'internal:update-binaries',
        );

        Phake::inOrder(
            Phake::verify($this->fileSystem)->fileExists('foo/composer.json'),
            Phake::verify($this->fileSystem)->read('foo/composer.json')
        );
        $this->assertSame($expected, array_keys($this->application->all()));
    }

    public function testEnabledCommandsTravis()
    {
        Phake::when($this->isolator)
            ->getenv('TRAVIS')
            ->thenReturn('true');

        $this->application = new Application('foo', $this->fileSystem, $this->isolator);
        $expected = array(
            'help',
            'list',
            'coverage',
            'documentation',
            'test',
            'update',
            'travis:build',
        );

        $this->assertSame($expected, array_keys($this->application->all()));
    }

    public function testDoRun()
    {
        $commandName = uniqid();
        Phake::when($this->application)
            ->defaultCommandName(Phake::anyParameters())
            ->thenReturn($commandName)
        ;
        Phake::when($this->application)
            ->rawArguments(Phake::anyParameters())
            ->thenReturn(array())
        ;
        $command = Phake::partialMock(
            'Symfony\Component\Console\Command\Command',
            $commandName
        );
        $this->application->add($command);
        $this->application->setAutoExit(false);
        $input = new ArrayInput(array());
        $output = Phake::partialMock('Symfony\Component\Console\Output\NullOutput');
        $this->application->run($input, $output);
        $expectedInput = new ArrayInput(array('command' => $commandName));

        Phake::inOrder(
            Phake::verify($this->application)->defaultCommandName(),
            Phake::verify($command)->run(Phake::capture($actualInput), $output)
        );
        $this->assertSame($commandName, $actualInput->getFirstArgument());
    }

    public function testRawArguments()
    {
        $method = $this->reflector->getMethod('rawArguments');
        $method->setAccessible(true);
        $argv = $_SERVER['argv'];
        $_SERVER['argv'] = array('foo', 'bar', 'baz');
        $actual = $method->invoke($this->application);
        $_SERVER['argv'] = $argv;

        $this->assertSame(array('bar', 'baz'), $actual);
    }

    public function testDefaultCommandName()
    {
        $method = $this->reflector->getMethod('defaultCommandName');
        $method->setAccessible(true);

        $this->assertSame('test', $method->invoke($this->application));
    }
}
