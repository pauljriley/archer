<?php
namespace Icecave\Testing\Console\Command\Travis;

use Icecave\Testing\GitHub\GitConfigReader;
use Icecave\Testing\Support\FileManager;
use Icecave\Testing\Travis\TravisClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Icecave\Testing\Console\Command\AbstractCommand;
use Symfony\Component\Yaml\Parser;

class UpdateConfigCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('travis:update-config');
        $this->setDescription('Regenerate the Travis CI configuration file.');

        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'The path to the root of the project.',
            '.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fileManager->setPackageRoot($input->getArgument('path'));

        $artifacts = $this->travisConfigManager->updateConfig();

        if (!$artifacts) {
            $output->writeln('<comment>Artifact publication is not available as no GitHub OAuth token has been configured.</comment>');
        }

        $output->writeln('Travis CI configuration updated successfully.');
        $output->write(PHP_EOL);
    }
}
