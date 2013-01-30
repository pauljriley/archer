<?php
namespace Icecave\Testing\Console\Command;

use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('initialize');
        $this->setDescription('Initialize a new project.');

        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'The path to the root of the project.',
            '.'
        );

        $this->addOption(
            'oauth-token',
            't',
            InputOption::VALUE_REQUIRED,
            'The GitHub OAuth token to use for composer and coverage report publishing.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        throw new \Exception('Not implemented error.');

        $packageName  = $input->getArgument('package-name');
        $projectPath  = rtrim($input->getArgument('path'), '/');
        $composerPath = $projectPath . '/composer.json';

        list(, $vendor, $package) = $matches;

        $skeletons = array('initialize', 'update');

        $variables = array(
            'dot'            => '.',
            'vendor'         => $vendor,
            'package'        => $package,
            'vendor-tc'      => ucfirst($vendor),
            'package-tc'     => ucfirst($package),
            'source-dir'     => 'src',
            'github-account' => $vendor,
        );

        // Handle a few icecave specifics ...
        if ($vendor === 'icecave') {
            $variables['source-dir'] = 'lib';
            $variables['github-account'] = 'IcecaveStudios';
        }

        // Get the username and email address from global git config ...
        if ($value = $this->readGitConfig('user.name')) {
            $variables['name'] = $value;
        }

        if ($value = $this->readGitConfig('user.email')) {
            $variables['email'] = $value;
        }

        if ($token = $input->getOption('oauth-token')) {
            $skeletons[] = 'coverage';
            $variables['travis-public-key'] = $key = $this->travisKey($variables['github-account'], $package);
            $variables['oauth-secure-environment'] = $this->encryptToken($key, $token);
        }

        // Make the project directory if it doesn't already exist ...
        if (!is_dir($projectPath)) {
            mkdir($projectPath, 0755, true);
        }

        // Build a temporary composer.json to pull down the latest icecave/testing ...
        file_put_contents($composerPath, '{ "require-dev": { "icecave/testing": "2.1.0.x-dev@dev" } }');
        $output->writeln('Installing <info>icecave/testing</info>.');
        $this->passthru('composer install --dev --working-dir %s', $projectPath);
        unlink($composerPath);

        $output->writeln('Cloning project skeleton.');
        $this->cloneSkeletons($projectPath, $skeletons, $variables);

        $output->writeln('Updating composer dependencies.');
        $this->passthru('composer update --dev --working-dir %s', $projectPath);

        chdir($projectPath);
        $this->passthru('vendor/bin/typhoon generate');
    }
}
