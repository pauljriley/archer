<?php
namespace Icecave\Testing\Console\Command\Internal;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

class UpdateBundledPackagesCommand extends Command
{
    protected function configure()
    {
        $this->setName('internal:update-bundled-packages');
        $this->setDescription('Update the PHAR packages that are bundled with icecave/testing.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = array();
        foreach ($this->bundledPackages() as $package) {
            $output->writeln('Fetching <info>' . $package . '</info>.');
            $paths[$package] = $this->fetchPackage($package);
        }

        $packageRoot = $this->getApplication()->packageRoot();
        $nearPath = $paths['icecave/near'];
        $packageCount = count($paths);
        unset($paths['icecave/near']);

        // Build the 'near' phar ...
        $output->writeln('Building <info>icecave/near</info> PHAR archive.');
        $this->passthru(
            '%s/bin/near compile %s %s/bin/ict-near',
            $nearPath,
            $nearPath,
            $packageRoot
        );

        // Build the other phars ...
        foreach ($paths as $package => $path) {
            list($vendor, $name) = explode('/', $package, 2);
            $output->writeln('Building <info>' . $package . '</info> PHAR archive.');
            $this->passthru(
                '%s/bin/ict-near compile %s %s/bin/ict-%s',
                $packageRoot,
                $path,
                $packageRoot,
                $name
            );
        }

        $output->writeln(
            sprintf(
                'Updated <info>%d</info> packages.',
                $packageCount
            )
        );
    }

    protected function fetchPackage($package)
    {
        $tempPath = sprintf(
            '%s/ict-%d/%s',
            sys_get_temp_dir(),
            getmypid(),
            $package
        );

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        $this->passthru(
            'composer --quiet create-project %s %s',
            $package,
            $tempPath
        );

        return $tempPath;
    }

    protected function composerConfig()
    {
        $composerFile = $this->getApplication()->packageRoot() . '/composer.json';
        $composerJson = file_get_contents($composerFile);

        return json_decode($composerJson);
    }

    protected function bundledPackages()
    {
        $composerConfig = $this->composerConfig();

        if (isset($composerConfig->extra) && isset($composerConfig->extra->{'bundled-packages'})) {
            return $composerConfig->extra->{'bundled-packages'};
        } else {
            return array();
        }
    }

    protected function passthru($command)
    {
        $arguments = array_slice(func_get_args(), 1);
        $arguments = array_map('escapeshellarg', $arguments);
        $command   = vsprintf($command, $arguments);

        $exitCode = null;
        passthru($command, $exitCode);

        if (0 !== $exitCode) {
            throw new RuntimeException('Failed executing "' . $command . '".');
        }
    }
}
