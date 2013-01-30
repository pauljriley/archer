<?php
namespace Icecave\Testing\Console\Command\Travis;

use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class FetchPublicKeyCommand extends Command
{
    protected function configure()
    {
        $this->setName('travis:fetch-public-key');
        $this->setDescription('Fetch the Travis CI public key for this repository.');

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
         * 1. Download new public key.
         * 2. Store in .ict.key
         * 3. Print notice about re-building coverage environment if key has changed
         */
        throw new \Exception('Not implemented error.');
    }
}
