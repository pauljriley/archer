<?php
namespace Icecave\Testing\Console\Command\GitHub;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class FetchTokenCommand extends Command
{
    protected function configure()
    {
        $this->setName('github:fetch-token');
        $this->setDescription('Fetch an existing GitHub OAuth token (using GitHub credentials) and set it as the current token.');

        $this->addArgument(
            'username',
            InputArgument::REQUIRED,
            'GitHub username.'
        );

        $this->addOption(
            'password',
            'p',
            InputOption::VALUE_REQUIRED,
            'GitHub password.'
        );

        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'The path to the root of the project.',
            '.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        throw new \Exception('Not implemented error.');
    }
}
