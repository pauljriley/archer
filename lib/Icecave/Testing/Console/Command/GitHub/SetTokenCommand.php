<?php
namespace Icecave\Testing\Console\Command\GitHub;

use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class SetTokenCommand extends Command
{
    protected function configure()
    {
        $this->setName('github:set-token');
        $this->setDescription('Set the GitHub OAuth token to use for publishing test results.');

        $this->addArgument(
            'oauth-token',
            InputArgument::REQUIRED,
            'A GitHub OAuth token with write access to the GitHub repository used for publishing.'
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
        /**
         * 1. Register a new token [if --create]
         * 2. Re-encrypt coverage environment
         * 3. Store in .ict.env
         * 4. Copy into .travis.yml
         */
        throw new \Exception('Not implemented error.');
    }
}
