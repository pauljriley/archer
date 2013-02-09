<?php
namespace Icecave\Archer\Console\Command\Internal;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class UpdateBinariesCommand extends AbstractInternalCommand
{
    protected function configure()
    {
        $this->setName('internal:update-binaries');
        $this->setDescription('Update the PHAR packages that are bundled with icecave/archer.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        foreach (array('woodhouse') as $package) {
            $output->writeln(sprintf('Fetching <info>icecave/%1$s</info> PHAR into <info>bin/%1$s</info>.', $package));
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
            '%s/bin/woodhouse',
            $this->getApplication()->packageRoot(),
            $packageName
        );
        $this->fileSystem()->write($target, $content);
        $this->fileSystem()->chmod($target, 0755);
    }
}
