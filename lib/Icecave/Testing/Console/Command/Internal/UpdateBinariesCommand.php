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
        foreach (array('chassis', 'woodhouse') as $package) {
            $output->writeln(sprintf('Fetching <info>icecave/%1$s</info> PHAR into <info>bin/ict-%1$s</info>.', $package));
            $this->updateBinary($package);
        }
    }

    protected function updateBinary($packageName)
    {
        $content = $this->isolator->file_get_contents('http://icecave.com.au/' . $packageName . '/' . $packageName);
        $target  = $this->getApplication()->packageRoot() . '/bin/ict-' . $packageName;
        $this->isolator->file_put_contents($target, $content);
        $this->isolator->chmod($target, 0755);
    }
}
