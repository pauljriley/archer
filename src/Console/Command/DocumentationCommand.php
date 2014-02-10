<?php
namespace Icecave\Archer\Console\Command;

use Icecave\Archer\Documentation\DocumentationGenerator;
use Icecave\Archer\Support\Liftoff\Launcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentationCommand extends Command
{
    public function __construct(
        DocumentationGenerator $generator = null,
        Launcher $launcher = null
    ) {
        if (null === $generator) {
            $generator = new DocumentationGenerator;
        }
        if (null === $launcher) {
            $launcher = new Launcher;
        }

        $this->generator = $generator;
        $this->launcher = $launcher;

        parent::__construct();
    }

    /**
     * @return DocumentationGenerator
     */
    public function generator()
    {
        return $this->generator;
    }

    /**
     * @return Launcher
     */
    public function launcher()
    {
        return $this->launcher;
    }

    protected function configure()
    {
        $this->setName('documentation');
        $this->setDescription('Generate documentation for a project.');
        $this->addOption(
            'open',
            'o',
            InputOption::VALUE_NONE,
            'Open the generated documentation in your default web browser.'
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write('<info>Generating documentation... </info>');
        $this->generator()->generate();
        $output->writeln('done.');

        if ($input->getOption('open')) {
            $output->write('<info>Opening documentation... </info>');
            $this->launcher()->launch('./artifacts/documentation/api/index.html');
            $output->writeln('done.');
        }
    }

    private $generator;
    private $launcher;
}
