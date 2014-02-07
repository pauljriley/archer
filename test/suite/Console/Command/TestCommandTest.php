<?php
namespace Icecave\Archer\Console\Command;

use Phake;
use PHPUnit_Framework_TestCase;
use ReflectionObject;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;

/**
 * @covers \Icecave\Archer\Console\Command\AbstractPHPUnitCommand
 * @covers \Icecave\Archer\Console\Command\TestCommand
 */
class TestCommandTest extends PHPUnit_Framework_TestCase
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
            __NAMESPACE__ . '\TestCommand',
            $this->fileSystem,
            $this->phpFinder,
            $this->phpunitFinder,
            $this->phpConfigurationReader,
            $this->configurationFileFinder,
            $this->processFactory
        );

        $this->application = Phake::mock('Icecave\Archer\Console\Application');
        $this->process = Phake::mock('Symfony\Component\Process\Process');

        Phake::when($this->command)
            ->getApplication(Phake::anyParameters())
            ->thenReturn($this->application)
        ;

        Phake::when($this->application)
            ->rawArguments(Phake::anyParameters())
            ->thenReturn(array('foo', 'bar'))
        ;

        Phake::when($this->phpConfigurationReader)
            ->read(Phake::anyParameters())
            ->thenReturn(array(
                'baz' => 'qux',
                'doom' => 'splat',
            ))
        ;

        Phake::when($this->configurationFileFinder)
            ->find(Phake::anyParameters())
            ->thenReturn('/path/to/phpunit.xml')
        ;

        Phake::when($this->processFactory)
            ->createFromArray(Phake::anyParameters())
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

        $this->reflector = new ReflectionObject($this->command);
        $this->executeMethod = $this->reflector->getMethod('execute');
        $this->executeMethod->setAccessible(true);

        $this->input = Phake::mock('Symfony\Component\Console\Input\InputInterface');

        // used for closures
        $that = $this;

        $this->stdErr = '';
        $this->errorOutput = Phake::mock('Symfony\Component\Console\Output\OutputInterface');
        Phake::when($this->errorOutput)
            ->write(Phake::anyParameters())
            ->thenGetReturnByLambda(
                function ($data) use ($that) {
                    $that->stdErr .= $data;
                }
            )
        ;

        $this->stdOut = '';
        $this->output = Phake::mock('Symfony\Component\Console\Output\ConsoleOutputInterface');
        Phake::when($this->output)
            ->write(Phake::anyParameters())
            ->thenGetReturnByLambda(
                function ($data) use ($that) {
                    $that->stdOut .= $data;
                }
            )
        ;
        Phake::when($this->output)
            ->writeln(Phake::anyParameters())
            ->thenGetReturnByLambda(
                function ($data) use ($that) {
                    $that->stdOut .= $data . "\n";
                }
            )
        ;
        Phake::when($this->output)
            ->getErrorOutput(Phake::anyParameters())
            ->thenReturn($this->errorOutput)
        ;

        Phake::when($this->process)
            ->run(Phake::anyParameters())
            ->thenGetReturnByLambda(
                function ($callback) {
                    $callback('out', "out\nout\n");
                    $callback('err', "err\nerr\n");

                    return 111;
                }
            )
        ;
    }

    public function testConfigure()
    {
        $expectedInputDefinition = new InputDefinition;
        $expectedInputDefinition->addArgument(new InputArgument(
            'argument',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Argument(s) to pass to PHPUnit.'
        ));

        $this->assertSame('test', $this->command->getName());
        $this->assertSame(
            'Run the test suite for a project.',
            $this->command->getDescription()
        );
        $this->assertEquals(
            $expectedInputDefinition,
            $this->command->getDefinition()
        );
    }

    public function testExecute()
    {
        $exitCode = $this->executeMethod->invoke(
            $this->command,
            $this->input,
            $this->output
        );
        $expectedStdout = <<<'EOD'
<info>Using PHP:</info> /path/to/php
<info>Using PHPUnit:</info> /path/to/phpunit
out
out

EOD;
        $expectedStderr = <<<'EOD'
err
err

EOD;

        $this->assertSame(111, $exitCode);
        $this->assertSame($expectedStdout, $this->stdOut);
        $this->assertSame($expectedStderr, $this->stdErr);
        Phake::inOrder(
            Phake::verify($this->phpFinder)->find(),
            Phake::verify($this->phpunitFinder)->find(),
            Phake::verify($this->phpConfigurationReader)
                ->read(Phake::capture($actualPhpConfigurationPaths)),
            Phake::verify($this->configurationFileFinder)->find(
                Phake::capture($actualPhpunitConfigurationPaths),
                './vendor/icecave/archer/res/phpunit/phpunit.xml'
            ),
            Phake::verify($this->processFactory)
                ->createFromArray(Phake::capture($actualArguments)),
            Phake::verify($this->process)->setTimeout(null),
            Phake::verify($this->command)->passthru(
                $this->identicalTo($this->process),
                $this->identicalTo($this->output)
            )
        );
        $this->assertSame(array(
            './vendor/icecave/archer/res/php/php.ini',
            './test/php.ini',
            './php.ini',
        ), $actualPhpConfigurationPaths);
        $this->assertSame(array(
            './phpunit.xml',
            './phpunit.xml.dist',
            './test/phpunit.xml',
            './test/phpunit.xml.dist',
        ), $actualPhpunitConfigurationPaths);
        $this->assertSame(array(
            '/path/to/php',
            '--define',
            'baz=qux',
            '--define',
            'doom=splat',
            '/path/to/phpunit',
            '--configuration',
            '/path/to/phpunit.xml',
            'bar',
        ), $actualArguments);
    }
}
