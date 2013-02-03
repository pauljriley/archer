<?php
namespace Icecave\Testing\Console\Command\Internal;

use Icecave\Testing\Support\Isolator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class UpdateBinariesCommand extends AbstractInternalCommand
{
    protected function configure()
    {
        $this->setName('internal:update-binaries');
        $this->setDescription('Update the PHAR packages that are bundled with icecave/testing.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        foreach (array('woodhouse') as $package) {
            $output->writeln(sprintf('Fetching <info>icecave/%1$s</info> PHAR into <info>res/bin/%1$s</info>.', $package));
            $this->updateBinary($package);
        }
    }

    protected function updateBinary($packageName)
    {
        $content = $this->fileSystem()->read(sprintf(
            'http://icecave.com.au/%s/%s',
            rawurlencode($packageName),
            rawurlencode($packageName)
        ));
        $target = sprintf(
            '%s/res/bin/%s',
            $this->getApplication()->packageRoot(),
            $packageName
        );
        $this->fileSystem()->write($target, $content);
        $this->fileSystem()->chmod($target, 0755);
    }
}
