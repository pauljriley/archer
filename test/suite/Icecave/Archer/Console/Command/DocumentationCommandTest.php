<?php
namespace Icecave\Archer\Console\Command;

use Phake;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Input\StringInput;

class DocumentationCommandTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->generator = Phake::mock(
            'Icecave\Archer\Documentation\DocumentationGenerator'
        );
        $this->command = new DocumentationCommand(
            $this->generator
        );

        $this->output = Phake::mock(
            'Symfony\Component\Console\Output\OutputInterface'
        );
    }

    public function testConstructor()
    {
        $this->assertSame($this->generator, $this->command->generator());
    }

    public function testConstructorDefaults()
    {
        $this->command = new DocumentationCommand;

        $this->assertInstanceOf(
            'Icecave\Archer\Documentation\DocumentationGenerator',
            $this->command->generator()
        );
    }

    public function testConfigure()
    {
        $this->assertSame('documentation', $this->command->getName());
        $this->assertSame(
            'Generate documentation for a project.',
            $this->command->getDescription()
        );
    }

    public function testExecute()
    {
        $this->input = new StringInput('');
        $this->command->run($this->input, $this->output);

        Phake::inOrder(
            Phake::verify($this->output)->write(
                '<info>Generating documentation... </info>'
            ),
            Phake::verify($this->generator)->generate(),
            Phake::verify($this->output)->writeln('done.')
        );
    }
}
