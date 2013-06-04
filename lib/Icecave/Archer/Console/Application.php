<?php
namespace Icecave\Archer\Console;

use Icecave\Archer\FileSystem\FileSystem;
use Icecave\Archer\Support\Isolator;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends SymfonyApplication
{
    /**
     * @param string          $packageRoot
     * @param FileSystem|null $fileSystem
     * @param Isolator|null   $isolator
     */
    public function __construct(
        $packageRoot,
        FileSystem $fileSystem = null,
        Isolator $isolator = null
    ) {
        parent::__construct('Archer', '0.4.2');

        if (null === $fileSystem) {
            $fileSystem = new FileSystem;
        }

        $this->packageRoot = $packageRoot;
        $this->fileSystem = $fileSystem;
        $this->isolator = Isolator::get($isolator);

        $this->getHelperSet()->set(new Helper\HiddenInputHelper);

        $this->add(new Command\CoverageCommand);
        $this->add(new Command\DocumentationCommand);
        $this->add(new Command\TestCommand);
        $this->add(new Command\UpdateCommand);

        $this->add(new Command\Internal\UpdateBinariesCommand($fileSystem));

        $this->add(new Command\Travis\BuildCommand(null, $isolator));
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
            $input = new ArrayInput(
                array(
                    'command' => $this->defaultCommandName(),
                )
            );
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
    private $isolator;
}
