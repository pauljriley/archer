<?php
namespace Icecave\Archer\Console\Command;

use PHPUnit_Framework_TestCase;
use Phake;
use ReflectionObject;

class AbstractPHPUnitCommandTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->fileSystem = Phake::mock(
            'Icecave\Archer\FileSystem\FileSystem'
        );
        $this->phpFinder = Phake::mock(
            'Symfony\Component\Process\PhpExecutableFinder'
        );
        $this->phpunitFinder = Phake::mock(
            'Icecave\Archer\Process\PHPUnitExecutableFinder'
        );
        $this->phpConfigurationReader = Phake::mock(
            'Icecave\Archer\Configuration\PHPConfigurationReader'
        );
        $this->configurationFileFinder = Phake::mock(
            'Icecave\Archer\Configuration\ConfigurationFileFinder'
        );
        $this->processFactory = Phake::mock(
            'Icecave\Archer\Process\ProcessFactory'
        );
        $this->command = Phake::partialMock(
            __NAMESPACE__ . '\AbstractPHPUnitCommand',
            $this->fileSystem,
            $this->phpFinder,
            $this->phpunitFinder,
            $this->phpConfigurationReader,
            $this->configurationFileFinder,
            $this->processFactory,
            'cmd'
        );

        $this->application = Phake::mock('Icecave\Archer\Console\Application');
        $this->process = Phake::mock('Symfony\Component\Process\Process');

        Phake::when($this->command)
            ->getApplication(Phake::anyParameters())
            ->thenReturn($this->application)
        ;

        Phake::when($this->processFactory)
            ->create(Phake::anyParameters())
            ->thenReturn($this->process)
        ;

        Phake::when($this->phpFinder)
            ->find(Phake::anyParameters())
            ->thenReturn('/path/to/php')
        ;

        Phake::when($this->phpunitFinder)
            ->find(Phake::anyParameters())
            ->thenReturn('/path/to/phpunit')
        ;
    }

    public function testConstructor()
    {
        Phake::verify($this->command)->ignoreValidationErrors();

        $this->assertSame($this->fileSystem, $this->command->fileSystem());
        $this->assertSame($this->phpFinder, $this->command->phpFinder());
        $this->assertSame($this->phpunitFinder, $this->command->phpunitFinder());
        $this->assertSame($this->phpConfigurationReader, $this->command->phpConfigurationReader());
        $this->assertSame($this->configurationFileFinder, $this->command->configurationFileFinder());
        $this->assertSame($this->processFactory, $this->command->processFactory());
    }

    public function testConstructorDefaults()
    {
        $this->command = Phake::partialMock(
            __NAMESPACE__ . '\AbstractPHPUnitCommand',
            null,
            null,
            null,
            null,
            null,
            null,
            'cmd'
        );

        $this->assertInstanceOf(
            'Icecave\Archer\FileSystem\FileSystem',
            $this->command->fileSystem()
        );
        $this->assertInstanceOf(
            'Symfony\Component\Process\PhpExecutableFinder',
            $this->command->phpFinder()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Process\PHPUnitExecutableFinder',
            $this->command->phpunitFinder()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Configuration\PHPConfigurationReader',
            $this->command->phpConfigurationReader()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Configuration\ConfigurationFileFinder',
            $this->command->configurationFileFinder()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Process\ProcessFactory',
            $this->command->processFactory()
        );
    }

    public function testGetHelp()
    {
        Phake::when($this->process)
            ->run(Phake::anyParameters())
            ->thenGetReturnByLambda(
                function ($callback) {
                    $callback('out', '<phpunit help>');
                }
            );

        $expectedHelp  = '<info>This command forwards all arguments to PHPUnit.</info>';
        $expectedHelp .= PHP_EOL;
        $expectedHelp .= PHP_EOL;
        $expectedHelp .= '<phpunit help>';

        $result = $this->command->getHelp();

        $shim = null;

        Phake::inOrder(
            Phake::verify($this->phpFinder)->find(),
            Phake::verify($this->phpunitFinder)->find(),
            Phake::verify($this->processFactory)->create('/path/to/php', '/path/to/phpunit', '--help'),
            Phake::verify($this->process)->run($this->isInstanceOf('Closure'))
        );

        $this->assertSame($expectedHelp, $result);
    }

    public function testGenerateArgumentsFiltering()
    {
        Phake::when($this->command)
            ->phpConfigurationArguments()
            ->thenReturn(array());

        Phake::when($this->command)
            ->readPHPConfiguration()
            ->thenReturn(array());

        Phake::when($this->command)
            ->findPHPUnitConfiguration()
            ->thenReturn('/path/to/config.xml');

        $reflector = new ReflectionObject($this->command);
        $method = $reflector->getMethod('generateArguments');
        $method->setAccessible(true);

        $input = array(
            '--quiet',
            '-q',
            '--version',
            '-V',
            '--ansi',
            '--no-ansi',
            '--no-interaction',
            '-n',
        );

        $expected = array(
            '/path/to/php',
            '/path/to/phpunit',
            '--configuration',
            '/path/to/config.xml',
            '--color'
        );

        $result = $method->invoke($this->command, '/path/to/php', '/path/to/phpunit', $input);

        $this->assertSame($expected, $result);
    }
}
