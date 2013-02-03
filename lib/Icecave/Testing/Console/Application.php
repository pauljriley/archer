<?php
namespace Icecave\Testing\Console;

use Icecave\Testing\FileSystem\FileSystem;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends SymfonyApplication
{
    /**
     * @param string          $packageRoot
     * @param FileSystem|null $fileSystem
     */
    public function __construct($packageRoot, FileSystem $fileSystem = null)
    {
        parent::__construct('Icecave Testing', '3.0.0-dev');

        if (null === $fileSystem) {
            $fileSystem = new FileSystem;
        }

        $this->packageRoot = $packageRoot;
        $this->fileSystem = $fileSystem;

        $this->add(new Command\CoverageCommand);
        $this->add(new Command\TestCommand);
        $this->add(new Command\UpdateCommand);

        $this->add(new Command\GitHub\CreateTokenCommand);
        $this->add(new Command\GitHub\FetchTokenCommand);

        $this->add(new Command\Internal\UpdateBinariesCommand($fileSystem));
    }

    /**
     * @return string
     */
    public function packageRoot()
    {
        return $this->packageRoot;
    }

    /**
     * @return FileSystem
     */
    public function fileSystem()
    {
        return $this->fileSystem;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return integer
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $rawArguments = $this->rawArguments();
        if (array() === $rawArguments) {
            $input = new ArrayInput(array(
                'command' => $this->defaultCommandName(),
            ));
        }

        return parent::doRun($input, $output);
    }

    /**
     * @return array<string>
     */
    public function rawArguments()
    {
        $argv = $_SERVER['argv'];
        array_shift($argv);

        return $argv;
    }

    /**
     * @return string
     */
    protected function defaultCommandName()
    {
        return 'test';
    }

    private $packageRoot;
    private $fileSystem;
}
