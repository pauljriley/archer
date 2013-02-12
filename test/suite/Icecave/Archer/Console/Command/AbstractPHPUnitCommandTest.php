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

        $this->_fileSystem = Phake::mock(
            'Icecave\Archer\FileSystem\FileSystem'
        );
        $this->_phpFinder = Phake::mock(
            'Symfony\Component\Process\PhpExecutableFinder'
        );
        $this->_phpunitFinder = Phake::mock(
            'Icecave\Archer\Process\PHPUnitExecutableFinder'
        );
        $this->_phpConfigurationReader = Phake::mock(
            'Icecave\Archer\Configuration\PHPConfigurationReader'
        );
        $this->_configurationFileFinder = Phake::mock(
            'Icecave\Archer\Configuration\ConfigurationFileFinder'
        );
        $this->_processFactory = Phake::mock(
            'Icecave\Archer\Process\ProcessFactory'
        );
        $this->_command = Phake::partialMock(
            __NAMESPACE__ . '\AbstractPHPUnitCommand',
            $this->_fileSystem,
            $this->_phpFinder,
            $this->_phpunitFinder,
            $this->_phpConfigurationReader,
            $this->_configurationFileFinder,
            $this->_processFactory,
            'cmd'
        );

        $this->_application = Phake::mock('Icecave\Archer\Console\Application');
        $this->_process = Phake::mock('Symfony\Component\Process\Process');

        Phake::when($this->_command)
            ->getApplication(Phake::anyParameters())
            ->thenReturn($this->_application)
        ;

        Phake::when($this->_processFactory)
            ->create(Phake::anyParameters())
            ->thenReturn($this->_process)
        ;

        Phake::when($this->_phpFinder)
            ->find(Phake::anyParameters())
            ->thenReturn('/path/to/php')
        ;

        Phake::when($this->_phpunitFinder)
            ->find(Phake::anyParameters())
            ->thenReturn('/path/to/phpunit')
        ;
    }

    public function testConstructor()
    {
        Phake::verify($this->_command)->ignoreValidationErrors();

        $this->assertSame($this->_fileSystem, $this->_command->fileSystem());
        $this->assertSame($this->_phpFinder, $this->_command->phpFinder());
        $this->assertSame($this->_phpunitFinder, $this->_command->phpunitFinder());
        $this->assertSame($this->_phpConfigurationReader, $this->_command->phpConfigurationReader());
        $this->assertSame($this->_configurationFileFinder, $this->_command->configurationFileFinder());
        $this->assertSame($this->_processFactory, $this->_command->processFactory());
    }

    public function testConstructorDefaults()
    {
        $this->_command = Phake::partialMock(
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
            $this->_command->fileSystem()
        );
        $this->assertInstanceOf(
            'Symfony\Component\Process\PhpExecutableFinder',
            $this->_command->phpFinder()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Process\PHPUnitExecutableFinder',
            $this->_command->phpunitFinder()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Configuration\PHPConfigurationReader',
            $this->_command->phpConfigurationReader()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Configuration\ConfigurationFileFinder',
            $this->_command->configurationFileFinder()
        );
        $this->assertInstanceOf(
            'Icecave\Archer\Process\ProcessFactory',
            $this->_command->processFactory()
        );
    }

    public function testGetHelp()
    {
        Phake::when($this->_process)
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

        $result = $this->_command->getHelp();

        $shim = null;

        Phake::inOrder(
            Phake::verify($this->_phpFinder)->find(),
            Phake::verify($this->_phpunitFinder)->find(),
            Phake::verify($this->_processFactory)->create('/path/to/php', '/path/to/phpunit', '--help'),
            Phake::verify($this->_process)->run($this->isInstanceOf('Closure'))
        );

        $this->assertSame($expectedHelp, $result);
    }

    public function testGenerateArgumentsFiltering()
    {
        Phake::when($this->_command)
            ->phpConfigurationArguments()
            ->thenReturn(array());

        Phake::when($this->_command)
            ->readPHPConfiguration()
            ->thenReturn(array());

        Phake::when($this->_command)
            ->findPHPUnitConfiguration()
            ->thenReturn('/path/to/config.xml');

        $reflector = new ReflectionObject($this->_command);
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

        $result = $method->invoke($this->_command, '/path/to/php', '/path/to/phpunit', $input);

        $this->assertSame($expected, $result);
    }
}
