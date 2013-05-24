<?php
namespace Icecave\Archer\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Icecave\Archer\Documentation\DocumentationGenerator;

class DocumentationCommand extends Command
{
    public function __construct(DocumentationGenerator $generator = null)
    {
        if (null === $generator) {
            $generator = new DocumentationGenerator;
        }

        $this->generator = $generator;

        parent::__construct();
    }

    /**
     * @return DocumentationGenerator
     */
    public function generator()
    {
        return $this->generator;
    }

    protected function configure()
    {
        $this->setName('documentation');
        $this->setDescription('Generate documentation for a project.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Generating documentation...</info>');

        $this->generator->generate();
    }

    private $generator;
}
